<?php

namespace Kumite\Adapters;

interface StorageAdapter
{
	/**
	 * @return participantId
	 */
	public function createParticipant($testKey, $variantKey);

	public function createEvent($testKey, $variantKey, $eventKey, $participantId, $metadata=null);

}