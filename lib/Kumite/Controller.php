<?php

namespace Kumite;

class Controller
{
    const COOKIE_PREFIX = 'kumite__';
    const OVERRIDE_COOKIE_PREFIX = 'kumite__override__';

    private $cookieAdapter;
    private $storageAdapter;
    private $testConfig;

    private $tests = array();
    private $unsavedCookies = array();


    public function __construct($cookieAdapter, $storageAdapter, $testConfig)
    {
        $this->cookieAdapter = $cookieAdapter;
        $this->storageAdapter = $storageAdapter;
        $this->testConfig = $testConfig;
    }

    public function start($testKey, $metadata = null, $allocatorOverride = null)
    {
        $test = $this->getTest($testKey);
        if (!$test->active()) {
            return;
        }

        if (!$this->getCookie($test)) {
            $variantKey = $test->allocate($allocatorOverride);
            if ($variantKey) {
                $participantId = $test->createParticipant($variantKey, $metadata);
                $this->setCookie($test, $variantKey, $participantId);
            }
        }
    }

    public function resume($testKey, $variantKey, $participantId)
    {
        $test = $this->getTest($testKey);
        $this->setCookie($test, $variantKey, $participantId);
    }

    public function participantId($testKey)
    {
        $test = $this->getTest($testKey);
        $cookie = $this->getCookie($test);
        if ($cookie) {
            return $cookie['pid'];
        }
    }

    public function inTest($testKey)
    {
        $test = $this->getTest($testKey);
        return (bool) $this->getCookie($test);
    }

    public function variant($testKey)
    {
        $test = $this->getTest($testKey);
        $cookie = $this->getCookie($test);
        if (!$cookie) {
            return $test->getDefault();
        }
        return $test->variant($cookie['variant']);
    }

    public function eventOffline($testKey, $variantKey, $eventKey, $participantId, $metadata)
    {
        $test = $this->getTest($testKey);
        $test->createEvent($variantKey, $eventKey, $participantId, $metadata);
    }

    public function event($testKey, $eventKey, $metadata = null)
    {
        $test = $this->getTest($testKey);
        $cookie = $this->getCookie($test);
        if ($cookie) {
            $test->createEvent($cookie['variant'], $eventKey, $cookie['pid'], $metadata);
        }
    }

    public function getTests()
    {
        foreach (array_keys($this->testConfig) as $testKey) {
            $this->getTest($testKey);
        }
        return $this->tests;
    }

    /**
     * @param type $testKey
     * @return Test
     */
    public function getTest($testKey)
    {
        if (!isset($this->tests[$testKey])) {
            if (!isset($this->testConfig[$testKey])) {
                throw new Exception("Missing test configuration for key '$testKey'");
            }
            $this->tests[$testKey] = new Test($testKey, $this->testConfig[$testKey], $this->storageAdapter);
        }
        return $this->tests[$testKey];
    }

    private function setCookie(Test $test, $variantKey, $participantId)
    {
        $cookieData = array(
            'variant' => $variantKey,
            'pid' => $participantId
        );
        $this->unsavedCookies[$test->key()] = $cookieData;
        $this->cookieAdapter->setCookie($this->cookieName($test), json_encode($cookieData));
    }

    private function getCookie(Test $test)
    {
        if (isset($this->unsavedCookies[$test->key()])) {
            return $this->unsavedCookies[$test->key()];
        }
        return json_decode($this->cookieAdapter->getCookie($this->cookieName($test)), true);
    }

    private function cookieName(Test $test)
    {
        return self::COOKIE_PREFIX . $test->key() . $test->version();
    }
}
