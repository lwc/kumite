<?php

namespace Kumite\Allocators;

use Kumite\Allocator;
use Kumite\Test;

class StaticAllocator implements Allocator
{
    public function allocate(Test $test, array $options)
    {
        return $options['variant'];
    }
}
