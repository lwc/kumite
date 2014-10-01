<?php

namespace Kumite;

class Controller
{
    const COOKIE_PREFIX = 'kumite__';
    const IMITATE_COOKIE_PREFIX = 'imitate__kumite__';

    private $cookieAdapter;
    private $storageAdapter;
    private $testConfig;
    private $allocators = array();
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
     * Sets a cookie that imitates being in a variant in a test.
     *
     * Only effects calls to variant. The methods inTest and participantId are
     * unaware of the imitation.
     *
     * @param string $testKey
     * @param string $variantKey
     */
    public function imitate($testKey, $variantKey)
    {
        $this->cookieAdapter->setCookie(self::IMITATE_COOKIE_PREFIX . $testKey, $variantKey);
    }

    /**
     * Removes the imitation cookie.
     * @param string $testKey
     */
    public function stopImitating($testKey)
    {
        $this->cookieAdapter->setCookie(self::IMITATE_COOKIE_PREFIX . $testKey, null);
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
        if ($cookie) {
            return $test->variant($cookie['variant']);
        }
        if ($imitationVariant = $this->imitationVariant($testKey)) {
            return $imitationVariant;
        }
        return $test->getDefault();
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
            $testConfig = $this->testConfig[$testKey];
            $allocator = $this->getAllocator($testConfig['allocator']['method']);
            $this->tests[$testKey] = new Test($testKey, $testConfig, $allocator, $this->storageAdapter);
        }
        return $this->tests[$testKey];
    }

    public function addAllocator($allocatorId, $allocator)
    {
        $this->allocators[$allocatorId] = $allocator;
    }

    /**
     * Serialise the state of all active tests for the current request
     *
     * @return array state of active tests, indexed by test key
     */
    public function freeze()
    {
        $state = $this->unsavedCookies;
        foreach ($this->cookieAdapter->getCookies() as $key => $value) {
            if (strpos($key, self::COOKIE_PREFIX) === 0) {
                $keyParts = explode('__', $key);
                $testKey = $keyParts[1];
                try {
                    $test = $this->getTest($testKey);
                    $cookie = $this->getCookie($test);
                    if ($cookie) {
                        $state[$testKey] = $cookie;
                    }
                } catch (\Exception $e) {
                }
            }
        }
        return $state;
    }

    /**
     * Takes an array of test state and reloads it into the current request.
     * @see Controller::freeze()
     *
     * @param type $activeTests
     */
    public function thaw($activeTests)
    {
        if (!is_array($activeTests)) {
            return;
        }
        foreach ($activeTests as $testKey => $testData) {

            if (isset($testData['variant']) && isset($testData['pid'])) {
                $this->resume($testKey, $testData['variant'], $testData['pid']);
            }
        }
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
        $version = $test->version();
        $version = isset($version) ? '__' . $version : '';
        return self::COOKIE_PREFIX . $test->key() . $version;
    }

    private function imitationVariant($testKey)
    {
        return $this->cookieAdapter->getCookie(self::IMITATE_COOKIE_PREFIX . $testKey);
    }

    private function getAllocator($allocatorId)
    {
        if (!isset($this->allocators[$allocatorId])) {
            throw new Exception("'$allocatorId' not a registered allocator");
        }
        return $this->allocators[$allocatorId];
    }
}
