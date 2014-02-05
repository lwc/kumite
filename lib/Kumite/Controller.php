<?php

namespace Kumite;

class Controller
{
    const COOKIE_PREFIX = 'kumite__';

    private $cookieAdapter;
    private $tests = array();
    private $unsavedCookies = array();

    public function __construct($tests, $cookieAdapter)
    {
        $this->tests = $tests;
        $this->cookieAdapter = $cookieAdapter;
    }

    public function startTest($testKey, $metadata = null)
    {
        $test = $this->getTest($testKey);
        if (!$test->active()) {
            return;
        }

        if (!$this->getCookie($test)) {
            $variantKey = $test->allocate();
            if ($variantKey) {
                $participantId = $test->createParticipant($variantKey, $metadata);
                $this->setCookie($test, $variantKey, $participantId);
            }
        }
    }

    public function getParticipantId($testKey)
    {
        $test = $this->getTest($testKey);
        $cookie = $this->getCookie($test);
        if ($cookie) {
            return $cookie['pid'];
        }
    }

    public function isInTest($testKey)
    {
        $test = $this->getTest($testKey);
        return (bool) $this->getCookie($test);
    }

    public function getActiveVariant($testKey)
    {
        $test = $this->getTest($testKey);
        $cookie = $this->getCookie($test);
        if (!$cookie)
            return $test->getDefault();
        return $test->variant($cookie['variant']);
    }

    public function addEventOffline($testKey, $variantKey, $eventKey, $participantId, $metadata)
    {
        $test = $this->getTest($testKey);
        $test->createEvent($variantKey, $eventKey, $participantId, $metadata);
    }

    public function addEvent($testKey, $eventKey, $metadata = null)
    {
        $test = $this->getTest($testKey);
        $cookie = $this->getCookie($test);
        if ($cookie) {
            $test->createEvent($cookie['variant'], $eventKey, $cookie['pid'], $metadata);
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
        return self::COOKIE_PREFIX . $test->key() . $test->version();
    }

    public function getTests()
    {
        return $this->tests;
    }

    /**
     * @param type $testKey
     * @return Test
     */
    public function getTest($testKey)
    {
        return $this->tests[$testKey];
    }
}
