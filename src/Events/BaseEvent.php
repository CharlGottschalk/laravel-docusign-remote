<?php

namespace CharlGottschalk\DocuSign\Events;

use CharlGottschalk\DocuSign\Models\Envelope;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BaseEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * The envelope instance.
     *
     * @var Envelope
     */
    public Envelope $envelope;

    /**
     * The event payload.
     *
     * @var object
     */
    public object $payload;

    /**
     * The event.
     *
     * @var string
     */
    public string $event;

    /**
     * Create a new event instance.
     *
     * @param Envelope $envelope
     * @param object $payload
     * @param string $event
     */
    public function __construct(Envelope $envelope, object $payload, string $event)
    {
        $this->envelope = $envelope;
        $this->payload = $payload;
        $this->event = $event;
    }

    /**
     * Get the document bytes from the event payload.
     *
     * @return bool|string|null
     */
    public function document(): bool|string|null
    {
        if (empty($this->payload->data->envelopeSummary->envelopeDocuments[0]->PDFBytes)) {
            return null;
        }

        return base64_decode($this->payload->data->envelopeSummary->envelopeDocuments[0]->PDFBytes);
    }
}
