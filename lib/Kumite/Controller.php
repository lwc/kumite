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

    /**
     * Attempts to start a test
     *
     * If the allocator returns a variant, a participant is created and a cookie
     * is set in the response.
     *
     * If no variant is returned, the request is considered excluded from the test.
     * Once a request is participating, further calls to start do nothing.
     *
     * The allocator may be overridden for special case tests, such as where
     * allocation is happening in an external system, or for testcases.
     *
     * @param string $testKey the key of the defined test to start
     * @param array $metadata optional metadata to attach to the participant
     * @param mixed $allocatorOverride an allocator, or variant key to override the configured allocator
     */
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

    /**
     * Resume a test by overriding any set cookie for that test.
     *
     * Useful for persisting tests across devices / via email
     *
     * @param string $testKey
     * @param string $variantKey
     * @param string $participantId
     */
    public function resume($testKey, $variantKey, $participantId)
    {
        $test = $this->getTest($testKey);
        $this->setCookie($test, $variantKey, $participantId);
    }

    /**
     * Return the participant identifier for the test.
     *
     * @param string $testKey
     * @return string
     */
    public function participantId($testKey)
    {
        $test = $this->getTest($testKey);
        $cookie = $this->getCookie($test);
        if ($cookie) {
            return $cookie['pid'];
        }
    }

    /**
     * Returns true if the request is participating in the test.
     *
     * @param string $testKey
     * @return bool
     */
    public function inTest($testKey)
    {
        $test = $this->getTest($testKey);
        return (bool) $this->getCookie($test);
    }

    /**
     * Returns the active variant for the provided test.
     *
     * If not participating in the test, the default variant will be returned.
     * To test for actual participation, use the above inTest method.
     *
     * @param string $testKey
     * @return Variant
     */
    public function variant($testKey)
    {
        $test = $this->getTest($testKey);
        $cookie = $this->getCookie($test);
        if (!$cookie) {
            return $test->getDefault();
        }
        return $test->variant($cookie['variant']);
    }

    /**
     * Tracks an event outside of request context.
     *
     * @param string $testKey
     * @param string $variantKey
     * @param string $eventKey
     * @param string $participantId
     * @param array $metadata
     */
    public function eventOffline($testKey, $variantKey, $eventKey, $participantId, $metadata = null)
    {
        $test = $this->getTest($testKey);
        $test->createEvent($variantKey, $eventKey, $participantId, $metadata);
    }

    /**
     * Tracks an event for participants in the test.
     *
     * Does nothing if the request is not participating in the test.
     *
     * @param string $testKey
     * @param string $eventKey
     * @param array $metadata
     */
    public function event($testKey, $eventKey, $metadata = null)
    {
        $test = $this->getTest($testKey);
        $cookie = $this->getCookie($test);
        if ($cookie) {
            $test->createEvent($cookie['variant'], $eventKey, $cookie['pid'], $metadata);
        }
    }

    /**
     * Returns all defined tests.
     *
     * @return array
     */
    public function getTests()
    {
        foreach (array_keys($this->testConfig) as $testKey) {
            $this->getTest($testKey);
        }
        return $this->tests;
    }

    /**
     * Returns the Test object for the given test key.
     *
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
