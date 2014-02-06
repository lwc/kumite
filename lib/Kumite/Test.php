<?php

namespace Kumite;

class Test
{

    private $storageAdapter;
    private $allocator;
    private $key;
    private $enabled;
    private $default = 'control';
    private $version;
    private $variants = array();
    private $events;
    private $results;

    public function __construct($key, $config, $storageAdapter)
    {
        $this->storageAdapter = $storageAdapter;
        $this->key = $key;

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

        if (!$config['allocator'] instanceof Allocator && !is_callable($config['allocator']) ) {
            throw new Exception('Allocator must be callable, instance of Kumite\\Allocator');
        }
        $this->allocator = $config['allocator'];

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

    public function allocate()
    {
        $allocator = $this->allocator;
        if (is_callable($allocator)) {
            return $allocator($this);
        }
        return $allocator->allocate($this);
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
