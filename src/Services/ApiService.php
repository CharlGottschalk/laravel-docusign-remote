<?php

namespace CharlGottschalk\DocuSign\Services;

use CharlGottschalk\DocuSign\Exceptions\DocuSignException;
use Exception;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class ApiService extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * We use additional options that must be supplied when constructing
     * the object:
     *   authorizationServer => https://account-d.docusign.com or
     *                          https://account.docusign.com
     *   allowSilentAuth => (optional) default is true
     *   targetAccountId => (optional) default is false which means the
     *                                 default account will be used.
     */
    private array $scopes = [
        "signature",
    ];

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     * @throws Exception
     */
    public function getBaseAuthorizationUrl(): string
    {
        $allowSilentAuth = config('docusign.allow_silent_auth');
        $url = $this->getAuthorizationServer();

        if ($allowSilentAuth) {
            $url .= '/oauth/auth';
        } else {
            $url .= '/oauth/auth?prompt=login';
        }

        return $url;
    }

    /**
     * Returns the DocuSign authorization server url
     * @return string authorization server url
     * @throws DocuSignException
     */
    private function getAuthorizationServer(): string
    {
        $url = config('docusign.authentication_server');

        if (empty($url)) {
            throw DocuSignException::authServerNotSet();
        }

        return $url;
    }

    /**
     * Get access token url to retrieve token
     *
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function getBaseAccessTokenUrl(array $params): string
    {
        $url = $this->getAuthorizationServer();
        $url .= '/oauth/token';

        return $url;
    }

    /**
     * Get provider url to fetch user details
     *
     * @param  AccessToken $token
     *
     * @return string
     * @throws Exception
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        $url = $this->getAuthorizationServer();
        $url .= '/oauth/userinfo';

        return $url;
    }

    /**
     * Get the default scopes used by this provider.
     *
     * This should not be a complete list of all scopes, but the minimum
     * required for the provider user interface!
     *
     * @return array
     */
    public function getDefaultScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Check a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (isset($data['error'])) {
            if (! empty($response)) {
                throw new IdentityProviderException(
                    $data['error'],
                    0,
                    $response
                );
            }
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array $response
     * @param AccessToken $token
     * @return ResourceOwner
     * @throws Exception
     */
    protected function createResourceOwner(array $response, AccessToken $token): ResourceOwner
    {
        $r = new ResourceOwner($response);
        $r->targetAccountId = config('docusign.account_id');

        return $r;
    }
}
