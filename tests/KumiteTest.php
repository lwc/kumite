<?php

require_once(__DIR__ . '/base.php');

class KumiteTest //extends BaseTest
{
    public function setUp()
    {
        $this->controller = Mockery::mock('overload:Kumite\Controller');
    }

    public function testConstruct()
    {
        $sa = Mockery::mock('Kumite\Adapters\StorageAdapter');
        $ca = Mockery::mock('Kumite\Adapters\CookieAdapter');

        $testConfig = $this->getTestConfig();

        $this->controller
            ->shouldReceive('__construct')
            ->with(array('myTest' => new Kumite\Test('myTest', $testConfig)), $sa, $ca)
        ;

        $k = new Kumite(array(
            'storageAdapter' => $sa,
            'cookieAdapter' => $ca,
            'tests' => array(
                'myTest' => $testConfig
            )
        ));
        $k->init();
    }

    public function testConstructCallableConfig()
    {
        $sa = Mockery::mock('Kumite\Adapters\StorageAdapter');
        $ca = Mockery::mock('Kumite\Adapters\CookieAdapter');

        $testConfig = $this->getTestConfig();

        $this->controller
            ->shouldReceive('__construct')
            ->with(array('myTest' => new Kumite\Test('myTest', $testConfig)), $sa, $ca)
        ;

        $k = new Kumite(array(
            'storageAdapter' => $sa,
            'cookieAdapter' => $ca,
            'tests' => function () use ($testConfig) {
                return array(
                    'myTest' => $testConfig
                );
            }
        ));
        $k->init();
    }

    public function testConstructDefaultCookieAdapter()
    {
        $sa = Mockery::mock('Kumite\Adapters\StorageAdapter');
        $ca = Mockery::type('Kumite\Adapters\PhpCookieAdapter');

        $testConfig = $this->getTestConfig();

        $this->controller
            ->shouldReceive('__construct')
            ->with(array('myTest' => new Kumite\Test('myTest', $testConfig)), $sa, $ca)
        ;

        $k = new Kumite(array(
            'storageAdapter' => $sa,
            'tests' => array(
                'myTest' => $testConfig
            )
        ));
        $k->init();
    }

    public function testStartTest()
    {
        $sa = Mockery::mock('Kumite\Adapters\StorageAdapter');
        $ca = Mockery::mock('Kumite\Adapters\CookieAdapter');

        $testConfig = $this->getTestConfig();

        $this->controller
            ->shouldReceive('startTest')
            ->with('myTest', 'blarg')
            ->once();

        $k = new Kumite(array(
            'storageAdapter' => $sa,
            'cookieAdapter' => $ca,
            'tests' => array(
                'myTest' => $testConfig
            )
        ));
        $k->startTest('myTest', 'blarg');
    }

    public function testAddEvent()
    {
        $sa = Mockery::mock('Kumite\Adapters\StorageAdapter');
        $ca = Mockery::mock('Kumite\Adapters\CookieAdapter');

        $testConfig = $this->getTestConfig();

        $this->controller
            ->shouldReceive('addEvent')
            ->with('myTest', 'sale', null)
            ->once();

        $k = new Kumite(array(
            'storageAdapter' => $sa,
            'cookieAdapter' => $ca,
            'tests' => array(
                'myTest' => $testConfig
            )
        ));
        $k->addEvent('myTest', 'sale', null);
    }

    private function getTestConfig()
    {
        return array(
            'start' => '2012-01-01',
            'end' => '2012-02-01',
            'default' => 'control',
            'variants' => array(
                'control',
                'austvideo' => array('listid' => '7ae4be2')
            )
        );
    }
}