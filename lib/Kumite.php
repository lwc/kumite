<?php

use Kumite\Exception;

class Kumite
{
    private static $instance;
    private static $now;
    private $controller;
    private $config;
    private $cookieAdapter;
    private $storageAdapter;

    // @codeCoverageIgnoreStart
    public static function start($testKey, $allocator)
    {
        self::assertSetup();
        self::$instance->startTest($testKey, $allocator);
    }

    public static function inTest($testKey)
    {
        self::assertSetup();
        return self::$instance->isInTest($testKey);
    }

    public static function participantId($testKey)
    {
        self::assertSetup();
        return self::$instance->getParticipantId($testKey);
    }

    public static function event($testKey, $eventKey, $metadata = null)
    {
        self::assertSetup();
        self::$instance->addEvent($testKey, $eventKey, $metadata);
    }

    public static function eventOffline($testKey, $variantKey, $eventKey, $participantId, $metadata = null)
    {
        self::assertSetup();
        self::$instance->addEventOffline($testKey, $variantKey, $eventKey, $participantId, $metadata);
    }

    public static function variant($testKey)
    {
        self::assertSetup();
        return self::$instance->getActiveVariant($testKey);
    }

    public static function setup($configuration)
    {
        self::$instance = new Kumite($configuration);
    }

    public static function now($now = null)
    {
        if ($now)
            self::$now = $now;

        if (self::$now)
            return self::$now;

        return time();
    }

    private static function assertSetup()
    {
        if (!isset(self::$instance))
            throw new Exception("Kumite::setup() needs to be called before this method");
    }

    // @codeCoverageIgnoreEnd

    public function __construct($configuration)
    {
        if (!isset($configuration['storageAdapter']))
            throw new Exception('Missing storageAdapter configuration');
        if (!isset($configuration['tests']))
            throw new Exception('Missing tests configuration');

        if (isset($configuration['cookieAdapter']))
            $this->cookieAdapter = $configuration['cookieAdapter'];
        else
            $this->cookieAdapter = new Kumite\Adapters\PhpCookieAdapter();

        $this->storageAdapter = $configuration['storageAdapter'];
        $this->config = $configuration['tests'];
    }

    public function startTest($testKey, $allocator)
    {
        $this->init();
        $this->controller->startTest($testKey, $allocator);
    }

    public function isInTest($testKey)
    {
        $this->init();
        return $this->controller->isInTest($testKey);
    }

    public function getParticipantId($testKey)
    {
        $this->init();
        return $this->controller->getParticipantId($testKey);
    }

    public function addEventOffline($testKey, $variantKey, $eventKey, $participantId, $metadata)
    {
        $this->init();
        $this->controller->addEventOffline($testKey, $variantKey, $eventKey, $participantId, $metadata);
    }

    public function addEvent($testKey, $eventKey, $metadata)
    {
        $this->init();
        $this->controller->addEvent($testKey, $eventKey, $metadata);
    }

    public function getActiveVariant($testKey)
    {
        $this->init();
        return $this->controller->getActiveVariant($testKey);
    }

    public function init()
    {
        if (!isset($this->controller)) {
            $config = $this->config;
            if (is_callable($config)) {
                $config = $config();
            }
            $tests = array();
            foreach ($config as $testKey => $config) {
                $tests[$testKey] = new Kumite\Test($testKey, $config);
            }
            $this->controller = new Kumite\Controller($tests, $this->storageAdapter, $this->cookieAdapter);
        }
    }
}
