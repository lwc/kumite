<?php

namespace Kumite\Adapters;

interface StorageAdapter
{
    public function createParticipant($testKey, $variantKey, $metadata=null);

    public function createEvent($testKey, $variantKey, $eventKey, $participantId, $metadata=null);

    public function countParticipants($testKey, $variantKey);

    public function countEvents($testKey, $variantKey, $eventKey);
}
