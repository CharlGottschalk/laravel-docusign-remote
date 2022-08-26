<?php

namespace CharlGottschalk\DocuSign\Events;

use CharlGottschalk\DocuSign\Models\Envelope;
use CharlGottschalk\DocuSign\Models\EnvelopeRecipient;

class BaseRecipientEvent extends BaseEvent
{
    /**
     * The envelope instance.
     *
     * @var EnvelopeRecipient
     */
    public EnvelopeRecipient $recipient;

    /**
     * Create a new event instance.
     *
     * @param EnvelopeRecipient $recipient
     * @param Envelope $envelope
     * @param object $payload
     * @param string $event
     */
    public function __construct(EnvelopeRecipient $recipient, Envelope $envelope, object $payload, string $event)
    {
        $this->$recipient = $recipient;
        parent::__construct($envelope, $payload, $event);
    }
}
