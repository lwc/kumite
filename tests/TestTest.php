<?php 

require_once(__DIR__.'/base.php');

class TestTest extends BaseTest
{
	public function testBasic()
	{
		$t = $this->createTest();
		$this->assertEquals($t->key(), 'videotest');
		$this->assertEquals($t->active(), true);
		$this->assertEquals($t->control()->key(), 'control');
		$this->assertEquals($t->variantKeys(), array('control', 'austvideo'));
		$this->assertEquals($t->variant('austvideo')->key(), 'austvideo');
		$this->assertEquals($t->variant('austvideo')->property('listid'), '7ae4be2');
	}	

	public function testAllocateCallable()
	{
		$t = $this->createTest();
		$variant = $t->choose(function($variants) {
			return $variants[0];
		});
		$this->assertEquals($variant, 'control');		
	}

	public function testAllocateAllocator()
	{
		$t = $this->createTest();
		$allocator = Mockery::mock('Kumite\\Allocator');
		$allocator->shouldReceive('allocate')->andReturn('austvideo');

		$variant = $t->choose($allocator);
		$this->assertEquals($variant, 'austvideo');
	}

	public function testAllocateConstant()
	{
		$t = $this->createTest();
		$variant = $t->choose('austvideo');
		$this->assertEquals($variant, 'austvideo');
	}

	public function testAllocateError()
	{
		$t = $this->createTest();
		try
		{
			$t->choose('this isnt an allocator');
			$this->fail('Expected exception');
		}
		catch (Kumite\Exception $e)
		{
		}
	}

	private function createTest()
	{
		return new Kumite\Test('videotest', array(
			'active' => true,
			'control' => 'control',
			'variants' => array(
				'control',
				'austvideo' => array('listid' => '7ae4be2')
			)
		));

	}
}