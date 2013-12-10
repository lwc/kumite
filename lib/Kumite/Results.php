<?php

namespace Kumite;

class Results
{
    private $test;
    private $totalParticipants = 0;
    private $variantTotals = array();
    private $events = array();
    private $eventTotals = array();

    public function __construct($test)
    {
        $this->test = $test;
        $this->query();
    }

    private function query()
    {
        $pTotal = KumiteParticipant::getTotalsForTest($this->testKey);
        $eTotal = KumiteEvent::getTotalsForTest($this->testKey);

        foreach ($pTotal as $row) {
            $this->totalParticipants += $row['total'];
            $this->variantTotals[$row['variantkey']] = $row['total'];
        }

        foreach ($eTotal as $row) {
            $this->eventTotals[$row['variantkey']][$row['eventkey']] = $row['total'];
            $this->events[$row['eventkey']] = $row['eventkey'];
        }
    }

    public function variants()
    {
        return array_keys($this->variantTotals);
    }

    public function events()
    {
        return array_keys($this->events);
    }

    public function totalParticipants()
    {
        return $this->totalParticipants;
    }

    public function variantTotal($variantKey)
    {
        return $this->variantTotals[$variantKey];
    }

    public function eventTotal($variantKey, $eventKey)
    {
        if (!isset($this->eventTotals[$variantKey]) || !isset($this->eventTotals[$variantKey][$eventKey]))
            return 0;
        return $this->eventTotals[$variantKey][$eventKey];
    }

    public function conversionRate($variantKey, $eventKey)
    {
        return 1.0 * $this->eventTotal($variantKey, $eventKey) / $this->variantTotal($variantKey);
    }

    public function eventPercent($variantKey, $eventKey)
    {
        return round($this->conversionRate($variantKey, $eventKey) * 100, 2);
    }

    public function confidenceInterval($variantKey, $eventKey)
    {
        $views = $this->variantTotal($variantKey);
        $rate = $this->conversionRate($variantKey, $eventKey);
        $standardError = sqrt(($rate * (1 - $rate)) / $views);
        return round($standardError * 1.96 * 100, 2);
    }

    public function changePercent($variantKey, $controlKey, $eventKey)
    {
        $cRate = $this->conversionRate($controlKey, $eventKey);
        $vRate = $this->conversionRate($variantKey, $eventKey);
        return round((($vRate - $cRate) / $cRate) * 100, 2);
    }

    public function significance($variantKey, $controlKey, $eventKey)
    {
        $pControl = $this->conversionRate($controlKey, $eventKey);
        $pTreatment = $this->conversionRate($variantKey, $eventKey);
        $nControl = $this->variantTotal($controlKey);
        $nTreatment = $this->variantTotal($variantKey);

        # convert to a z score
        $sigmaCombined = sqrt($pTreatment * (1 - $pTreatment) / $nTreatment +
            $pControl * (1 - $pControl) / $nControl);
        # add 1e-8 to denominator to avoid divide by zero
        $z = ($pTreatment - $pControl) / ($sigmaCombined + 1e-8);

        # return the likelihood of a value this extreme or greater under
        # the null hypothesis
        $p = 2 * (1 - stats_cdf_normal(abs($z), 0, 1, 1));

        return 100 - round(100 * $p, 2);
    }
}
