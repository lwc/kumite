<?php

namespace Kumite;

class Test
{

    private $storageAdapter;
    private $allocator;
    private $key;
    private $start;
    private $end;
    private $enabled;
    private $default = 'control';
    private $variants = array();

    public function __construct($key, $config, $storageAdapter)
    {
        $this->storageAdapter = $storageAdapter;
        $this->key = $key;

        foreach ($this->requiredKeys() as $requiredKey) {
            if (!array_key_exists($requiredKey, $config))
                throw new Exception("Test '$key' missing config key '$requiredKey'");
        }

        if (isset($config['enabled'])) {
            $this->enabled = $config['enabled'];
        }
        else if (isset($config['start']) && isset($config['end'])) {
            $this->start = strtotime($config['start']);
            $this->end = strtotime($config['end']);
        }
        else {
            throw new Exception('Either enabled or start & end must be defined');
        }

        if (isset($config['default'])) {
            $this->default = $config['default'];
        }

        if (!$config['allocator'] instanceof Allocator && !is_callable($config['allocator']) ) {
            throw new Exception('Allocator must be callable, instance of Kumite\\Allocator');
        }
        $this->allocator = $config['allocator'];

        foreach ($config['variants'] as $key => $value) {
            if (is_array($value))
                $this->variants[$key] = new Variant($key, $value);
            else
                $this->variants[$value] = new Variant($value);
        }

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
        if (isset($this->enabled)) {
            return $this->enabled;
        }
        return (($this->start < \Kumite::now()) && ($this->end > \Kumite::now()));
    }

    public function variantKeys()
    {
        return array_keys($this->variants);
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

    private function requiredKeys()
    {
        return array(
            'variants',
            'allocator'
        );
    }
}
