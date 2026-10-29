<?php

namespace App\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DownloadDatabaseSnapshotController
{
    public function __invoke(Request $request): StreamedResponse
    {
        // Only super administrators may download database backups.
        abort_unless(
            $request->user()?->role->value === 'super_admin',
            403
        );

        $disk = config('db-snapshots.disk', 'snapshots');

        $snapshotName = 'krishna-engineers-' . now()->format('Ymd-His');

        try {
            // Create a fresh snapshot before downloading.
            $exitCode = Artisan::call('snapshot:create', [
                'name' => $snapshotName,
            ]);

            if ($exitCode !== 0) {
                throw new RuntimeException(
                    'The database snapshot command failed.'
                );
            }

            $storage = Storage::disk($disk);

            // Locate the file created by this request.
            $snapshotPath = collect($storage->allFiles())
                ->first(
                    fn(string $path): bool => str_starts_with(
                        basename($path),
                        $snapshotName . '.'
                    )
                );

            if (!$snapshotPath || !$storage->exists($snapshotPath)) {
                throw new RuntimeException(
                    'The generated database snapshot was not found.'
                );
            }

            $downloadName = basename($snapshotPath);

            return response()->streamDownload(
                function () use ($storage, $snapshotPath): void {
                    $stream = $storage->readStream($snapshotPath);

                    if ($stream === false) {
                        throw new RuntimeException('Unable to read the database snapshot.');
                    }

                    try {
                        fpassthru($stream);
                    } finally {
                        fclose($stream);

                        // Remove the temporary snapshot after streaming.
                        $storage->delete($snapshotPath);
                    }
                },
                $downloadName,
                [
                    'Content-Type' => str_ends_with($downloadName, '.gz')
                        ? 'application/gzip'
                        : 'application/sql',
                    'Cache-Control' => 'private, no-store',
                    'X-Content-Type-Options' => 'nosniff',
                ],
            );
        } catch (Throwable $exception) {
            Log::error('Database snapshot download failed.', [
                'exception' => $exception,
            ]);

            abort(
                500,
                'Unable to generate the database download. Please try again.'
            );
        }
    }
}
