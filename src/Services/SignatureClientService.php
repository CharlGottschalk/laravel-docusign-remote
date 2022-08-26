<?php

namespace CharlGottschalk\DocuSign\Services;

use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\Client\ApiClient;
use DocuSign\eSign\Client\ApiException;
use DocuSign\eSign\Configuration;
use DocuSign\eSign\Model\RecipientViewRequest;
use DocuSign\eSign\Model\ViewUrl;
use Exception;

class SignatureClientService
{
    /**
     * DocuSign API Client
     */
    public ApiClient $apiClient;

    /**
     * Create a new client instance.
     *
     * @param $args
     * @return void
     */
    public function __construct($model)
    {
        # Construct API headers
        # Exceptions will be caught by the calling function
        $config = new Configuration();
        $config->setHost($model['base_path']);
        $config->addDefaultHeader('Authorization', 'Bearer ' . $model['ds_access_token']);
        $this->apiClient = new ApiClient($config);
    }

    /**
     * Getter for the RecipientViewRequest
     *
     * @param $authenticationMethod
     * @param $envelopeDefinition
     * @return RecipientViewRequest
     */
    public function getRecipientViewRequest($authenticationMethod, $envelopeDefinition): RecipientViewRequest
    {
        return new RecipientViewRequest(
            [
                'authentication_method' => $authenticationMethod,
                'client_user_id' => $envelopeDefinition['signer_client_id'],
                'recipient_id' => '1',
                'return_url' => $envelopeDefinition['ds_return_url'],
                'user_name' => $envelopeDefinition['signer_name'],
                'email' => $envelopeDefinition['signer_email'],
            ]
        );
    }

    /**
     * Getter for the AccountsApi
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param RecipientViewRequest $recipientViewRequest
     * @return ViewUrl - the list of Recipient Views
     * @throws Exception
     */
    public function getRecipientView(string $accountId, string $envelopeId, RecipientViewRequest $recipientViewRequest): ViewUrl
    {
        try {
            $envelope_api = $this->getEnvelopeApi();
            $viewUrl = $envelope_api->createRecipientView($accountId, $envelopeId, $recipientViewRequest);
        } catch (ApiException $e) {
            $error_code = $e->getResponseBody()->errorCode;
            $error_message = $e->getResponseBody()->message;

            throw new Exception($error_message, $error_code);
        }

        return $viewUrl;
    }

    /**
     * Getter for the EnvelopesApi
     */
    public function getEnvelopeApi(): EnvelopesApi
    {
        return new EnvelopesApi($this->apiClient);
    }
}
