<?php

namespace CharlGottschalk\DocuSign\Handlers;

use CharlGottschalk\DocuSign\Models\Envelope;
use CharlGottschalk\DocuSign\Models\EnvelopeRecipient;
use CharlGottschalk\DocuSign\Exceptions\MissingData;
use Illuminate\Http\Request;

class RecipientHandler
{
    /**
     * The envelope being processed
     *
     * @var Envelope
     */
    protected Envelope $envelope;

    /**
     * The The envelope recipient being processed
     *
     * @var string
     */
    protected string $envelopeRecipient = EnvelopeRecipient::class;

    /**
     * Hold the Request object
     *
     * @var Request
     */
    protected Request $request;

    /**
     * Hold a list of recipients
     *
     * @var array
     */
    protected array $recipients = [];

    /**
     * Hold the carbon copy recipient
     *
     * @var array
     */
    protected array $carbonCopy = [];

    /**
     * Instantiate a new RecipientHandler
     * @param Envelope|null $envelope
     */
    public function __construct(Envelope $envelope = null)
    {
        if (! empty($envelope)) {
            $this->envelope = $envelope;
        }
    }

    /**
     * Add a recipient
     *
     * @param string $name
     * @param string $email
     * @param int $order
     * @return RecipientHandler
     */
    public function addRecipient(string $name, string $email, int $order = 1): RecipientHandler
    {
        $this->recipients[] = [
            'name' => $name,
            'email' => $email,
            'order' => $order,
        ];

        return $this;
    }

    /**
     * Set a CC recipient
     *
     * @param string $name
     * @param string $email
     * @return Envelope
     */
    public function setCCRecipient(string $name, string $email): Envelope
    {
        $this->carbonCopy['name'] = $name;
        $this->carbonCopy['email'] = $email;

        return $this->envelope;
    }

    /**
     * Set the envelope model to handle
     *
     * @param Envelope $envelope
     * @return RecipientHandler
     */
    public function for(Envelope $envelope): RecipientHandler
    {
        $this->envelope = $envelope;

        return $this;
    }

    /**
     * Store the recipients in the database
     *
     * @return void
     * @throws MissingData
     */
    public function save(): void
    {
        if (! count($this->recipients)) {
            throw MissingData::recipientsMissing();
        }

        $order = 0;
        # Save recipients in the DB
        foreach ($this->recipients as $recipient) {
            EnvelopeRecipient::create([
                'envelope_id' => $this->envelope->id,
                'name' => $recipient['name'],
                'email' => $recipient['email'],
                'order' => $recipient['order'],
            ]);

            $order = $recipient['order'];
        }

        EnvelopeRecipient::create([
            'envelope_id' => $this->envelope->id,
            'name' => $this->carbonCopy['name'],
            'email' => $this->carbonCopy['email'],
            'order' => $order + 1,
            'is_cc' => true,
        ]);
    }
}
