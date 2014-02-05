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
        $c->startTest('myTest');
        return $c;
    }

    public function testStartTestNoCookieInactive()
    {
        $c = $this->createController(array(
            'start' => '2012-06-01',
            'end' => '2012-06-01',
        ));

        $c->startTest('myTest');
    }

    public function testStartTestCookie()
    {
        $this->expectGetCookie();
        $c = $this->createController();

        $c->startTest('myTest');
    }

    public function testNotInTest()
    {
        $this->expectGetCookieNull();
        $c = $this->createController();
        $this->assertEquals($c->isInTest('myTest'), false);
    }

    public function testIsInTest()
    {
        $this->expectGetCookie();
        $c = $this->createController();
        $this->assertEquals($c->isInTest('myTest'), true);
    }

    public function testGetActiveVariantCookie()
    {
        $this->expectGetCookie();
        $c = $this->createController();
        $this->assertEquals($c->getActiveVariant('myTest')->key(), 'austvideo');
    }

    public function testGetActiveVariantNoCookie()
    {
        $this->expectGetCookieNull();
        $c = $this->createController();
        $this->assertEquals($c->getActiveVariant('myTest')->key(), 'control');
    }

    public function testAddEventCookie()
    {
        $this->expectGetCookie();
        $this->expectEventStorage();

        $c = $this->createController();
        $c->addEvent('myTest', 'sale', array('amount' => 300));
    }

    public function testAddEventNoCookie()
    {
        $this->expectGetCookieNull();
        $c = $this->createController();
        $c->addEvent('myTest', 'sale', array('amount' => 300));
    }

    public function testAddEventOffline()
    {
        $this->expectEventStorage();
        $c = $this->createController();
        $c->addEventOffline('myTest', 'austvideo', 'sale', 100, array('amount' => 300));
    }

    public function testStartStarted()
    {
        $c = $this->testStartTestNoCookie();

        // should do nothing
        $c->startTest('myTest');
    }

    public function testGetActiveVariantStarted()
    {
        $c = $this->testStartTestNoCookie();
        $this->assertEquals($c->getActiveVariant('myTest')->key(), 'austvideo');
    }

    public function testAddEventStarted()
    {
        $c = $this->testStartTestNoCookie();
        $this->expectEventStorage();
        $c->addEvent('myTest', 'sale', array('amount' => 300));
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
        $this->test = new Kumite\Test('myTest', array_merge(array(
                'start' => '2012-01-01',
                'end' => '2012-02-01',
                'allocator' => function($test) {
                    return 'austvideo';
                },
                'default' => 'control',
                'variants' => array(
                    'control',
                    'austvideo' => array('listid' => '7ae4be2')
                ),
                'events' => array(
                    'sale'
                )
            ), $options), $this->storageAdapter
        );
        return new Kumite\Controller(array(
            'myTest' => $this->test
            ), $this->cookieAdapter);
    }
}