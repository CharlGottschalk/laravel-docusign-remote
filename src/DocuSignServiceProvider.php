<?php

namespace CharlGottschalk\DocuSign;

use App;
use CharlGottschalk\DocuSign\Commands\PublishCommand;
use Illuminate\Http\Request;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DocuSignServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package's assets
     *
     * @param Package $package
     */
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('docusign')
            ->hasConfigFile()
            ->hasMigrations(['create_envelopes_table', 'create_envelope_recipients_table'])
            ->hasCommand(PublishCommand::class);
    }

    /**
     * Perform logic during package registration
     */
    public function registeringPackage()
    {
        # Bind the DocuSign facade
        App::bind('docusign', function () {
            return new DocuSign();
        });

        # Macro that allows the addition of recipients to the request object
        # Usage: $request->mergeRecipient(['name' => 'Jean-Luc Picard', 'email' => 'jl.picard@starfleet.com', 'order' => 1]);
        Request::macro(
            'mergeRecipient',
            function (array $recipient) {
                if ($this->has('recipients')) {
                    $this->merge([
                        'recipients' => array_merge($recipient, $this->input('recipients')),
                    ]);
                } else {
                    $this->merge([
                        'recipients' => [$recipient],
                    ]);
                }
            }
        );
    }
}
