<?php

namespace CharlGottschalk\DocuSign\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \DenCreative\DocuSign\DocuSign
 */
class DocuSign extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'docusign';
    }
}
