<?php

namespace Kumite\Adapters;

interface CookieAdapter
{
	public function getCookies();

	public function setCookie($name, $data);

	public function getCookie($name);
}