<?php

namespace CharlGottschalk\DocuSign\Handlers;

use Carbon\Carbon;
use CharlGottschalk\DocuSign\Services\CodeGrantService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use stdClass;

/**
 * Used to communicate with DocuSign through Saturn API.
 */
class SessionHandler
{
    public const ACCESS_TOKEN = 'ds_access_token';
    public const REFRESH_TOKEN = 'ds_refresh_token';
    public const REFRESH_TOKEN_EXPIRATION = 'ds_refresh_expires_at';
    public const ACCESS_TOKEN_EXPIRATION = 'ds_token_expires_at';

    /**
     * Hold the security state
     *
     * @var object
     */
    private object $state;

    /**
     * Set authenticated session variables
     *
     * @param array $response
     * @return void
     */
    private function setSession(array $response): void
    {
        if ($response['success']) {
            $timestamp = $response['ds_expiration'];
            $tokenExpiresAt = Carbon::createFromTimestamp($timestamp);
            $refreshExpiresAt = Carbon::now()->addDays(29);

            session([
                self::ACCESS_TOKEN => $response['ds_access_token'],
                self::REFRESH_TOKEN => $response['ds_refresh_token'],
                self::REFRESH_TOKEN_EXPIRATION => $refreshExpiresAt,
                self::ACCESS_TOKEN_EXPIRATION => $tokenExpiresAt,
            ]);
        }
    }

    /**
     * Create a security state for redirect authentication
     *
     * @param string $redirect
     * @return string
     */
    public function createState(string $redirect): string
    {
        # Add security state to ensure calls are coming from DocuSign
        $securityCode = Str::random();

        # Array to hold state values
        $stateArray = [
            'security_code' => $securityCode,
            'redirect' => $redirect,
        ];

        # Encrypt state array
        $encrypted = Crypt::encryptString(json_encode($stateArray));

        # Set state in session for authentication later
        session(['ds_security_code' => $encrypted]);

        return $encrypted;
    }

    /**
     * Check if returned state from DocuSign is valid
     *
     * @param string $state
     * @return bool
     */
    public function stateIsValid(string $state): bool
    {
        # Decrypt the state from DocuSign
        $json = Crypt::decryptString($state);
        $this->state = json_decode($json);

        # Compare DocuSign state with earlier saved state in session
        return session('ds_security_code') == $state;
    }

    /**
     * Return the redirect url from state
     *
     * @return string
     */
    public function getRedirect(): string
    {
        return $this->state->redirect;
    }

    /**
     * Get a value from session
     *
     * @param string $key
     * @return mixed
     */
    public function getSessionValue(string $key): mixed
    {
        return session($key);
    }

    /**
     * Get access token from session
     *
     * @return string
     */
    public static function token(): string
    {
        return session(self::ACCESS_TOKEN);
    }

    /**
     * Clear the current authentication session
     *
     * @return void
     */
    public function clearSession(): void
    {
        session()->forget([
            self::ACCESS_TOKEN,
            self::REFRESH_TOKEN,
            self::REFRESH_TOKEN_EXPIRATION,
            self::ACCESS_TOKEN_EXPIRATION,
        ]);
    }

    /**
     * Check if access token has expired
     *
     * @return bool
     */
    public function tokenHasExpired(): bool
    {
        $expiresAt = $this->getSessionValue(self::ACCESS_TOKEN_EXPIRATION);

        if (empty($expiresAt)) {
            return true;
        }

        if (Carbon::now()->greaterThan($expiresAt)) {
            return true;
        }

        return false;
    }

    /**
     * Check if refresh token is still valid
     *
     * @return bool
     */
    public function canRefresh(): bool
    {
        $expiresAt = $this->getSessionValue(self::REFRESH_TOKEN_EXPIRATION);

        if (empty($expiresAt)) {
            return false;
        }

        if (Carbon::now()->greaterThan($expiresAt)) {
            return false;
        }

        return true;
    }

    /**
     * Check if an access token already exists
     *
     * @return bool
     */
    public function hasToken(): bool
    {
        if (! session()->has(self::ACCESS_TOKEN)) {
            return false;
        }

        if ($this->tokenHasExpired()) {
            return false;
        }

        return true;
    }

    /**
     * Get a login URL for DocuSign authentication
     *
     * @param string $redirect
     * @param string $callback
     * @return string
     */
    public function getLoginUrl(string $redirect, string $callback): string
    {
        $service = new CodeGrantService();

        # Create a new state for the request
        $newState = $this->createState($redirect);

        # Get the DocuSign login URL
        return $service->getLogin($newState, $callback);
    }

    /**
     * Get an access token from a successful DocuSign login
     *
     * @param Request $request
     * @return array
     */
    public function getToken(Request $request): array
    {
        $service = new CodeGrantService();

        # Check if the returned state from DocuSign is valid
        if (! $this->stateIsValid($request->input('state'))) {
            return [
                'success' => false,
                'message' => 'Insecure state',
                'insecure' => true,
            ];
        }

        # Request an access token from DocuSign
        $response = $service->getToken($request->input('code'));

        # Set the authenticated session variables
        $this->setSession($response);

        return $response;
    }

    /**
     * Get a fresh access token using valid refresh token
     *
     * @return string
     * @throws Exception
     */
    public function refreshToken(): string
    {
        $service = new CodeGrantService();

        # Request a fresh access token from DocuSign
        $response = $service->refreshToken($this->getSessionValue(self::REFRESH_TOKEN));

        # Set the authenticated session variables
        $this->setSession($response);

        return $response->data->ds_access_token;
    }
}
