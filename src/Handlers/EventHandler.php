<?php

namespace CharlGottschalk\DocuSign\Handlers;

use CharlGottschalk\DocuSign\Events\DocuSignEvent;
use CharlGottschalk\DocuSign\Handlers\Events\EnvelopeCompleted;
use CharlGottschalk\DocuSign\Handlers\Events\EnvelopeDeclined;
use CharlGottschalk\DocuSign\Handlers\Events\RecipientCompleted;
use CharlGottschalk\DocuSign\Handlers\Events\RecipientDeclined;
use CharlGottschalk\DocuSign\Handlers\Events\RecipientSent;
use CharlGottschalk\DocuSign\Handlers\Events\RecipientViewed;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventHandler
{
    public const SIGNATURE_HEADER = 'X-DocuSign-Signature-1';

    private array $eventHandlers = [
        'envelopeCompleted' => EnvelopeCompleted::class,
        'envelopeDeclined' => EnvelopeDeclined::class,
        'recipientSent' => RecipientSent::class,
        'recipientViewed' => RecipientViewed::class,
        'recipientCompleted' => RecipientCompleted::class,
        'recipientDeclined' => RecipientDeclined::class,
    ];

    /**
     * Ensure the request is actually coming from DocuSign
     *
     * @param Request $request
     * @return bool
     */
    public function isAuthentic(Request $request): bool
    {
        # Get the request body
        $content = $request->getContent();
        # Get the signature sent with request
        $signature = $request->header(self::SIGNATURE_HEADER);

        # Hash the request body using HMAC key
        $hashed = hash_hmac('sha256', $content, utf8_encode(config('docusign.hmac')));
        $base64Hash = base64_encode(hex2bin($hashed));

        # Compare our hash with the request's hash
        return hash_equals($signature, $base64Hash);
    }

    /**
     * Process event payload
     *
     * @param Request $request
     * @return void
     */
    public function process(Request $request): void
    {
        $envelopeHandler = new EnvelopeHandler();
        # Decode the payload string
        $payload = json_decode($request->getContent());
        # Get the event name
        $event = Str::camel($payload->event);
        # Get the related envelope
        $envelope = $envelopeHandler->fetch($payload->data->envelopeId);

        if (! empty($envelope)) {
            # Call event processing function
            if (array_key_exists($event, $this->eventHandlers)) {
                $this->eventHandlers[$event]::handle($envelope, $payload, $event);
            }

            # Dispatch event
            DocuSignEvent::dispatch($envelope, $payload, $event);
        }
    }
}
