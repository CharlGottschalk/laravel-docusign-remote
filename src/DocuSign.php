<?php

namespace CharlGottschalk\DocuSign;

use CharlGottschalk\DocuSign\Handlers\EnvelopeHandler;
use CharlGottschalk\DocuSign\Handlers\EventHandler;
use CharlGottschalk\DocuSign\Handlers\SessionHandler;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocuSign
{
    /**
     * Check if a valid access token exists, otherwise refresh or redirect to DocuSign's login
     *
     * @returns RedirectResponse
     * @throws Exception
     */
    public function login(string $redirectTo): RedirectResponse
    {
        $sessionHandler = new SessionHandler();

        $return = redirect()->back();

        # Check if access token is still valid
        if ($sessionHandler->hasToken()) {
            $return = redirect()->to($redirectTo);
        } else {
            # Access token invalid, is the refresh token still valid?
            if ($sessionHandler->canRefresh()) {
                $sessionHandler->refreshToken();

                $return = redirect()->to($redirectTo);
            } else {

                # All tokens invalid, log the user in
                $response = $sessionHandler->getLoginUrl($redirectTo, url(config('docusign.route_prefix') . '/callback'));
                $return = redirect($response);
            }
        }

        return $return;
    }

    /**
     * Get an access token after successful login
     *
     */
    public function getToken(Request $request): bool|string
    {
        $sessionHandler = new SessionHandler();

        # Request access token
        $response = $sessionHandler->getToken($request);

        if (! $response['success']) {
            return false;
        }

        # Return redirect URL from state
        return $sessionHandler->getRedirect();
    }

    /**
     * Get a fresh authentication token
     *
     * @return string
     * @throws Exception
     */
    public static function refreshToken(): string
    {
        $sessionHandler = new SessionHandler();

        return $sessionHandler->refreshToken();
    }

    /**
     * Create and return an EnvelopeHandler
     *
     * @return EnvelopeHandler
     */
    public static function create(): EnvelopeHandler
    {
        # Initialise and return $envelopeHandler
        return new EnvelopeHandler();
    }

    /**
     * Is the DocuSign event actually coming from DocuSign
     *
     * @param Request $request
     * @return bool
     */
    public static function authenticEvent(Request $request): bool
    {
        $eventHandler = new EventHandler();

        # Check if request is coming from DocuSign
        return $eventHandler->isAuthentic($request);
    }

    /**
     * Process a DocuSign event
     *
     * @param Request $request
     * @return void
     */
    public static function processEvent(Request $request): void
    {
        $eventHandler = new EventHandler();

        # Process the DocuSign event payload
        $eventHandler->process($request);
    }
}
