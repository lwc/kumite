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
        if (!$this->getCookie($testKey)) {
            $test = $this->getTest($testKey);
            if (!$test->active())
                return;
            $variantKey = $test->allocate();
            if ($variantKey) {
                $participantId = $test->createParticipant($variantKey, $metadata);
                $this->setCookie($testKey, $variantKey, $participantId);
            }
        }
    }

    public function getParticipantId($testKey)
    {
        $cookie = $this->getCookie($testKey);
        if ($cookie) {
            return $cookie['pid'];
        }
    }

    public function isInTest($testKey)
    {
        return (bool) $this->getCookie($testKey);
    }

    public function getActiveVariant($testKey)
    {
        $cookie = $this->getCookie($testKey);
        if (!$cookie)
            return $this->getTest($testKey)->getDefault();
        return $this->getTest($testKey)->variant($cookie['variant']);
    }

    public function addEventOffline($testKey, $variantKey, $eventKey, $participantId, $metadata)
    {
        $test = $this->getTest($testKey);
        $test->createEvent($variantKey, $eventKey, $participantId, $metadata);
    }

    public function addEvent($testKey, $eventKey, $metadata = null)
    {
        $cookie = $this->getCookie($testKey);
        if ($cookie) {
            $test = $this->getTest($testKey);
            $test->createEvent($cookie['variant'], $eventKey, $cookie['pid'], $metadata);
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
        return self::COOKIE_PREFIX . $testKey;
    }

    public function getTests()
    {
        return $this->tests;
    }

    public function getTest($testKey)
    {
        return $this->tests[$testKey];
    }
}
