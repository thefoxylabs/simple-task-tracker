<?php

namespace App\Support;

use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * File uploads live on the default filesystem disk (Cloudflare R2 out of the
 * box, see FILESYSTEM_DISK in .env).
 */
class Uploads
{
    /** Allowed attachment types and maximum size (KB) used in validation. */
    public const RULES = ['file', 'max:20480', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,csv,txt,zip'];

    public static function disk(): string
    {
        return config('filesystems.default');
    }

    /** Download a stored file, via a short-lived signed URL on cloud disks. */
    public static function download(Attachment $attachment): Response
    {
        $disk = Storage::disk($attachment->disk);

        if (config("filesystems.disks.{$attachment->disk}.driver") === 's3') {
            return redirect()->away($disk->temporaryUrl($attachment->path, now()->addMinutes(5), [
                'ResponseContentDisposition' => HeaderUtils::makeDisposition('attachment', $attachment->name, self::asciiName($attachment->name)),
            ]));
        }

        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->name);
    }

    /** A plain-ASCII fallback filename for older browsers. */
    public static function asciiName(string $name): string
    {
        return Str::ascii(str_replace(['%', '/', '\\', '"'], '', $name)) ?: 'download';
    }

    /** Remove control characters and path parts from an uploaded file's name. */
    public static function cleanName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';

        return Str::limit(trim($name), 200, '') ?: 'file';
    }
}
