<?php

namespace Kumite;

interface Allocator
{
	public function allocate(Test $test);
}
