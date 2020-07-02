<?php declare(strict_types=1);

namespace Yami\Migration;

class Node
{

    /**
     * @var string
     */
    protected $selector;

    /**
     * @var array
     */
    protected $value;

    /**
     * @param mixed the node contents
     * @param string the original selector
     */
    public function __construct($value, string $selector)
    {
        $this->value = $value;
        $this->selector = $selector;
    }

    /**
     * Return the current node value
     * 
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * Sets or replaces the node value
     * 
     * @param mixed $value
     */
    public function set($value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Add a value to a node
     * 
     * @param mixed $value
     * 
     * @return Yami\Migration\Node
     */
    public function add($value): self
    {
        if (is_array($this->value)) {
            $this->value = array_merge($this->value, (is_array($value) ? $value : [$value]));
            return $this;
        }
        // Special case for empty nodes
        if ($this->value === null) {
            $this->value = $value;
            return $this;
        }
        throw new \Exception(sprintf('Unable to add value to scalar node of "%s".', $this->selector));
    }

    /**
     * Remove a value from the node if it exists
     * 
     * @param mixed $key
     * 
     * @return Yami\Migration\Node
     */
    public function remove($key): self
    {
        if (is_array($key)) {
            foreach($key as $k) {
                if (!$this->removeKeyOrValue($k)) {
                    throw new \Exception(sprintf('Unable to remove key "%s" from node.', $k));
                }
            }
            $this->removeDuplicates();
            return $this;    
        } else {
            if ($this->removeKeyOrValue($key)) {
                $this->removeDuplicates();
                return $this;
            }
        }
        throw new \Exception(sprintf('Unable to remove key "%s" from node.', $key));
    }

    /**
     * Recurses down the node to see if the key is found
     * 
     * @param mixed the key to search for
     * 
     * @return bool
     */
    public function has($key): bool
    {
        return is_array($this->value) && isset($this->value[$key]);
    }

    /**
     * Recurses down the node to see if the key is found
     * and contains an array
     * 
     * @param mixed the key to search for
     * 
     * @return bool
     */
    public function containsArray($key): bool
    {
        return $this->has($key) && is_array($this->value[$key]);
    }

    /**
     * Recurses down the node to see if the key contains
     * a value of the specified type
     * 
     * @param mixed the key to search for
     * @param string the type to validate for
     * 
     * @return bool
     */
    public function containsType($key, string $type): bool
    {
        if (!$this->has($key)) {
            return false;
        }

        switch($type) {
            case 'integer': return is_int($this->value[$key]);
            case 'string':  return is_string($this->value[$key]);
            case 'float':   return is_float($this->value[$key]);
            case 'boolean': return is_bool($this->value[$key]);
        }

        throw new \Exception(sprintf('Unknown type "%s" specified.', $type));
    }

    /**
     * Return the current $selector
     * 
     * @return string
     */
    public function getSelector(): string
    {
        return $this->selector;
    }

    /**
     * Dump the node to stdout
     * 
     * @return void
     */
    public function dump(): void
    {
        var_dump($this->value);
    }

    /**
     * Removes a key or value, depending on the state of $this->value
     * 
     * @param mixed the key to search for
     * 
     * @return bool
     */
    private function removeKeyOrValue($key): bool
    {
        if (!is_array($this->value)) {
            return false;
        }
        $valueHasKeys = count(array_filter(array_keys($this->value), 'is_string')) > 0;
        if ($valueHasKeys) {
            if (isset($this->value[$key])) {
                unset($this->value[$key]);
                return true;
            }
        } else {
            if (is_numeric($key)) {
                if (isset($this->value[$key])) {
                    unset($this->value[$key]);
                    return true;
                }
            } else {
                $found = array_search($key, $this->value);
                if ($found !== false) {
                    array_splice($this->value, $found, 1);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Remove duplicate values from an array with numeric indexes
     * 
     * @return void
     */
    private function removeDuplicates(): void
    {
        if (is_array($this->value) && count(array_filter(array_keys($this->value), 'is_string')) == 0) {
            $this->value = array_values($this->value);
        }
    }

}