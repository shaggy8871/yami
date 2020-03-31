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
     * @param mixed $value
     */
    public function __construct($value, string $selector)
    {
        $this->value = $value;
        $this->selector = $selector;
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
     * Add a value to a Node at the root
     * 
     * @param mixed $value
     * 
     * @return Yami\Migration\Node
     */
    public function add($value): self
    {
        if (is_array($this->value)) {
            if (is_array($value)) {
                foreach($value as $k => $v) {
                    $this->value[$k] = $v;
                }
            } else {
                $this->value[] = $value;
            }
            return $this;    
        }
        throw new \Exception(sprintf('Unable to add value to scalar node of "%s".', $this->selector));
    }

    /**
     * Remove a value from the Node if it exists
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
            return $this;    
        } else
        if (isset($this->value[$key])) {
            unset($this->value[$key]);
            return $this;    
        }
        throw new \Exception(sprintf('Unable to remove key "%s" from node.', $key));
    }

    /**
     * Recurses down the node to see if the key is found
     */
    public function contains(string $key): bool
    {

    }

    /**
     * Recurses down the node to see if the key is found
     * and contains an array
     */
    public function containsArray(string $key): bool
    {

    }

    /**
     * Recurses down the node to see if the key contains
     * a value of the specified type
     */
    public function containsType(string $key, string $type): bool
    {

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
     * Return the current $value
     * 
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Dump the node to stdout
     */
    public function dump(): void
    {
        var_dump($this->value);
    }

}