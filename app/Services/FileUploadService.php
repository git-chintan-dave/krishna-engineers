<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    private const string DISK = 'public';

    /**
     * Handle storing a new file.
     *
     * Returns the existing value when no file was uploaded.
     */
    public function store(
        ?UploadedFile $file,
        string $directory,
    ): ?string {
        if (! $file) {
            return null;
        }

        return $this->upload(
            $file,
            $directory,
        );
    }

    /**
     * Store a new uploaded file.
     */
    public function upload(
        UploadedFile $file,
        string $directory,
    ): string {
        $extension = $file->getClientOriginalExtension();

        $filename = Str::uuid()
            ->toString()
            .'.'
            .$extension;

        $file->storeAs(
            $directory,
            $filename,
            self::DISK,
        );

        return $filename;
    }

    /**
     * Handle updating an existing file.
     *
     * If a new file is supplied, the old file is replaced.
     *
     * If remove is true, the old file is deleted.
     *
     * If neither is provided, the existing filename is returned.
     */
    public function update(
        ?UploadedFile $file,
        ?string $oldFilename,
        string $directory,
        bool $remove = false,
    ): ?string {
        if ($file) {
            return $this->replace(
                $file,
                $oldFilename,
                $directory,
            );
        }

        if ($remove) {
            $this->delete(
                $oldFilename,
                $directory,
            );

            return null;
        }

        return $oldFilename;
    }

    /**
     * Replace an existing file with a new upload.
     */
    public function replace(
        UploadedFile $file,
        ?string $oldFilename,
        string $directory,
    ): string {
        $filename = $this->upload(
            $file,
            $directory,
        );

        $this->delete(
            $oldFilename,
            $directory,
        );

        return $filename;
    }

    /**
     * Delete a stored file.
     */
    public function delete(
        ?string $filename,
        string $directory,
    ): void {
        if (! $filename) {
            return;
        }

        Storage::disk(self::DISK)->delete(
            $directory.'/'.$filename,
        );
    }
}
