<?php

namespace Kumite;

class Test
{
	private $key;
	private $start;
	private $end;
	private $default = 'control';
	private $variants = array();

	public function __construct($key, $config)
	{
		$this->key = $key;

		foreach ($this->requiredKeys() as $requiredKey)
		{
			if (!array_key_exists($requiredKey, $config))
				throw new Exception("Test '$key' missing config key '$requiredKey'");
		}
		$this->start = strtotime($config['start']);
		$this->end = strtotime($config['end']);

		if (isset($config['default']))
			$this->default = $config['default'];

		foreach ($config['variants'] as $key => $value)
		{
			if (is_array($value))
				$this->variants[$key] = new Variant($key, $value);
			else
				$this->variants[$value] = new Variant($value);
		}

		if (!array_key_exists($this->default, $this->variants))
			throw new Exception("Default variant '{$this->default}' found");
	}

	public function key()
	{
		return $this->key;
	}

	public function active()
	{
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

	public function choose($allocator)
	{
		if ($allocator instanceof Allocator)
			return $allocator->allocate($this->variantKeys());
		if (is_callable($allocator))
			return $allocator($this->variantKeys());
		if (in_array($allocator, $this->variantKeys()))
			return $allocator;
		throw new Exception('Allocator must be callable, instance of Kumite\\Allocator or a variant key');
	}

	private function requiredKeys()
	{
		return array(
			'variants',
			'start',
			'end'
		);
	}
}
