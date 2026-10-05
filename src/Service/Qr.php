<?php

declare(strict_types=1);

namespace Zactonz\Service;

use Zactonz\Exception\ApiException;
use Zactonz\Exception\TransportException;
use Zactonz\File;
use Zactonz\Http\Dispatcher;
use Zactonz\Result;

/**
 * QR codes.
 *
 * Generated from the API specifications by tools/generate.php. Edits made here are lost the next time it runs.
 */
final class Qr
{
    /**
     * @internal Use the accessor on Zactonz\Client instead.
     */
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    /**
     * Generates a QR code and returns its hosted link, or the 0/1 matrix itself for the `text` format.
     *
     * @param string $content The text or URL to encode, up to 4000 characters.
     * @param string|null $format `png` (default), `jpg`, `svg`, or `text` for a 0/1 matrix in JSON.
     * @param int|null $size Pixels per QR module, from `1` to `20`.
     * @param string|null $accuracy Error-correction level. Higher levels survive more damage but produce denser codes. One of `low`, `normal`, `good`, `high`.
     * @param int|null $padding Quiet zone around the code in modules, 0 to 20. Default 2.
     * @param string|null $color Foreground colour as six hex digits, for example `ff6700`, or a decimal RGB integer. Default black.
     * @param string|null $bgcolor Background colour as six hex digits, for example `ff6700`, or a decimal RGB integer. Default white.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/qr-encoder/
     */
    public function encode(
        string $content,
        ?string $format = null,
        ?int $size = null,
        ?string $accuracy = null,
        ?int $padding = null,
        ?string $color = null,
        ?string $bgcolor = null,
    ): Result {
        return $this->dispatcher->result('qr', 'GET', '/qr/enc/', [
            'content' => $content,
            'format' => $format,
            'size' => $size,
            'accuracy' => $accuracy,
            'padding' => $padding,
            'color' => $color,
            'bgcolor' => $bgcolor,
            'resp' => 'json',
        ]);
    }

    /**
     * Generates a QR code and returns the image.
     *
     * @param string $content The text or URL to encode, up to 4000 characters.
     * @param string|null $format `png` (default), `jpg` or `svg`.
     * @param int|null $size Pixels per QR module, from `1` to `20`.
     * @param string|null $accuracy Error-correction level. Higher levels survive more damage but produce denser codes. One of `low`, `normal`, `good`, `high`.
     * @param int|null $padding Quiet zone around the code in modules, 0 to 20. Default 2.
     * @param string|null $color Foreground colour as six hex digits, for example `ff6700`, or a decimal RGB integer. Default black.
     * @param string|null $bgcolor Background colour as six hex digits, for example `ff6700`, or a decimal RGB integer. Default white.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/qr-encoder/
     */
    public function encodeFile(
        string $content,
        ?string $format = null,
        ?int $size = null,
        ?string $accuracy = null,
        ?int $padding = null,
        ?string $color = null,
        ?string $bgcolor = null,
    ): File {
        return $this->dispatcher->file('qr', 'GET', '/qr/enc/', [
            'content' => $content,
            'format' => $format,
            'size' => $size,
            'accuracy' => $accuracy,
            'padding' => $padding,
            'color' => $color,
            'bgcolor' => $bgcolor,
            'resp' => 'image',
        ]);
    }

    /**
     * Reads the contents of a QR code from an image.
     *
     * @param string $image A link to the image, or the base64-encoded image bytes when `format` is `base64`. A `data:image/...;base64,` prefix is accepted.
     * @param string|null $format How `image` is given: `url` for a link to the image, `base64` for the image bytes.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/qr-decoder/
     */
    public function decode(
        string $image,
        ?string $format = null,
    ): Result {
        return $this->dispatcher->result('qr', 'POST', '/qr/dec/', [
            'image' => $image,
            'format' => $format,
        ]);
    }
}
