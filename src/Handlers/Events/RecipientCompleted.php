<?php

namespace CharlGottschalk\DocuSign\Handlers\Events;

use CharlGottschalk\DocuSign\Events\RecipientCompleted as RecipientCompletedEvent;
use CharlGottschalk\DocuSign\Models\EnvelopeRecipient;

class RecipientCompleted extends BaseEventHandler
{
    public static function handle($envelope, $payload, $event)
    {
        $recipient = self::setRecipientStatus($envelope, $payload, EnvelopeRecipient::RECIPIENT_STATUS_SIGNED);

        # Dispatch related event
        RecipientCompletedEvent::dispatch($recipient, $envelope, $payload, $event);
    }
}
