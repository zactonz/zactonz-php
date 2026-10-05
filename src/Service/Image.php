<?php

declare(strict_types=1);

namespace Zactonz\Service;

use InvalidArgumentException;
use Zactonz\Exception\ApiException;
use Zactonz\Exception\TransportException;
use Zactonz\File;
use Zactonz\Http\Dispatcher;
use Zactonz\Result;

/**
 * Image conversion.
 *
 * Generated from the API specifications by tools/generate.php. Edits made here are lost the next time it runs.
 */
final class Image
{
    /**
     * @internal Use the accessor on Zactonz\Client instead.
     */
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    /**
     * Converts, resizes or crops an image and returns a hosted link with its metadata.
     *
     * @param string|null $file Path of a local file. The image to convert: JPEG, PNG, GIF, WebP, AVIF or BMP, up to the upload limit of your plan.
     * @param string|null $url Public URL of the image, instead of a local file.
     * @param string|null $format Output format: `webp` (default), `avif`, `jpeg`, `png`, `gif`, or `keep` to keep the source format.
     * @param int|null $quality 1 to 100 for lossy formats.
     * @param int|null $width Target width in pixels, up to 8000. Omit to derive from the height.
     * @param int|null $height Target height in pixels, up to 8000. Omit to derive from the width.
     * @param string|null $fit `inside` (default, shrink to fit, never enlarge), `cover` (fill and crop), `contain` (fit with padding, see `background`) or `fill` (stretch).
     * @param string|null $background Six hex digits used to flatten transparency and to pad `contain`. JPEG output is flattened on white when unset.
     * @param int|null $rotate Clockwise degrees, 0 to 359. EXIF orientation is always applied first.
     * @param string|null $flip `h`, `v` or `both`.
     * @param bool|null $grayscale When true, converts to grayscale.
     * @param int|null $blur Gaussian blur passes, 0 to 20.
     * @param bool|null $sharpen When true, applies a sharpening kernel.
     *
     * @throws InvalidArgumentException When an argument cannot be sent as given.
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/image-convert/
     */
    public function convert(
        ?string $file = null,
        ?string $url = null,
        ?string $format = null,
        ?int $quality = null,
        ?int $width = null,
        ?int $height = null,
        ?string $fit = null,
        ?string $background = null,
        ?int $rotate = null,
        ?string $flip = null,
        ?bool $grayscale = null,
        ?int $blur = null,
        ?bool $sharpen = null,
    ): Result {
        if ($file === null && $url === null) {
            throw new InvalidArgumentException('Give one of: file, url');
        }
        return $this->dispatcher->result('image', 'POST', '/image/', [
            'url' => $url,
            'format' => $format,
            'quality' => $quality,
            'width' => $width,
            'height' => $height,
            'fit' => $fit,
            'background' => $background,
            'rotate' => $rotate,
            'flip' => $flip,
            'grayscale' => $grayscale,
            'blur' => $blur,
            'sharpen' => $sharpen,
            'resp' => 'json',
        ], [
            'file' => $file,
        ]);
    }

    /**
     * Converts, resizes or crops an image and returns the image.
     *
     * @param string|null $file Path of a local file. The image to convert: JPEG, PNG, GIF, WebP, AVIF or BMP, up to the upload limit of your plan.
     * @param string|null $url Public URL of the image, instead of a local file.
     * @param string|null $format Output format: `webp` (default), `avif`, `jpeg`, `png`, `gif`, or `keep` to keep the source format.
     * @param int|null $quality 1 to 100 for lossy formats.
     * @param int|null $width Target width in pixels, up to 8000. Omit to derive from the height.
     * @param int|null $height Target height in pixels, up to 8000. Omit to derive from the width.
     * @param string|null $fit `inside` (default, shrink to fit, never enlarge), `cover` (fill and crop), `contain` (fit with padding, see `background`) or `fill` (stretch).
     * @param string|null $background Six hex digits used to flatten transparency and to pad `contain`. JPEG output is flattened on white when unset.
     * @param int|null $rotate Clockwise degrees, 0 to 359. EXIF orientation is always applied first.
     * @param string|null $flip `h`, `v` or `both`.
     * @param bool|null $grayscale When true, converts to grayscale.
     * @param int|null $blur Gaussian blur passes, 0 to 20.
     * @param bool|null $sharpen When true, applies a sharpening kernel.
     *
     * @throws InvalidArgumentException When an argument cannot be sent as given.
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/image-convert/
     */
    public function convertFile(
        ?string $file = null,
        ?string $url = null,
        ?string $format = null,
        ?int $quality = null,
        ?int $width = null,
        ?int $height = null,
        ?string $fit = null,
        ?string $background = null,
        ?int $rotate = null,
        ?string $flip = null,
        ?bool $grayscale = null,
        ?int $blur = null,
        ?bool $sharpen = null,
    ): File {
        if ($file === null && $url === null) {
            throw new InvalidArgumentException('Give one of: file, url');
        }
        return $this->dispatcher->file('image', 'POST', '/image/', [
            'url' => $url,
            'format' => $format,
            'quality' => $quality,
            'width' => $width,
            'height' => $height,
            'fit' => $fit,
            'background' => $background,
            'rotate' => $rotate,
            'flip' => $flip,
            'grayscale' => $grayscale,
            'blur' => $blur,
            'sharpen' => $sharpen,
            'resp' => 'image',
        ], [
            'file' => $file,
        ]);
    }

    /**
     * Reads the dimensions, format and dominant colours of an image without converting it.
     *
     * @param string|null $file Path of a local file. The image to convert: JPEG, PNG, GIF, WebP, AVIF or BMP, up to the upload limit of your plan.
     * @param string|null $url Public URL of the image, instead of a local file.
     *
     * @throws InvalidArgumentException When an argument cannot be sent as given.
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/image-convert/
     */
    public function info(
        ?string $file = null,
        ?string $url = null,
    ): Result {
        if ($file === null && $url === null) {
            throw new InvalidArgumentException('Give one of: file, url');
        }
        return $this->dispatcher->result('image', 'POST', '/image/', [
            'url' => $url,
            'info' => '1',
        ], [
            'file' => $file,
        ]);
    }
}
