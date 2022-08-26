<?php

namespace CharlGottschalk\DocuSign\Services;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class CodeGrantService
{
    /**
     * Get OAUTH provider
     *
     * @return ApiService $provider
     */
    private function getOAuthProvider(string $state = '', string $callback = ''): ApiService
    {
        if (empty($callback)) {
            $callback = config('docusign.callback_url');
        }

        return new ApiService(
            [
                'clientId' => config('docusign.integration_key'),
                'clientSecret' => config('docusign.integration_secret'),
                'redirectUri' => $callback,
                'authorizationServer' => config('docusign.authentication_server'),
                'allowSilentAuth' => config('docusign.allow_silent_authentication'),
                'state' => $state,
            ]
        );
    }

    /**
     * Get DocuSign login URL
     *
     * @param string $state
     * @param string $callback
     * @return string
     */
    public function getLogin(string $state, string $callback): string
    {
        $provider = $this->getOAuthProvider($state, $callback);

        return $provider->getAuthorizationUrl(['state' => $state]);
    }

    /**
     * Get new access token from given authentication code
     *
     * @param string $code
     * @return array
     */
    public function getToken(string $code): array
    {
        $provider = $this->getOAuthProvider();

        try {
            # Try to get an access token using the authorization code grant.
            $accessToken = $provider->getAccessToken(
                'authorization_code',
                [
                    'code' => $code,
                ]
            );

            # We have an access token, which we may use in authenticated
            # requests against the service provider's API.
            $session = [
                'ds_access_token' => $accessToken->getToken(),
                'ds_refresh_token' => $accessToken->getRefreshToken(),
                'ds_expiration' => $accessToken->getExpires(),
            ];

            # Using the access token, we may look up details about the
            # resource owner.
            $user = $provider->getResourceOwner($accessToken);
            $session['ds_user_name'] = $user->getName();
            $session['ds_user_email'] = $user->getEmail();

            $account_info = $user->getAccountInfo();
            $base_uri_suffix = '/restapi';
            $session['ds_account_id'] = $account_info["account_id"];
            $session['ds_account_name'] = $account_info["account_name"];
            $session['ds_base_path'] = $account_info["base_uri"] . $base_uri_suffix;
            $session['success'] = true;

            return $session;
        } catch (IdentityProviderException $e) {
            # Failed to get the access token or user details.
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get a new access token from given refresh token
     *
     * @param string $refreshToken
     * @return array
     */
    public function refreshToken(string $refreshToken): array
    {
        $provider = $this->getOAuthProvider();

        try {
            # Try to get an access token using the authorization code grant.
            $accessToken = $provider->getAccessToken(
                'refresh_token',
                [
                    'refresh_token' => $refreshToken,
                ]
            );

            # We have an access token, which we may use in authenticated
            # requests against the service provider's API.
            return [
                'ds_access_token' => $accessToken->getToken(),
                'ds_refresh_token' => $accessToken->getRefreshToken(),
                'ds_expiration' => $accessToken->getExpires(),
                'success' => true,
            ];
        } catch (IdentityProviderException $e) {
            # Failed to get the access token or user details.
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
