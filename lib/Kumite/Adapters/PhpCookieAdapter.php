<?php

namespace Kumite\Adapters;
// @codeCoverageIgnoreStart
class PhpCookieAdapter implements CookieAdapter
{
	public function getCookies()
	{
		return $_COOKIE;
	}

	public function getCookie($name)
	{
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
	}

	public function setCookie($name, $data)
	{
		setcookie($name, $data, strtotime('2030-01-01'), '/');
	}
}
