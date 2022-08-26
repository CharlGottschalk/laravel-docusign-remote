<?php

namespace CharlGottschalk\DocuSign\Handlers\Events;

use CharlGottschalk\DocuSign\Events\EnvelopeDeclined as EnvelopeDeclinedEvent;
use CharlGottschalk\DocuSign\Models\Envelope;

class EnvelopeDeclined extends BaseEventHandler
{
    public static function handle($envelope, $payload, $event)
    {
        $envelope = self::setEnvelopeStatus($envelope, $payload, Envelope::ENVELOPE_STATUS_DECLINED);

        # Dispatch related event
        EnvelopeDeclinedEvent::dispatch($envelope, $payload, $event);
    }
}
