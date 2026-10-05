<?php

declare(strict_types=1);

namespace Zactonz\Service;

use Zactonz\Exception\ApiException;
use Zactonz\Exception\TransportException;
use Zactonz\File;
use Zactonz\Http\Dispatcher;
use Zactonz\Result;

/**
 * Barcodes.
 *
 * Generated from the API specifications by tools/generate.php. Edits made here are lost the next time it runs.
 */
final class Barcode
{
    /**
     * @internal Use the accessor on Zactonz\Client instead.
     */
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    /**
     * Renders a barcode and returns a hosted link with its dimensions.
     *
     * @param string $content The value to encode. Each symbology has its own alphabet and length, listed under the `type` parameter.
     * @param string|null $type `code128` (printable ASCII, up to 80 characters), `code39` (digits, capitals and `- . $ / + %`), `code93`, `ean13` (12 digits, or 13 with the check digit), `ean8` (7 or 8 digits), `upca` (11 or 12 digits), `upce` (6 to 8 digits), `itf14` (13 or 14 digits), `codabar` (a start and stop letter A to D around digits).
     * @param string|null $format `png` (default), `svg` or `jpg`.
     * @param int|null $scale Width of the narrowest bar in pixels, 1 to 10.
     * @param int|null $height Bar height in pixels, 20 to 300.
     * @param string|null $color Bar colour as six hex digits.
     * @param string|null $bgcolor Background colour as six hex digits.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/barcode-encoder/
     */
    public function encode(
        string $content,
        ?string $type = null,
        ?string $format = null,
        ?int $scale = null,
        ?int $height = null,
        ?string $color = null,
        ?string $bgcolor = null,
    ): Result {
        return $this->dispatcher->result('barcode', 'GET', '/barcode/', [
            'content' => $content,
            'type' => $type,
            'format' => $format,
            'scale' => $scale,
            'height' => $height,
            'color' => $color,
            'bgcolor' => $bgcolor,
            'resp' => 'json',
        ]);
    }

    /**
     * Renders a barcode and returns the image.
     *
     * @param string $content The value to encode. Each symbology has its own alphabet and length, listed under the `type` parameter.
     * @param string|null $type `code128` (printable ASCII, up to 80 characters), `code39` (digits, capitals and `- . $ / + %`), `code93`, `ean13` (12 digits, or 13 with the check digit), `ean8` (7 or 8 digits), `upca` (11 or 12 digits), `upce` (6 to 8 digits), `itf14` (13 or 14 digits), `codabar` (a start and stop letter A to D around digits).
     * @param string|null $format `png` (default), `svg` or `jpg`.
     * @param int|null $scale Width of the narrowest bar in pixels, 1 to 10.
     * @param int|null $height Bar height in pixels, 20 to 300.
     * @param string|null $color Bar colour as six hex digits.
     * @param string|null $bgcolor Background colour as six hex digits.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/barcode-encoder/
     */
    public function encodeFile(
        string $content,
        ?string $type = null,
        ?string $format = null,
        ?int $scale = null,
        ?int $height = null,
        ?string $color = null,
        ?string $bgcolor = null,
    ): File {
        return $this->dispatcher->file('barcode', 'GET', '/barcode/', [
            'content' => $content,
            'type' => $type,
            'format' => $format,
            'scale' => $scale,
            'height' => $height,
            'color' => $color,
            'bgcolor' => $bgcolor,
            'resp' => 'image',
        ]);
    }
}
