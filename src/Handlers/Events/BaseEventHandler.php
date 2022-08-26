<?php

namespace CharlGottschalk\DocuSign\Handlers\Events;

use CharlGottschalk\DocuSign\Models\Envelope;
use CharlGottschalk\DocuSign\Models\EnvelopeRecipient;
use Illuminate\Support\Facades\Storage;

class BaseEventHandler
{
    /**
     * Process the related envelope event
     *
     * @param $envelope
     * @param $payload
     * @param $status
     * @return Envelope
     */
    protected static function setEnvelopeStatus($envelope, $payload, $status): Envelope
    {
        if (config('docusign.process_events')) {
            # Set envelope status to 'complete'
            $envelope->status = $status;
            $envelope->save();

            # Store signed document to storage
            Storage::disk(config('docusign.storage.disk'))
                ->put(
                    $envelope->path . '/' . $envelope->name . '_completed.' . $envelope->extension,
                    base64_decode($payload->data->envelopeSummary->envelopeDocuments[0]->PDFBytes)
                );
        }

        return $envelope;
    }

    /**
     * Process the related recipient event
     *
     * @param $envelope
     * @param $payload
     * @param $status
     * @return EnvelopeRecipient
     */
    protected static function setRecipientStatus($envelope, $payload, $status): EnvelopeRecipient
    {
        $recipient = EnvelopeRecipient::where('envelope_id', $envelope->id)
            ->where('order', $payload->data->recipientId)
            ->first();

        if (config('docusign.process_events')) {
            # Set recipient status to 'signed'
            $recipient->status = $status;
            $recipient->save();
        }

        return $recipient;
    }
}
