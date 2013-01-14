<?php 

require_once(__DIR__.'/base.php');

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
			->with('myTest', 'austvideo')
			->andReturn(100);

		$this->cookieAdapter
			->shouldReceive('setCookie')
			->with('kumite__myTest', json_encode(array(
				'variant' => 'austvideo',
				'pid' => 100
			)))
			->once();

		$c = $this->createController();
		$c->startTest('myTest', function($variants) {
			return 'austvideo';
		});
	}

	public function testStartTestNoCookieInactive()
	{
		$this->expectGetCookieNull();
		$c = $this->createController(array('active'=>false));

		$c->startTest('myTest', function($variants) {
			return 'austvideo';
		});
	}

	public function testStartTestCookie()
	{
		$this->expectGetCookie();
		$c = $this->createController();

		$c->startTest('myTest', function($variants) {
			return 'austvideo';
		});
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

		$this->storageAdapter
			->shouldReceive('createEvent')
			->with('myTest', 'austvideo', 'sale', 100, array('amount'=>300))
			->once();

		$c = $this->createController();
		$c->addEvent('myTest', 'sale', array('amount'=>300));
	}

	public function testAddEventNoCookie()
	{
		$this->expectGetCookieNull();
		$c = $this->createController();
		$c->addEvent('myTest', 'sale', array('amount'=>300));
	}

	private function expectGetCookieNull()
	{
		$this->cookieAdapter
			->shouldReceive('getCookie')
			->once()
			->with('kumite__myTest')
			->andReturn(null);
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
			)));
	}

	private function createController($options=array())
	{
		$this->test = new Kumite\Test('myTest', array_merge(array(
				'active' => true,
				'control' => 'control',
				'variants' => array(
					'control',
					'austvideo' => array('listid' => '7ae4be2')
				)
			), $options)
		);
		return new Kumite\Controller(array(
			'myTest' => $this->test
		), $this->storageAdapter, $this->cookieAdapter);
	}
}