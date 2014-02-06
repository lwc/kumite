<?php

require_once(__DIR__.'/../vendor/autoload.php');

date_default_timezone_set('Australia/Melbourne');

class BaseTest extends PHPUnit_Framework_TestCase
{
	public function teardown()
	{
	    \Mockery::close();
	}
}