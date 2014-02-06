<?php

namespace Kumite;

class Variant
{
    private $key;
    private $properties = array();

    public function __construct($key, array $properties=array())
    {
        $this->key = $key;
        $this->properties = $properties;
    }

    public function key()
    {
        return $this->key;
    }

    public function properties()
    {
        if (isset($this->properties)) {
            return $this->properties;
        }
        return array();
    }

    public function property($key, $default=null)
    {
        if (isset($this->properties[$key])) {
            return $this->properties[$key];
        }
        return $default;
    }

    public function __toString()
    {
        return $this->key;
    }
}
