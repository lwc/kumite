<?php

namespace Kumite\Allocators;

use Kumite\Allocator;

class UCB1Allocator implements Allocator
{
    public function allocate(\Kumite\Test $test, array $options)
    {
        $event = $options['event'];

        $ucbValues = array();

        foreach ($test->variantKeys() as $v) {
            $armCount = $this->getArmCount($v, $test);

            // try all variants at least once
            if ($armCount == 0)
                return $v;

            $r = $this->getConversionRate($v, $test, $event);
            $b = $this->getBonus($v, $test);

            $ucbValues[$v] = $r + $b;
        }

        // return variant with the highest chance for success
        return array_search(max($ucbValues), $ucbValues);
    }

    private function getArmCount($v, $test)
    {
        return $test->countParticipants($v);
    }

    private function getEventCount($v, $test, $event)
    {
        return $test->countEvents($v, $event);
    }

    private function getConversionRate($variant, $test, $event)
    {
        $e = $this->getEventCount($variant, $test, $event);
        $n = $this->getArmCount($variant, $test);
        return $e / (float) $n;
    }

    private function getBonus($variant, $test)
    {
        $totalCount = array_sum($this->armCounts);

        $armCount = $this->getArmCount($variant, $test);

        return sqrt((2.0 * log($totalCount)) / $armCount);
    }
}
