<?php

require_once(__DIR__ . '/base.php');

class ControllerTest extends BaseTest
{
    public function setUp()
    {
        $this->cookieAdapter = Mockery::mock('Kumite\\Adapters\\CookieAdapter');
        $this->storageAdapter = Mockery::mock('Kumite\\Adapters\\StorageAdapter');
    }

    public function testStartTestNoCookie()
    {
        $this->expectGetCookieNull();

        $this->storageAdapter
            ->shouldReceive('createParticipant')
            ->once()
            ->with('myTest', 'austvideo', null)
            ->andReturn(100)
            ->globally()
            ->ordered()
        ;

        $this->cookieAdapter
            ->shouldReceive('setCookie')
            ->with('kumite__myTest', json_encode(array(
                'variant' => 'austvideo',
                'pid' => 100
            )))
            ->once()
            ->globally()
            ->ordered()
        ;

        $c = $this->createController();
        $c->start('myTest');
        return $c;
    }

    public function testStartTestNoCookieInactive()
    {
        $c = $this->createController(array(
            'enabled' => false,
        ));

        $c->start('myTest');
    }

    public function testStartTestCookie()
    {
        $this->expectGetCookie();
        $c = $this->createController();

        $c->start('myTest');
    }

    public function testNotInTest()
    {
        $this->expectGetCookieNull();
        $c = $this->createController();
        $this->assertEquals($c->inTest('myTest'), false);
    }

    public function testIsInTest()
    {
        $this->expectGetCookie();
        $c = $this->createController();
        $this->assertEquals($c->inTest('myTest'), true);
    }

    public function testGetActiveVariantCookie()
    {
        $this->expectGetCookie();
        $c = $this->createController();
        $this->assertEquals($c->variant('myTest')->key(), 'austvideo');
    }

    public function testGetActiveVariantNoCookie()
    {
        $this->expectGetCookieNull();
        $this->cookieAdapter
            ->shouldReceive('getCookie')
            ->once()
            ->with('imitate__kumite__myTest')
            ->andReturn(null)
            ->globally()
            ->ordered()
        ;
        $c = $this->createController();
        $this->assertEquals($c->variant('myTest')->key(), 'control');
    }

    public function testGetActiveVariantImitating()
    {
        $this->expectImitateCookie();
        $c = $this->createController();
        $this->assertEquals($c->variant('myTest'), 'newVariant');
    }

    public function testAddEventCookie()
    {
        $this->expectGetCookie();
        $this->expectEventStorage();

        $c = $this->createController();
        $c->event('myTest', 'sale', array('amount' => 300));
    }

    public function testAddEventNoCookie()
    {
        $this->expectGetCookieNull();
        $c = $this->createController();
        $c->event('myTest', 'sale', array('amount' => 300));
    }

    public function testAddEventOffline()
    {
        $this->expectEventStorage();
        $c = $this->createController();
        $c->eventOffline('myTest', 'austvideo', 'sale', 100, array('amount' => 300));
    }

    public function testStartStarted()
    {
        $c = $this->testStartTestNoCookie();

        // should do nothing
        $c->start('myTest');
    }

    public function testGetActiveVariantStarted()
    {
        $c = $this->testStartTestNoCookie();
        $this->assertEquals($c->variant('myTest')->key(), 'austvideo');
    }

    public function testAddEventStarted()
    {
        $c = $this->testStartTestNoCookie();
        $this->expectEventStorage();
        $c->event('myTest', 'sale', array('amount' => 300));
    }

    private function expectGetCookieNull()
    {
        $this->cookieAdapter
            ->shouldReceive('getCookie')
            ->once()
            ->with('kumite__myTest')
            ->andReturn(null)
            ->globally()
            ->ordered()
        ;
    }

    private function expectGetCookie()
    {
        $this->cookieAdapter
            ->shouldReceive('getCookie')
            ->once()
            ->with('kumite__myTest')
            ->andReturn(json_encode(array(
                'variant' => 'austvideo',
                'pid' => 100
            )))
            ->globally()
            ->ordered()
        ;
    }

    private function expectImitateCookie()
    {
        $this->cookieAdapter
            ->shouldReceive('getCookie')
            ->once()
            ->with('kumite__myTest')
            ->andReturn(null)
            ->globally()
            ->ordered()
        ;
        $this->cookieAdapter
            ->shouldReceive('getCookie')
            ->once()
            ->with('imitate__kumite__myTest')
            ->andReturn('newVariant')
            ->globally()
            ->ordered()
        ;
    }

    private function expectEventStorage()
    {
        $this->storageAdapter
            ->shouldReceive('createEvent')
            ->with('myTest', 'austvideo', 'sale', 100, array('amount' => 300))
            ->once()
            ->globally()
            ->ordered()
        ;
    }

    private function createController($options = array())
    {
        $testConfig = array_merge(array(
            'enabled' => true,
            'allocator' => array(
                'options' => array(),
                'method' => 'my-allocator'
            ),
            'default' => 'control',
            'variants' => array(
                'control',
                'austvideo' => array('listid' => '7ae4be2')
            ),
            'events' => array(
                'sale'
            )
        ), $options);

        $controller = new Kumite\Controller($this->cookieAdapter, $this->storageAdapter, array(
            'myTest' => $testConfig
        ));
        $controller->addAllocator('my-allocator', function($test) {
            return 'austvideo';
        });
        return $controller;
    }
}