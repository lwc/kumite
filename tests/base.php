<?php

require_once(__DIR__.'/../vendor/autoload.php');

date_default_timezone_set('Australia/Melbourne');

Kumite::now(strtotime('2012-01-10'));

class BaseTest extends PHPUnit_Framework_TestCase
{
	public function teardown()
	{
	    \Mockery::close();
	}
}