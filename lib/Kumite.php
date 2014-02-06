<?php

use Kumite\Exception;

class Kumite
{
    private static $instance;

    // @codeCoverageIgnoreStart
    public static function start($testKey, $metadata = null)
    {
        self::assertSetup();
        self::$instance->start($testKey, $metadata);
    }

    public static function inTest($testKey)
    {
        self::assertSetup();
        return self::$instance->inTest($testKey);
    }

    public static function participantId($testKey)
    {
        self::assertSetup();
        return self::$instance->participantId($testKey);
    }

    public static function event($testKey, $eventKey, $metadata = null)
    {
        self::assertSetup();
        self::$instance->event($testKey, $eventKey, $metadata);
    }

    public static function eventOffline($testKey, $variantKey, $eventKey, $participantId, $metadata = null)
    {
        self::assertSetup();
        self::$instance->eventOffline($testKey, $variantKey, $eventKey, $participantId, $metadata);
    }

    public static function variant($testKey)
    {
        self::assertSetup();
        return self::$instance->variant($testKey);
    }

    public static function setup($configuration)
    {
        if (!isset($configuration['storageAdapter']))
            throw new Exception('Missing storageAdapter configuration');
        if (!isset($configuration['tests']))
            throw new Exception('Missing tests configuration');

        $cookieAdapter = new Kumite\Adapters\PhpCookieAdapter();

        if (isset($configuration['cookieAdapter'])) {
            $cookieAdapter = $configuration['cookieAdapter'];
        }

        $storageAdapter = $configuration['storageAdapter'];
        $testConfig = $configuration['tests'];

        self::$instance = new Kumite\Controller($cookieAdapter, $storageAdapter, $testConfig);
    }

    private static function assertSetup()
    {
        if (!isset(self::$instance))
            throw new Exception("Kumite::setup() needs to be called before this method");
    }

    // @codeCoverageIgnoreEnd
}
