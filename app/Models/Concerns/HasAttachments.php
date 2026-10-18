<?php

namespace App\Models\Concerns;

use App\Models\Attachment;
use App\Models\User;
use App\Support\Uploads;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;

trait HasAttachments
{
    public static function bootHasAttachments(): void
    {
        // Delete one by one so the stored files are removed too.
        static::deleting(fn ($model) => $model->attachments->each->delete());
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }

    public function attach(UploadedFile $file, User $user): ?Attachment
    {
        $disk = Uploads::disk();
        $path = $file->store('attachments/'.now()->format('Y/m'), $disk);

        if (! $path) {
            return null;
        }

        return $this->attachments()->create([
            'user_id' => $user->id,
            'disk' => $disk,
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }
}
