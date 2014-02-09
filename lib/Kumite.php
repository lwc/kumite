<?php

use Kumite\Exception;

class Kumite
{
    private static $instance;

    public static function __callStatic($name, $arguments)
    {
        self::assertSetup();
        return call_user_func_array(array(self::$instance, $name), $arguments);
    }

    public static function setup($configuration)
    {
        if (!isset($configuration['storageAdapter'])) {
            throw new Exception('Missing storageAdapter configuration');
        }
        if (!isset($configuration['tests'])) {
            throw new Exception('Missing tests configuration');
        }

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
        if (!isset(self::$instance)) {
            throw new Exception("Kumite::setup() needs to be called before this method");
        }
    }
}
