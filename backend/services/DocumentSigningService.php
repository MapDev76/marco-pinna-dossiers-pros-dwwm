<?php

/**
 * Document signing service.
 *
 * Provides shared Imagick-based in-place signing logic used by both the
 * dashboard admin flow and the employee-space flow.  All functions are pure
 * (no global state, no HTTP side-effects) so they are easy to call from any
 * controller.
 *
 * Supported source formats: application/pdf, image/png, image/jpeg, image/webp.
 * Returns an array with keys:
 *   blob      – binary string of the signed document
 *   mime_type – MIME type of the signed document
 *   page      – 1-based page number where the signature was applied
 *
 * Throws RuntimeException when signing cannot be completed.
 */

/**
 * Resize a cloned Imagick signature stamp to at most $targetMaxWidth pixels wide.
 *
 * @param mixed $signatureStamp  An Imagick object already loaded with the signature image.
 * @param int   $targetMaxWidth  Maximum output width in pixels.
 * @return mixed                 A cloned, possibly-resized Imagick object (caller must destroy it).
 */
function documentSigningBuildStamp(mixed $signatureStamp, int $targetMaxWidth): mixed
{
    $filterLanczos = defined('\\Imagick::FILTER_LANCZOS') ? (int) constant('\\Imagick::FILTER_LANCZOS') : 1;
    $stamp = clone $signatureStamp;
    $targetMaxWidth = max(80, $targetMaxWidth);
    $currentWidth = max(1, (int) $stamp->getImageWidth());

    if ($currentWidth > $targetMaxWidth) {
        try {
            $stamp->resizeImage($targetMaxWidth, 0, $filterLanczos, 1, true);
        } catch (Throwable $resizeError) {
            // Keep original signature size if resize is not supported for this raster payload.
        }
    }

    return $stamp;
}

/**
 * Assemble a multi-page Imagick PDF object into a PDF binary via temporary file.
 *
 * Used as a fallback when writeImagesBlob fails but Imagick can still write
 * image sequences to disk. This implementation intentionally avoids shell calls.
 *
 * @param mixed $pdfDoc  An Imagick multi-page object.
 * @return string        Raw PDF bytes.
 * @throws RuntimeException when fallback export fails.
 */
function documentSigningWritePdfViaTempFile(mixed $pdfDoc): string
{
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'staffease-sign-' . bin2hex(random_bytes(6));
    if (!@mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
        throw new RuntimeException('Unable to create temporary directory for PDF signing');
    }

    $outputPdf = $tempDir . '/signed-output.pdf';

    try {
        $pdfForExport = clone $pdfDoc;
        $pdfForExport->setFirstIterator();
        $pdfForExport->setImageFormat('pdf');
        $pdfForExport->writeImages($outputPdf, true);
        $pdfForExport->clear();
        $pdfForExport->destroy();

        if (!is_file($outputPdf)) {
            throw new RuntimeException('Temporary PDF export did not generate an output file');
        }

        $signedPdf = @file_get_contents($outputPdf);
        if (!is_string($signedPdf) || $signedPdf === '') {
            throw new RuntimeException('Unable to read temporary PDF output');
        }

        return $signedPdf;
    } finally {
        if (file_exists($outputPdf)) {
            @unlink($outputPdf);
        }
        if (is_dir($tempDir)) {
            @rmdir($tempDir);
        }
    }
}

/**
 * Apply a drawn signature stamp onto a PDF or image document in-place using Imagick.
 *
 * @param string $sourceBlob      Raw bytes of the source document.
 * @param string $sourceMimeType  MIME type of the source document (e.g. 'application/pdf').
 * @param string $signatureData   Base64 data-URI of the drawn signature (PNG).
 * @param float  $posX            Horizontal position as a percentage (4–96).
 * @param float  $posY            Vertical position as a percentage (4–96).
 * @param int    $page            1-based page number for PDFs.
 * @param string $signerName      Display name of the signer (used in the annotation text).
 * @param string $signedAt        Formatted timestamp string (used in the annotation text).
 *
 * @return array{blob: string, mime_type: string, page: int}
 * @throws RuntimeException when Imagick is not available or signing fails.
 */
