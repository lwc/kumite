<?php 

require_once(__DIR__.'/base.php');

class VariantTest extends BaseTest
{
	public function testWithoutProperties()
	{
		$v = new Kumite\Variant('control');
		$this->assertEquals($v->key(), 'control');
		$this->assertEquals($v->properties(), array());
		$this->assertEquals($v->property('showTitle', true), true);
	}

	public function testWithProperties()
	{
		$v = new Kumite\Variant('variant1', array('listId'=>'a6ec45a'));
		$this->assertEquals($v->key(), 'variant1');
		$this->assertEquals($v->properties(), array('listId'=>'a6ec45a'));
		$this->assertEquals($v->property('listId', 'bfb8e4f'), 'a6ec45a');
		$this->assertEquals($v->property('name', 'Luke'), 'Luke');		
	}
}