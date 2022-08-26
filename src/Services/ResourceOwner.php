<?php

namespace CharlGottschalk\DocuSign\Services;

use Exception;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class ResourceOwner implements ResourceOwnerInterface
{
    /**
     * Raw response
     *
     * @var array
     */
    protected array $response;

    /**
     * The default or selected account.
     * If targetAccountId option was set then that account will be selected.
     * Else (usual case), the user's default account will be selected.
     * @var array [ <account_id>, <is_default>, <account_name>, <base_url>,
     *      (optional) <organization> info ]
     *
     * Example:
     *      "account_id": "7f09961a-a22e-4ea2-8395-aaaaaaaaaaaa",
     *      "is_default": true,
     *      "account_name": "ACME Supplies",
     *      "base_uri": "https://demo.docusign.net",
     *      "organization": {
     *          "organization_id": "9dd9d6cd-7ad1-461a-a432-aaaaaaaaaaaa",
     *          "links": [
     *              {
     *                  "rel": "self",
     *                  "href": "https://account-d.docusign.com/organizations/9dd9d6cd-7ad1-461a-a432-aaaaaaaaaaaa"
     *              }
     *          ]
     *      }
     */
    protected mixed $accountInfo = false;

    /**
     * @var mixed|bool
     */
    public mixed $targetAccountId = false;

    /**
     * Creates new resource owner.
     *
     * @param array $response
     * @throws Exception if an account is selected but not found.
     */
    public function __construct(array $response = [])
    {
        $this->response = $response;
        $this->targetAccountId = config('docusign.account_id');

        # Find the selected or default account
        if ($this->targetAccountId) {
            foreach ($response['accounts'] as $accountInfo) {
                if ($accountInfo['account_id'] == $this->targetAccountId) {
                    $this->accountInfo = $accountInfo;

                    break;
                }
            }
            if (! $this->accountInfo) {
                throw new Exception("Targeted Account with Id{$this->targetAccountId} not found.");
            }
        } else {
            # Find the default account info
            foreach ($response['accounts'] as $accountInfo) {
                if ($accountInfo['is_default']) {
                    $this->accountInfo = $accountInfo;

                    break;
                }
            }
        }
    }

    /**
     * Returns the identifier of the authorized resource owner.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->getUserId();
    }

    /**
     * Get resource owner id
     *
     * @return string|null
     */
    public function getUserId(): ?string
    {
        return $this->response['sub'] ?: null;
    }

    /**
     * Get resource owner email
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->response['email'] ?: null;
    }

    /**
     * Get resource owner name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->response['name'] ?: null;
    }

    /**
     * Get selected account info
     *
     * @return mixed [account_id, is_default, account_name, base_url]
     */
    public function getAccountInfo(): mixed
    {
        return $this->accountInfo;
    }

    /**
     * Get array of account info for the user's accounts
     * An account's info may include organization info
     *
     * @return array
     */
    public function getAccounts(): array
    {
        return $this->response['accounts'];
    }

    /**
     * Return all the owner details available as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->response;
    }
}
