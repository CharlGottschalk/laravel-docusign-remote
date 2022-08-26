<?php

namespace CharlGottschalk\DocuSign\Exceptions;

use Exception;

class DocuSignException extends Exception
{
    public static function authServerNotSet(): self
    {
        return new static('DocuSign authorisation server not set');
    }
}
