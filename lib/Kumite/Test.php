<?php

namespace Kumite;

class Test
{
	private $kumite;
	private $key;
	private $active;
	private $allocator;
	private $control = 'control';
	private $variants = array();

	public function __construct($key, $config)
	{
		$this->key = $key;
		$this->active = isset($config['active']) ? $config['active'] : false;

		if (!isset($config['variants']))
			throw new Exception('No variants defined in configuration');

		$this->control = $config['control'];

		foreach ($config['variants'] as $key => $value)
		{
			if (is_array($value))
				$this->variants[$key] = new Variant($key, $value);
			else
				$this->variants[$value] = new Variant($value);
		}
	}

	public function key()
	{
		return $this->key;
	}

	public function active()
	{
		return $this->active;
	}

	public function variantKeys()
	{
		return array_keys($this->variants);
	}

	public function control()
	{
		return $this->variants[$this->control];
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
}
