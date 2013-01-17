<?php

namespace Kumite;

class Controller
{
	const COOKIE_PREFIX = 'kumite__';

	private $cookieAdapter;
	private $storageAdapter;

	private $tests = array();
	private $unsavedCookies = array();

	public function __construct($tests, $storageAdapter, $cookieAdapter)
	{
		$this->tests = $tests;
		$this->cookieAdapter = $cookieAdapter;
		$this->storageAdapter = $storageAdapter;
	}

	public function startTest($testKey, $allocator)
	{
		if (!$this->getCookie($testKey))
		{
			$test = $this->getTest($testKey);
			if (!$test->active())
				return;
			$variantKey = $test->choose($allocator);
			if ($variantKey)
			{
				$participantId = $this->storageAdapter->createParticipant($testKey, $variantKey);
				$this->setCookie($testKey, $variantKey, $participantId);
			}
		}
	}

	public function getActiveVariant($testKey)
	{
		$cookie = $this->getCookie($testKey);
		if (!$cookie)
			return $this->getTest($testKey)->getDefault();
		return $this->getTest($testKey)->variant($cookie['variant']);
	}

	public function addEvent($testKey, $eventKey, $metadata=null)
	{
		$cookie = $this->getCookie($testKey);
		if ($cookie)
		{
			$this->storageAdapter->createEvent($testKey, $cookie['variant'], $eventKey, $cookie['pid'], $metadata);
		}
	}

	private function setCookie($testKey, $variantKey, $participantId)
	{
		$cookieData = array(
			'variant' => $variantKey,
			'pid' => $participantId
		);
		$this->unsavedCookies[$testKey] = $cookieData;
		$this->cookieAdapter->setCookie($this->cookieName($testKey), json_encode($cookieData));
	}

	private function getCookie($testKey)
	{
		if (isset($this->unsavedCookies[$testKey]))
			return $this->unsavedCookies[$testKey];
		return json_decode($this->cookieAdapter->getCookie($this->cookieName($testKey)), true);
	}

	private function cookieName($testKey)
	{
		return self::COOKIE_PREFIX.$testKey;
	}

	public function getTest($testKey)
	{
		return $this->tests[$testKey];
	}	
}