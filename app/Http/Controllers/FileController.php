<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Task;
use App\Support\Uploads;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class FileController extends Controller
{
    public function logo(): Response
    {
        $path = settings('logo_path');
        $disk = Storage::disk(settings('logo_disk') ?: Uploads::disk());

        abort_unless($path && $disk->exists($path), 404);

        return response($disk->get($path), 200, [
            'Content-Type' => $disk->mimeType($path) ?: 'image/png',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }

    public function attachment(Attachment $attachment): Response
    {
        if ($attachment->attachable instanceof Task) {
            Gate::authorize('view', $attachment->attachable);
        }

        return Uploads::download($attachment);
    }
}
