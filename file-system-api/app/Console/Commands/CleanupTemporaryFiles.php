<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupTemporaryFiles extends Command
{
    protected $signature = 'cleanup:temporary-files';
    protected $description = 'Deletes old temporary files from the tmp/ directory';

    public function handle()
    {
        $files = Storage::files('tmp');
        foreach ($files as $file) {
            if (Storage::lastModified($file) < now()->subHours(1)->timestamp) {
                Storage::delete($file);
            }
        }

        $this->info('Temporary files cleaned up.');
    }
}

