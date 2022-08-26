# Laravel DocuSign Remote Signature Client

Easily request remote signatures using DocuSign.

This package provides a simple fluent interface for creating envelopes and requesting signatures using [DocuSign](https://www.docusign.com/).

### Example:
```php
use DocuSign;

# Request a signature
$response = DocuSign::create()
                    ->setCCRecipient('Recipient Name', 'ccrecipient@example.com')
                    ->addRecipient('Recipient Name', 'recipient@example.com')
                    ->setSubject('Please sign :document')
                    ->selectDocument('Document_Name.pdf')
                    ->request();
```

---

## Installation

You can install the package via composer:

```bash
composer require charlgottschalk/laravel-docusign-remote
```

---

### Documentation

Full documentation is still under construction

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

