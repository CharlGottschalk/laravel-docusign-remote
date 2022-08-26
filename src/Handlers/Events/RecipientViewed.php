<?php

namespace CharlGottschalk\DocuSign\Handlers\Events;

use CharlGottschalk\DocuSign\Events\RecipientViewed as RecipientViewedEvent;
use CharlGottschalk\DocuSign\Models\EnvelopeRecipient;

class RecipientViewed extends BaseEventHandler
{
    public static function handle($envelope, $payload, $event)
    {
        $recipient = self::setRecipientStatus($envelope, $payload, EnvelopeRecipient::RECIPIENT_STATUS_VIEWED);

        # Dispatch related event
        RecipientViewedEvent::dispatch($recipient, $envelope, $payload, $event);
    }
}
