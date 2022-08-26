<?php

use CharlGottschalk\DocuSign\Http\Controllers\DocuSignController;
use Illuminate\Support\Facades\Route;

if (! function_exists('docusign_callback')) {
    function docusign_callback()
    {
        Route::group([
            'prefix' => config('docusign.route_prefix'),
        ], function ($router) {
            $router->get('callback', [DocuSignController::class, 'callback'])->name('docu-sign.callback');
        });
    }
}

if (! function_exists('docusign_event')) {
    function docusign_event()
    {
        Route::group([
            'prefix' => config('docusign.route_prefix'),
        ], function ($router) {
            $router->post('event', [DocuSignController::class, 'event'])->name('docu-sign.event');
        });
    }
}
