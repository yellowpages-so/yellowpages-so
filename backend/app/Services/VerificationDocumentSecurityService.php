<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class VerificationDocumentSecurityService
{
    public function scan(string $storageKey): array
    {
        $scanner = trim(
            (string) shell_exec(
                'command -v clamscan 2>/dev/null'
            )
        );

        if ($scanner === '') {
            throw new RuntimeException(
                'ClamAV is not installed. Install clamscan before approving document-based verification.'
            );
        }

        $path = Storage::disk('local')->path(
            $storageKey
        );

        if (! is_file($path)) {
            throw new RuntimeException(
                'Verification document file was not found.'
            );
        }

        $process = new Process([
            $scanner,
            '--no-summary',
            $path,
        ]);

        $process->setTimeout(60);
        $process->run();

        $exitCode = $process->getExitCode();

        if ($exitCode === 0) {
            return [
                'status' => 'clean',
                'clean' => true,
                'output' => trim(
                    $process->getOutput()
                ),
            ];
        }

        if ($exitCode === 1) {
            return [
                'status' => 'infected',
                'clean' => false,
                'output' => trim(
                    $process->getOutput().
                    "\n".
                    $process->getErrorOutput()
                ),
            ];
        }

        throw new RuntimeException(
            'ClamAV scan failed: '.
            trim(
                $process->getOutput().
                "\n".
                $process->getErrorOutput()
            )
        );
    }
}
