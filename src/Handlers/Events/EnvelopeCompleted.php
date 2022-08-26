<?php

namespace CharlGottschalk\DocuSign\Handlers\Events;

use CharlGottschalk\DocuSign\Events\EnvelopeCompleted as EnvelopeCompletedEvent;
use CharlGottschalk\DocuSign\Models\Envelope;

class EnvelopeCompleted extends BaseEventHandler
{
    public static function handle($envelope, $payload, $event)
    {
        $envelope = self::setEnvelopeStatus($envelope, $payload, Envelope::ENVELOPE_STATUS_COMPLETE);

        # Dispatch related event
        EnvelopeCompletedEvent::dispatch($envelope, $payload, $event);
    }
}
