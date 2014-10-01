<?php

namespace Kumite;

class Test
{

    private $storageAdapter;
    private $allocator;
    private $allocatorOptions = array();
    private $key;
    private $enabled;
    private $default = 'control';
    private $version;
    private $variants = array();
    private $events;
    private $results;

    public function __construct($key, $config, $allocator, $storageAdapter)
    {
        $this->storageAdapter = $storageAdapter;
        $this->key = $key;
        $this->allocator = $allocator;

        if (!$allocator instanceof Allocator && !is_callable($allocator) ) {
            throw new Exception('Allocator must be callable, instance of Kumite\\Allocator');
        }

        foreach ($this->requiredKeys() as $requiredKey) {
            if (!array_key_exists($requiredKey, $config)) {
                throw new Exception("Test '$key' missing config key '$requiredKey'");
            }
        }

        if (isset($config['default'])) {
            $this->default = $config['default'];
        }

        if (isset($config['version'])) {
            $this->version = $config['version'];
        }

        if (isset($config['allocator']['options'])) {
            $this->allocatorOptions = $config['allocator']['options'];
        }

        foreach ($config['variants'] as $key => $value) {
            if (is_array($value)) {
                $this->variants[$key] = new Variant($key, $value);
            } else {
                $this->variants[$value] = new Variant($value);
            }
        }

        $this->events = $config['events'];
        $this->enabled = $config['enabled'];

        if (!array_key_exists($this->default, $this->variants)) {
            throw new Exception("Default variant '{$this->default}' found");
        }
    }

    public function key()
    {
        return $this->key;
    }

    public function active()
    {
        return $this->enabled;
    }

    public function version()
    {
        return $this->version;
    }

    public function variantKeys()
    {
        return array_keys($this->variants);
    }

    public function eventKeys()
    {
        return $this->events;
    }

    public function getDefault()
    {
        return $this->variants[$this->default];
    }

    public function variant($variantKey)
    {
        return $this->variants[$variantKey];
    }

    public function allocate($allocatorOverride=null)
    {
        if (isset($allocatorOverride)) {
            $allocator = $allocatorOverride;
        } else {
            $allocator = $this->allocator;
        }

        if (is_string($allocator)) {
            return $allocator;
        }
        if (is_callable($allocator)) {
            return $allocator($this, $this->allocatorOptions);
        }
        return $allocator->allocate($this, $this->allocatorOptions);
    }

    public function createParticipant($variantKey, $metadata = null)
    {
        return $this->storageAdapter->createParticipant($this->key, $variantKey, $metadata);
    }

    public function createEvent($variantKey, $eventKey, $participantId, $metadata = null)
    {
        return $this->storageAdapter->createEvent($this->key, $variantKey, $eventKey, $participantId, $metadata);
    }

    public function countParticipants($variantKey)
    {
        return $this->storageAdapter->countParticipants($this->key, $variantKey);
    }

    public function countEvents($variantKey, $eventKey)
    {
        return $this->storageAdapter->countEvents($this->key, $variantKey, $eventKey);
    }

    public function results()
    {
        if (!isset($this->results)) {
            $this->results = new Results($this);
        }
        return $this->results;
    }

    private function requiredKeys()
    {
        return array(
            'variants',
            'events',
            'allocator',
            'enabled'
        );
    }
}
