<?php

namespace CharlGottschalk\DocuSign\Services;

use CharlGottschalk\DocuSign\Models\Envelope;
use DocuSign\eSign\Client\ApiException;
use DocuSign\eSign\Model\CarbonCopy;
use DocuSign\eSign\Model\Document;
use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Model\Recipients;
use DocuSign\eSign\Model\Signer;
use DocuSign\eSign\Model\SignHere;
use DocuSign\eSign\Model\Tabs;

class RemoteSigningService
{
    /**
     * Handles the envelope API
     * @var SignatureClientService
     */
    protected SignatureClientService $clientService;

    /**
     * The definition containing the documents to sign
     * and the recipients
     *
     * @var EnvelopeDefinition
     */
    protected EnvelopeDefinition $envelopeDefinition;

    /**
     * Holds the arguments for creating an envelope
     *
     * @var array
     */
    protected array $model;

    /**
     * Holds the request email's subject line
     *
     * @var string
     */
    protected string $subject;

    /**
     * Holds the status of the envelope
     *
     * @var string
     */
    protected string $status;

    /**
     * Generate a subject line for the email
     * if one isn't already set
     *
     * @return void
     */
    protected function generateSubject(): void
    {
        if (empty($this->subject)) {
            $this->subject = 'Please sign ' . $this->model['document_name'];
        }
    }

    /**
     * Make the template model and initiate the SignatureClientService
     *
     * @param Envelope $envelope
     * @param string $token
     */
    protected function init(Envelope $envelope, string $token): void
    {
        $this->status = Envelope::ENVELOPE_STATUS_SENT;
        $this->makeModel($envelope, $token);
        $this->clientService = new SignatureClientService($this->model);
    }

    /**
     * Creates an envelope definition for the request.
     *
     * @param Envelope $envelope
     * @return void -- returns an envelope definition
     */
    protected function makeEnvelope(Envelope $envelope): void
    {
        # The envelope has two recipients.
        # recipient 1 - signer
        # recipient 2 - cc
        # The envelope will be sent first to the signer.
        # After it is signed, a copy is sent to the cc recipient.

        # Create the envelope definition
        $this->envelopeDefinition = new EnvelopeDefinition([
            'email_subject' => $this->subject,
        ]);

        # Get the base64 value of the document to be signed
        $documentBase64 = $envelope->file;

        # Create the DocuSign document model
        $doc = new Document([
            'document_base64' => $documentBase64,
            'name' => $envelope->original_filename,
            'file_extension' => $envelope->extension,
            'document_id' => '1',  # a label used to reference the doc - must be a positive integer
        ]);

        # Add the document to the envelope
        $this->envelopeDefinition->setDocuments([$doc]);

        # Create the signing recipient model
        $signingRecipients = [];

        foreach ($this->model['envelope_definition']['recipients'] as $recipient) {
            $signingRecipients[] = new Signer([
                'email' => $recipient->email, 'name' => $recipient->name,
                'recipient_id' => $recipient->order, 'routing_order' => $recipient->order, ]);
        }

        # Create the CC recipient model
        $ccRecipient = new CarbonCopy([
            'email' => $this->model['envelope_definition']['cc_email'], 'name' => $this->model['envelope_definition']['cc_name'],
            'recipient_id' => count($signingRecipients) + 1, 'routing_order' => count($signingRecipients) + 1, ]);

        # Add recipients and signing tabs to the envelope
        $this->addSigners($signingRecipients, $ccRecipient);
    }

    /**
     * Add recipients and signing tabs to the envelope definition
     *
     * @param array $signingRecipients
     * @param CarbonCopy $ccRecipient
     */
    protected function addSigners(array $signingRecipients, CarbonCopy $ccRecipient): void
    {
        # Create signHere fields (also known as tabs) on the documents,
        # We're using anchor (autoPlace) positioning
        #
        # The DocuSign platform searches throughout the envelope's
        # documents for matching anchor strings.
        $signHere = new SignHere([
            'anchor_string' => '/sn1/', 'anchor_units' => 'pixels',
            'anchor_y_offset' => '10', 'anchor_x_offset' => '20', ]);

        # Add the tabs model (including the signHere tabs) to the signing recipient
        # The tabs object expects arrays of the different field/tab types
        foreach ($signingRecipients as $recipient) {
            $recipient->setTabs(new Tabs([
                'sign_here_tabs' => [$signHere], ]));
        }

        # Add the recipients to the envelope object
        $recipients = new Recipients([
            'signers' => $signingRecipients, 'carbon_copies' => [$ccRecipient], ]);
        $this->envelopeDefinition->setRecipients($recipients);

        # Request that the envelope be sent by setting 'status' to 'sent'
        # Request that the envelope be created as a draft by setting 'status' to 'created'
        $this->envelopeDefinition->setStatus($this->model['envelope_definition']['status']);
    }

    /**
     * Get specific template arguments
     *
     * @param Envelope $envelope
     * @param string $token
     * @return void
     */
    protected function makeModel(Envelope $envelope, string $token): void
    {
        # Get recipient from document
        $recipients = $envelope->recipients()->where('is_cc', false)->get();
        $carbonCopy = $envelope->recipients()->where('is_cc', true)->first();

        $envelopeModel = [
            'recipients' => $recipients,
            'cc_email' => $carbonCopy->email,
            'cc_name' => $carbonCopy->name,
            'status' => $this->status,
        ];

        $this->model = [
            'account_id' => config('docusign.account_id'),
            'base_path' => config('docusign.base_url') . '/restapi',
            'document_name' => $envelope->original_filename,
            'ds_access_token' => $token,
            'envelope_definition' => $envelopeModel,
        ];
    }

    /**
     * Set the status of the envelope to draft
     *
     * @return $this
     */
    public function setAsDraft(): static
    {
        $this->status = Envelope::ENVELOPE_STATUS_DRAFT;

        return $this;
    }

    /**
     * Set the subject line for the email
     *
     * @param string $subject
     * @return $this
     */
    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Do the work of the example
     * 1. Create the envelope request object
     * 2. Send the envelope
     *
     * @param Envelope $envelope
     * @param $token
     * @return array
     */
    public function requestRemoteSignature(Envelope $envelope, $token): array
    {
        $this->init($envelope, $token);
        $this->generateSubject();

        # 1. Create the envelope request object
        $this->makeEnvelope($envelope);
        $envelopeApi = $this->clientService->getEnvelopeApi();

        # 2. call Envelopes::create API method
        # Exceptions will be caught by the calling function
        try {
            $envelopeResponse = $envelopeApi->createEnvelope($this->model['account_id'], $this->envelopeDefinition);
        } catch (ApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }

        return [
            'success' => true,
            'envelope_id' => $envelopeResponse->getEnvelopeId(),
        ];
    }
}
