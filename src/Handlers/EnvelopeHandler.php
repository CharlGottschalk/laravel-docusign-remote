<?php

namespace CharlGottschalk\DocuSign\Handlers;

use CharlGottschalk\DocuSign\Exceptions\MissingData;
use CharlGottschalk\DocuSign\Models\Envelope;
use CharlGottschalk\DocuSign\Services\RemoteSigningService;
use Illuminate\Http\Request;

class EnvelopeHandler
{
    /**
     * Required fields
     *
     * @var array|string[]
     */
    protected array $fields = [
        'document',
    ];

    /**
     * Relationship fields
     *
     * @var array|string[]
     */
    protected array $relationships = [];

    /**
     * Recipient Handler
     *
     * @var RecipientHandler
     */
    protected RecipientHandler $recipientHandler;

    /**
     * File Handler
     *
     * @var FileHandler
     */
    protected FileHandler $fileHandler;

    /**
     * The model that represents an envelope in the database
     *
     * @var Envelope
     */
    protected Envelope $envelope;

    /**
     * Hold the Request object
     *
     * @var Request
     */
    protected Request $request;

    /**
     * Hold the data required for requesting a signature
     *
     * @var array
     */
    protected array $data = [];

    /**
     * Hold the subject format
     *
     * @var string
     */
    private string $subject = 'Please sign :document';

    public function __construct()
    {
        $this->envelope = new Envelope();

        $this->recipientHandler = new RecipientHandler();
        $this->fileHandler = new FileHandler();
    }

    /**
     * Make sure all required field values are accounted for
     *
     * @return void
     * @throws MissingData
     */
    private function validateData(): void
    {
        foreach ($this->fields as $key) {
            if (! $this->request->has($key)) {
                throw MissingData::missingValue($key);
            }
        }
    }

    /**
     * Store the uploaded document in storage and insert database entries
     *
     * @return void
     * @throws MissingData
     */
    private function save(): void
    {
        # Format email's subject line
        $this->formatSubject();
        # Persist the envelope into database
        $this->envelope->save();
        # Insert recipients in database
        $this->insertRecipients();
    }

    /**
     * Set the envelope's email subject
     *
     * @return void
     */
    private function formatSubject(): void
    {
        $replacements = [
            ':document' => $this->envelope->original_filename,
        ];

        $this->envelope->subject = strtr($this->subject, $replacements);
    }

    /**
     * Store the uploaded document's database entry
     *
     * @return void
     * @throws MissingData
     */
    private function insertRecipients(): void
    {
        # Save recipients in the DB
        $this->recipientHandler
            ->for($this->envelope)
            ->save();
    }

    /**
     * Set the status of an envelope
     *
     * @param string $status
     * @return EnvelopeHandler
     */
    private function setStatus(string $status): EnvelopeHandler
    {
        # Set the document's status
        $this->envelope->status = $status;
        $this->envelope->save();

        return $this;
    }

    /**
     * Set the docusign id of an envelope
     *
     * @param string $docusignId
     * @return EnvelopeHandler
     */
    private function setEnvelopeId(string $docusignId): EnvelopeHandler
    {
        # Set the document's envelope id to that from DocuSign
        $this->envelope->envelope_id = $docusignId;
        $this->envelope->save();

        return $this;
    }

    /**
     * Get the active model
     *
     * @return Envelope
     */
    public function getModel(): Envelope
    {
        return $this->envelope;
    }

    /**
     * Store the uploaded document in storage and add to envelope
     *
     * @param Request $request
     * @param string $key
     * @return EnvelopeHandler
     */
    public function upload(Request $request, string $key = 'document'): static
    {
        # Save the document in storage and update envelope
        $this->envelope = $this->fileHandler->for($this->envelope)
                                    ->upload($request);

        return $this;
    }

    /**
     * Add a recipient
     *
     * @param string $name
     * @param string $email
     * @param int $order
     * @return EnvelopeHandler
     */
    public function addRecipient(string $name, string $email, int $order = 1): EnvelopeHandler
    {
        $this->recipientHandler
            ->for($this->envelope)
            ->addRecipient($name, $email, $order);

        return $this;
    }

    /**
     * Add recipients
     *
     * @param array $recipients
     * @return EnvelopeHandler
     */
    public function addRecipients(array $recipients): EnvelopeHandler
    {
        foreach ($recipients as $recipient) {
            $this->recipientHandler
                ->for($this->envelope)
                ->addRecipient($recipient['name'], $recipient['email'], $recipient['order']);
        }

        return $this;
    }

    /**
     * Set the CC recipient
     *
     * @param string $name
     * @param string $email
     * @return EnvelopeHandler
     */
    public function setCCRecipient(string $name, string $email): EnvelopeHandler
    {
        $this->envelope = $this->recipientHandler
                                ->for($this->envelope)
                                ->setCCRecipient($name, $email);

        return $this;
    }

    /**
     * Set the CC recipient
     *
     * @param string $subject
     * @return EnvelopeHandler
     */
    public function setSubject(string $subject): EnvelopeHandler
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Select a document from the configured storage
     *
     * @param string $name
     * @return EnvelopeHandler
     * @throws MissingData
     */
    public function selectDocument(string $name): static
    {
        $this->envelope = $this->fileHandler
                                ->for($this->envelope)
                                ->selectDocument($name);

        return $this;
    }

    /**
     * Append a directory to the document's path
     *
     * @param string $append
     * @return EnvelopeHandler
     */
    public function appendPath(string $append): static
    {
        $this->fileHandler->appendPath($append);

        return $this;
    }

    /**
     * Process the model and document store and request a signature
     *
     * @return array
     * @throws MissingData
     */
    public function request(): array
    {
        # Process model and document
        $this->save();

        $signingService = new RemoteSigningService();

        # Request a signature from DocuSign
        $response = $signingService->requestRemoteSignature($this->envelope, SessionHandler::token());

        if (! $response['success']) {
            return $response;
        }

        # Set document status to sent
        $this->setStatus(Envelope::ENVELOPE_STATUS_SENT)
            ->setEnvelopeId($response['envelope_id']);

        return $response;
    }

    /**
     * Fetch an envelope for the given ID
     *
     * @param string $envelopeId
     * @return Envelope
     */
    public function fetch(string $envelopeId): Envelope
    {
        return Envelope::where('envelope_id', $envelopeId)
                        ->first();
    }
}
