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
            $this->value = array_merge($this->value, (is_array($value) ? $value : []));
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
                if (isset($this->value[$k])) {
                    unset($this->value[$k]);
                } else {
                    throw new \Exception(sprintf('Unable to remove key "%s" from node.', $k));
                }
            }
            $this->value = array_values($this->value);
            return $this;    
        } else
        if (isset($this->value[$key])) {
            unset($this->value[$key]);
            $this->value = array_values($this->value);
            return $this;    
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
        return isset($this->value[$key]);
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
            case 'int':     return is_int($this->value[$key]);
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

}