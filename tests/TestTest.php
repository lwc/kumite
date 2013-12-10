<?php

require_once(__DIR__ . '/base.php');

class TestTest extends BaseTest
{
    public function testBasic()
    {
        $t = $this->createTest();
        $this->assertEquals($t->key(), 'videotest');
        $this->assertEquals($t->active(), true);
        $this->assertEquals($t->getDefault()->key(), 'control');
        $this->assertEquals($t->variantKeys(), array('control', 'austvideo'));
        $this->assertEquals($t->variant('austvideo')->key(), 'austvideo');
        $this->assertEquals($t->variant('austvideo')->property('listid'), '7ae4be2');
    }

    public function testEnabled()
    {
        $test = $this->createTest(array(
            'enabled' => true
        ));
        $this->assertEquals($test->active(), true);

        $test = $this->createTest(array(
            'enabled' => false
        ));
        $this->assertEquals($test->active(), false);
    }

    public function testMissingEnabledOrDateRange()
    {
        try {
            new Kumite\Test('videotest', array(
                'variants' => array(
                    'control',
                    'austvideo' => array('listid' => '7ae4be2')
                )
            ));
            $this->fail('Expected exception');
        }
        catch (Kumite\Exception $e) {

        }
    }

    public function testMissingDefaultVariant()
    {
        try {
            new Kumite\Test('videotest', array(
                'enabled' => true,
                'variants' => array(
                    'meow',
                    'austvideo' => array('listid' => '7ae4be2'),
                )
            ));
            $this->fail('Expected exception');
        }
        catch (Kumite\Exception $e) {

        }
    }

    private function createTest($data = array())
    {
        $storageAdapter = Mockery::mock('Kumite\\Adapters\\StorageAdapter');

        return new Kumite\Test('videotest', array_merge(array(
                'start' => '2012-01-01',
                'end' => '2012-02-01',
                'default' => 'control',
                'allocator' => function($test) {
                    return 'austvideo';
                },
                'variants' => array(
                    'control',
                    'austvideo' => array('listid' => '7ae4be2')
                )
                ), $data), $storageAdapter);
    }
}