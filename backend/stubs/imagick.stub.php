<?php

/**
 * IDE-only stubs for Imagick extension classes.
 *
 * Runtime note: classes are declared only when Imagick is not loaded.
 */
if (!class_exists('Imagick')) {
    class Imagick implements IteratorAggregate
    {
        public const FILTER_LANCZOS = 22;
        public const COMPOSITE_OVER = 40;
        public const ALPHACHANNEL_REMOVE = 11;

        public function __construct(?string $files = null) {}
        public function readImage(string $filename): bool { return true; }
        public function readImageBlob(string $image): bool { return true; }
        public function pingImageBlob(string $image): bool { return true; }
        public function setImageFormat(string $format): bool { return true; }
        public function setImageBackgroundColor(string|ImagickPixel $background): bool { return true; }
        public function setImageAlphaChannel(int $alphachannel): bool { return true; }
        public function thumbnailImage(int $columns, int $rows, bool $bestfit = false, bool $fill = false): bool { return true; }
        public function setResolution(float $xResolution, float $yResolution): bool { return true; }
        public function getNumberImages(): int { return 1; }
        public function setIteratorIndex(int $index): bool { return true; }
        public function setFirstIterator(): bool { return true; }
        public function getImageWidth(): int { return 1; }
        public function getImageHeight(): int { return 1; }
        public function compositeImage(Imagick $composite_object, int $composite, int $x, int $y, int $channel = 0): bool { return true; }
        public function annotateImage(ImagickDraw $draw_settings, float $x, float $y, float $angle, string $text): bool { return true; }
        public function writeImagesBlob(bool $adjoin = true): string { return ''; }
        public function getImageBlob(): string { return ''; }
        public function writeImage(string $filename): bool { return true; }
        public function resizeImage(int $columns, int $rows, int $filter, float $blur, bool $bestfit = false): bool { return true; }
        public function clear(): bool { return true; }
        public function destroy(): bool { return true; }

        public function getIterator(): Traversable
        {
            return new ArrayIterator([]);
        }
    }
}

if (!class_exists('ImagickDraw')) {
    class ImagickDraw
    {
        public function setFillColor(ImagickPixel|string $fill_color): bool { return true; }
        public function setFontSize(float $pointsize): bool { return true; }
    }
}

if (!class_exists('ImagickPixel')) {
    class ImagickPixel
    {
        public function __construct(string $color) {}
    }
}
