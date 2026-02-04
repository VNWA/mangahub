<?php

namespace App\Helpers;

use Intervention\Image\ImageManager;

class Image
{
    /**
     * Convert image to webp, jpeg or png format and resize it
     */
    public static function convert(string $source, string $target, ?int $width = null, ?int $height = null, string $extension = 'webp', int $quality = 90): void
    {
        $manager = new ImageManager(['driver' => 'gd']);
        $image = $manager->make($source);

        $maxSize = 1920;

        if ($width && $height) {
            $image->fit($width, $height);
        } elseif ($width || $image->width() > $maxSize) {
            $image->resize($width ?? $maxSize, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        } elseif ($height || $image->height() > $maxSize) {
            $image->resize(null, $height ?? $maxSize, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $extension = strtolower($extension);

        if ($extension === 'png') {
            $image->encode('png')->save($target);

            return;
        }

        if ($extension === 'jpg') {
            $extension = 'jpeg';
        }

        $image->encode($extension, $quality)->save($target);
    }
}
