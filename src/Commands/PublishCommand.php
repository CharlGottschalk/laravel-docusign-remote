<?php

namespace CharlGottschalk\DocuSign\Commands;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    public $signature = 'docusign:publish';

    public $description = 'Publishes assets';

    public function handle()
    {
        $this->callSilently('vendor:publish', [
            '--tag' => 'docusign-migrations',
        ]);

        $this->callSilently('vendor:publish', [
            '--tag' => 'docusign-config',
        ]);

        $this->info('DouSign assets published!');
    }
}
