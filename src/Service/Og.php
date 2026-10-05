<?php

declare(strict_types=1);

namespace Zactonz\Service;

use Zactonz\Exception\ApiException;
use Zactonz\Exception\TransportException;
use Zactonz\File;
use Zactonz\Http\Dispatcher;
use Zactonz\Result;

/**
 * Social card images.
 *
 * Generated from the API specifications by tools/generate.php. Edits made here are lost the next time it runs.
 */
final class Og
{
    /**
     * @internal Use the accessor on Zactonz\Client instead.
     */
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    /**
     * Generates a social card image and returns a hosted link, valid for seven days.
     *
     * @param string $title Main text, up to 120 characters.
     * @param string|null $subtitle Secondary line, up to 200 characters.
     * @param string|null $site Short label shown at the bottom, for example your domain, up to 60 characters.
     * @param string|null $logo Public URL of a PNG, JPEG, WebP, GIF or SVG logo, up to 512 KB.
     * @param string|null $template `card` (accent bar and logo), `minimal` (centred) or `split` (text beside an accent panel).
     * @param string|null $theme `light` or `dark`.
     * @param string|null $accent Accent colour as six hex digits.
     * @param string|null $bg Background colour as six hex digits, overriding the theme.
     * @param string|null $size Pixel size. One of `1200x630`, `1200x600`, `1080x1080`, `1600x900`.
     * @param string|null $font `titillium`, `system` or `serif`.
     * @param string|null $format `png` (default), `jpeg` or `webp`.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/og-image/
     */
    public function generate(
        string $title,
        ?string $subtitle = null,
        ?string $site = null,
        ?string $logo = null,
        ?string $template = null,
        ?string $theme = null,
        ?string $accent = null,
        ?string $bg = null,
        ?string $size = null,
        ?string $font = null,
        ?string $format = null,
    ): Result {
        return $this->dispatcher->result('og', 'GET', '/og/', [
            'title' => $title,
            'subtitle' => $subtitle,
            'site' => $site,
            'logo' => $logo,
            'template' => $template,
            'theme' => $theme,
            'accent' => $accent,
            'bg' => $bg,
            'size' => $size,
            'font' => $font,
            'format' => $format,
            'resp' => 'json',
        ]);
    }

    /**
     * Generates a social card image and returns the image.
     *
     * @param string $title Main text, up to 120 characters.
     * @param string|null $subtitle Secondary line, up to 200 characters.
     * @param string|null $site Short label shown at the bottom, for example your domain, up to 60 characters.
     * @param string|null $logo Public URL of a PNG, JPEG, WebP, GIF or SVG logo, up to 512 KB.
     * @param string|null $template `card` (accent bar and logo), `minimal` (centred) or `split` (text beside an accent panel).
     * @param string|null $theme `light` or `dark`.
     * @param string|null $accent Accent colour as six hex digits.
     * @param string|null $bg Background colour as six hex digits, overriding the theme.
     * @param string|null $size Pixel size. One of `1200x630`, `1200x600`, `1080x1080`, `1600x900`.
     * @param string|null $font `titillium`, `system` or `serif`.
     * @param string|null $format `png` (default), `jpeg` or `webp`.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/og-image/
     */
    public function generateFile(
        string $title,
        ?string $subtitle = null,
        ?string $site = null,
        ?string $logo = null,
        ?string $template = null,
        ?string $theme = null,
        ?string $accent = null,
        ?string $bg = null,
        ?string $size = null,
        ?string $font = null,
        ?string $format = null,
    ): File {
        return $this->dispatcher->file('og', 'GET', '/og/', [
            'title' => $title,
            'subtitle' => $subtitle,
            'site' => $site,
            'logo' => $logo,
            'template' => $template,
            'theme' => $theme,
            'accent' => $accent,
            'bg' => $bg,
            'size' => $size,
            'font' => $font,
            'format' => $format,
            'resp' => 'image',
        ]);
    }
}
