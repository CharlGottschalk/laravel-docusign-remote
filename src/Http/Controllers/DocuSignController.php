<?php

namespace CharlGottschalk\DocuSign\Http\Controllers;

use CharlGottschalk\DocuSign\Events\NonAuthenticEvent;
use CharlGottschalk\DocuSign\Http\Requests\AccessTokenRequest;
use DocuSign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

class DocuSignController extends BaseController
{
    /**
     * The method called by DocuSign authentication
     * Returns the DocuSign session, including access token
     *
     * @param AccessTokenRequest $request
     * @return RedirectResponse
     */
    public function callback(AccessTokenRequest $request): RedirectResponse
    {
        # Request access token
        $response = DocuSign::getToken($request);

        # $response will be 'false' if request failed
        if (! $response) {
            // Handle error
        }

        # $response will contain intended redirect route if successful
        return redirect()->to($response);
    }

    /**
     * Process a DocuSign event - Webhook
     *
     * @param Request $request
     * @return Response
     */
    public function event(Request $request): Response
    {
        if (! DocuSign::authenticEvent($request)) {
            NonAuthenticEvent::dispatch($request);

            return response('Unauthorized', 401);
        }

        DocuSign::processEvent($request);

        return response('OK', 200);
    }
}
