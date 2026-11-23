<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Validates and re-encodes uploaded images with GD (ADR-0017). Re-encoding strips
 * EXIF/GPS and any embedded payload; WebP derivatives are generated at Media::SIZES.
 * Non-image files (PDF brochures) are stored as-is after MIME sniffing.
 */
class ImageProcessor
{
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    private const DOC_MIMES = ['application/pdf'];

    public const MAX_PIXELS = 40_000_000; // decompression-bomb guard

    /** @param UploadedFile|string $file an uploaded file or an absolute path to a trusted local file */
    public function store(UploadedFile|string $file, Model $owner, string $collection = 'gallery', bool $private = false, ?string $alt = null, ?string $caption = null, bool $demo = false): Media
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $disk = $private ? 'local' : 'public';

        if (in_array($mime, self::IMAGE_MIMES, true)) {
            return $this->storeImage($path, $mime, $owner, $collection, $disk, $private, $alt, $caption, $demo);
        }
        if (in_array($mime, self::DOC_MIMES, true)) {
            return $this->storeDocument($path, $mime, $owner, $collection, $disk, $private, $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($path), $caption);
        }

        throw new InvalidArgumentException("Unsupported file type [{$mime}].");
    }

    private function storeImage(string $path, string $mime, Model $owner, string $collection, string $disk, bool $private, ?string $alt, ?string $caption, bool $demo): Media
    {
        [$w, $h] = getimagesize($path) ?: [0, 0];
        if ($w < 1 || $h < 1 || $w * $h > self::MAX_PIXELS) {
            throw new InvalidArgumentException('Image dimensions are invalid or too large.');
        }

        $img = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            default => imagecreatefromwebp($path),
        };
        if (! $img) {
            throw new InvalidArgumentException('Image could not be decoded.');
        }

        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $orientation = @exif_read_data($path)['Orientation'] ?? 1;
            $img = match ($orientation) {
                3 => imagerotate($img, 180, 0), 6 => imagerotate($img, -90, 0), 8 => imagerotate($img, 90, 0), default => $img
            };
            [$w, $h] = [imagesx($img), imagesy($img)];
        }
        imagepalettetotruecolor($img);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        $media = Media::create([
            'mediable_type' => $owner->getMorphClass(), 'mediable_id' => $owner->getKey(), 'collection' => $collection,
            'disk' => $disk, 'path' => 'pending', 'filename' => 'image.webp', 'mime' => 'image/webp', 'size' => 0,
            'width' => $w, 'height' => $h, 'alt' => $alt, 'caption' => $caption, 'is_private' => $private, 'is_demo' => $demo,
            'sort_order' => (int) Media::where('mediable_type', $owner->getMorphClass())->where('mediable_id', $owner->getKey())->where('collection', $collection)->max('sort_order') + 1,
        ]);

        $dir = 'media/'.$media->id.'-'.Str::random(8);
        $total = 0;
        foreach (Media::SIZES as $size) {
            $out = $w > $size ? imagescale($img, $size, (int) round($h * $size / $w), IMG_BICUBIC) : $img;
            ob_start();
            imagewebp($out, null, 82);
            $bytes = (string) ob_get_clean();
            Storage::disk($disk)->put("{$dir}/{$size}.webp", $bytes);
            $total += strlen($bytes);
            if ($out !== $img) {
                imagedestroy($out);
            }
        }
        imagedestroy($img);

        $media->update(['path' => $dir, 'size' => $total]);

        return $media;
    }

    private function storeDocument(string $path, string $mime, Model $owner, string $collection, string $disk, bool $private, string $original, ?string $caption): Media
    {
        $dir = 'media/doc-'.Str::random(16);
        $filename = Str::slug(pathinfo($original, PATHINFO_FILENAME)) ?: 'document';
        $filename .= '.pdf';
        Storage::disk($disk)->putFileAs($dir, new File($path), $filename);

        return Media::create([
            'mediable_type' => $owner->getMorphClass(), 'mediable_id' => $owner->getKey(), 'collection' => $collection,
            'disk' => $disk, 'path' => $dir, 'filename' => $filename, 'mime' => $mime, 'size' => filesize($path),
            'caption' => $caption, 'is_private' => $private,
        ]);
    }
}
