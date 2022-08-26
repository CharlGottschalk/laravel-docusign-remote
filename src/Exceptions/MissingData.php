<?php

namespace CharlGottschalk\DocuSign\Exceptions;

use Exception;

class MissingData extends Exception
{
    public static function recipientsMissing(): self
    {
        return new static('No recipients are defined.');
    }

    public static function ccRecipientMissing(): self
    {
        return new static('No CC recipient is defined.');
    }

    public static function documentDoesNotExist(): self
    {
        return new static('The given document does not exist at the provided path');
    }
}
