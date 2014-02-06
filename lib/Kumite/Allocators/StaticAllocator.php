<?php

namespace Kumite\Allocators;

use Kumite\Allocator;
use Kumite\Test;

class StaticAllocator implements Allocator
{
    private $variant;

    public function __construct($variant)
    {
        $this->variant = $variant;
    }

    public function allocate(Test $test)
    {
        return $this->variant;
    }
}