function documentSigningApply(
    string $sourceBlob,
    string $sourceMimeType,
    string $signatureData,
    float $posX,
    float $posY,
    int $page,
    string $signerName,
    string $signedAt
): array {
    if (!class_exists('Imagick')) {
        throw new RuntimeException('Imagick extension is required for in-place document signing');
    }

    $compositeOver = defined('\\Imagick::COMPOSITE_OVER') ? (int) constant('\\Imagick::COMPOSITE_OVER') : 40;

    $posX = max(4.0, min(96.0, $posX));
    $posY = max(4.0, min(96.0, $posY));
    $page = max(1, $page);

    // Decode the signature image from its data-URI.
    $signaturePayload = preg_replace('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', '', $signatureData);
    $signaturePayload = str_replace(' ', '+', (string) $signaturePayload);
    $signatureBinary = base64_decode($signaturePayload, true);
    if (!is_string($signatureBinary) || $signatureBinary === '') {
        throw new RuntimeException('Invalid signature payload');
    }

    $signatureStamp = new Imagick();
    $signatureStamp->readImageBlob($signatureBinary);
    $signatureStamp->setImageFormat('png');

    $stampDraw = new ImagickDraw();
    $stampDraw->setFillColor(new ImagickPixel('rgba(31,41,55,0.92)'));
    $stampDraw->setFontSize(16);

    $signedBlob = '';
    $signedMimeType = $sourceMimeType;
    $appliedPage = $page;

    try {
        if (str_contains($sourceMimeType, 'pdf')) {
            $pdfDoc = new Imagick();
            $pdfDoc->setResolution(150, 150);
            $pdfDoc->readImageBlob($sourceBlob);

            $pagesCount = max(1, (int) $pdfDoc->getNumberImages());
            $pageIndex = min($pagesCount - 1, max(0, $page - 1));
            $appliedPage = $pageIndex + 1;
            $pdfDoc->setIteratorIndex($pageIndex);

            $pageWidth = max(1, (int) $pdfDoc->getImageWidth());
            $pageHeight = max(1, (int) $pdfDoc->getImageHeight());

            $targetStampWidth = max(36, (int) round($pageWidth * 0.055));
            $pageStamp = documentSigningBuildStamp($signatureStamp, $targetStampWidth);

            $stampWidth = max(1, (int) $pageStamp->getImageWidth());
            $stampHeight = max(1, (int) $pageStamp->getImageHeight());
            $stampX = max(4, min($pageWidth - $stampWidth - 4, (int) round(($posX / 100) * $pageWidth - ($stampWidth / 2))));
            $stampY = max(4, min($pageHeight - $stampHeight - 56, (int) round(($posY / 100) * $pageHeight - ($stampHeight / 2))));

            $pdfDoc->compositeImage($pageStamp, $compositeOver, $stampX, $stampY);
            try {
                $pdfDoc->annotateImage($stampDraw, $stampX, min($pageHeight - 10, $stampY + $stampHeight + 22), 0, 'Firma digitale: ' . $signerName);
                $pdfDoc->annotateImage($stampDraw, $stampX, min($pageHeight - 10, $stampY + $stampHeight + 42), 0, 'Data/Ora: ' . $signedAt . ' - Pagina ' . $appliedPage);
            } catch (Throwable $annotationError) {
                // Some runtimes do not have a default font configured for Imagick.
                // Keep the visual signature and persist metadata on the document row.
            }

            try {
                $pdfDoc->setFirstIterator();
                $pdfDoc->setImageFormat('pdf');
                $signedBlob = (string) $pdfDoc->writeImagesBlob();
            } catch (Throwable $writeError) {
                $pdfDoc->setFirstIterator();
                $signedBlob = documentSigningWritePdfViaTempFile($pdfDoc);
            }
            $signedMimeType = 'application/pdf';

            $pageStamp->clear();
            $pageStamp->destroy();
            $pdfDoc->clear();
            $pdfDoc->destroy();
        } elseif (str_starts_with($sourceMimeType, 'image/')) {
            $imageDoc = new Imagick();
            $imageDoc->readImageBlob($sourceBlob);
            if ($imageDoc->getNumberImages() > 1) {
                $imageDoc->setIteratorIndex(0);
            }

            $imgWidth = max(1, (int) $imageDoc->getImageWidth());
            $imgHeight = max(1, (int) $imageDoc->getImageHeight());
            $targetStampWidth = max(28, (int) round($imgWidth * 0.055));

            $imageStamp = documentSigningBuildStamp($signatureStamp, $targetStampWidth);
            $stampWidth = max(1, (int) $imageStamp->getImageWidth());
            $stampHeight = max(1, (int) $imageStamp->getImageHeight());
            $stampX = max(4, min($imgWidth - $stampWidth - 4, (int) round(($posX / 100) * $imgWidth - ($stampWidth / 2))));
            $stampY = max(4, min($imgHeight - $stampHeight - 56, (int) round(($posY / 100) * $imgHeight - ($stampHeight / 2))));

            $imageDoc->compositeImage($imageStamp, $compositeOver, $stampX, $stampY);
            try {
                $imageDoc->annotateImage($stampDraw, $stampX, min($imgHeight - 10, $stampY + $stampHeight + 20), 0, 'Firma digitale: ' . $signerName . ' - ' . $signedAt);
            } catch (Throwable $annotationError) {
                // Best-effort only.
            }

            if (str_contains($sourceMimeType, 'png')) {
                $imageDoc->setImageFormat('png');
                $signedMimeType = 'image/png';
            } elseif (str_contains($sourceMimeType, 'webp')) {
                $imageDoc->setImageFormat('webp');
                $signedMimeType = 'image/webp';
            } else {
                $imageDoc->setImageFormat('jpeg');
                $signedMimeType = 'image/jpeg';
            }

            $signedBlob = (string) $imageDoc->getImageBlob();

            $imageStamp->clear();
            $imageStamp->destroy();
            $imageDoc->clear();
            $imageDoc->destroy();
        } else {
            throw new RuntimeException('Unsupported document format for in-place signing. Use PDF or image documents.');
        }
    } finally {
        $signatureStamp->clear();
        $signatureStamp->destroy();
    }

    if ($signedBlob === '') {
        throw new RuntimeException('Unable to generate signed document content');
    }

    return [
        'blob' => $signedBlob,
        'mime_type' => $signedMimeType,
        'page' => $appliedPage,
    ];
}
