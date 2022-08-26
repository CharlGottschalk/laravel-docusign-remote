<?php

namespace CharlGottschalk\DocuSign\Handlers\Events;

use CharlGottschalk\DocuSign\Events\RecipientDeclined as RecipientDeclinedEvent;
use CharlGottschalk\DocuSign\Models\EnvelopeRecipient;

class RecipientDeclined extends BaseEventHandler
{
    public static function handle($envelope, $payload, $event)
    {
        $recipient = self::setRecipientStatus($envelope, $payload, EnvelopeRecipient::RECIPIENT_STATUS_DECLINED);

        # Dispatch related event
        RecipientDeclinedEvent::dispatch($recipient, $envelope, $payload, $event);
    }
}
