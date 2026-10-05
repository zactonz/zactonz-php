<?php

declare(strict_types=1);

namespace Zactonz\Service;

use Zactonz\Exception\ApiException;
use Zactonz\Exception\TransportException;
use Zactonz\File;
use Zactonz\Http\Dispatcher;
use Zactonz\Result;

/**
 * Screenshots and PDFs.
 *
 * Generated from the API specifications by tools/generate.php. Edits made here are lost the next time it runs.
 */
final class Screenshot
{
    /**
     * @internal Use the accessor on Zactonz\Client instead.
     */
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    /**
     * Renders a web page to an image or a PDF and returns its hosted link.
     *
     * @param string $url Absolute URL of the page to capture.
     * @param int|null $width Viewport width in pixels, 200 to 3840. Values outside the range are clamped. Default 1280.
     * @param int|null $height Viewport height in pixels, 150 to 4320. Values outside the range are clamped. Default 1024.
     * @param string|null $format Output type. `jpeg` (default), `png`, `webp`, or `pdf` for a paged document.
     * @param int|null $quality JPEG quality from 1 to 100. Default 100. Ignored for other formats.
     * @param int|null $delay Seconds to wait after the page has loaded before capturing, 0 to 15. Default 5.
     * @param string|null $orientation PDF only. One of `portrait`, `landscape`.
     * @param int|float|null $paperWidth PDF only. Paper width in inches.
     * @param int|float|null $paperHeight PDF only. Paper height in inches.
     * @param int|float|null $marginTop PDF only. Top margin in inches, 0 to 5. Default 0.4.
     * @param int|float|null $scale PDF only. Render scale.
     * @param int|float|null $marginBottom PDF only. Bottom margin in inches, 0 to 5. Default 0.4.
     * @param int|float|null $marginLeft PDF only. Left margin in inches, 0 to 5. Default 0.4.
     * @param int|float|null $marginRight PDF only. Right margin in inches, 0 to 5. Default 0.4.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/screenshot-url/
     */
    public function capture(
        string $url,
        ?int $width = null,
        ?int $height = null,
        ?string $format = null,
        ?int $quality = null,
        ?int $delay = null,
        ?string $orientation = null,
        int|float|null $paperWidth = null,
        int|float|null $paperHeight = null,
        int|float|null $marginTop = null,
        int|float|null $scale = null,
        int|float|null $marginBottom = null,
        int|float|null $marginLeft = null,
        int|float|null $marginRight = null,
    ): Result {
        return $this->dispatcher->result('screen', 'POST', '/screen/url/', [
            'url' => $url,
            'width' => $width,
            'height' => $height,
            'format' => $format,
            'quality' => $quality,
            'delay' => $delay,
            'orientation' => $orientation,
            'paperWidth' => $paperWidth,
            'paperHeight' => $paperHeight,
            'marginTop' => $marginTop,
            'scale' => $scale,
            'marginBottom' => $marginBottom,
            'marginLeft' => $marginLeft,
            'marginRight' => $marginRight,
        ]);
    }

    /**
     * Renders a web page to an image or a PDF and returns the file.
     *
     * @param string $url Absolute URL of the page to capture.
     * @param int|null $width Viewport width in pixels, 200 to 3840. Values outside the range are clamped. Default 1280.
     * @param int|null $height Viewport height in pixels, 150 to 4320. Values outside the range are clamped. Default 1024.
     * @param string|null $format Output type. `jpeg` (default), `png`, `webp`, or `pdf` for a paged document.
     * @param int|null $quality JPEG quality from 1 to 100. Default 100. Ignored for other formats.
     * @param int|null $delay Seconds to wait after the page has loaded before capturing, 0 to 15. Default 5.
     * @param string|null $orientation PDF only. One of `portrait`, `landscape`.
     * @param int|float|null $paperWidth PDF only. Paper width in inches.
     * @param int|float|null $paperHeight PDF only. Paper height in inches.
     * @param int|float|null $marginTop PDF only. Top margin in inches, 0 to 5. Default 0.4.
     * @param int|float|null $scale PDF only. Render scale.
     * @param int|float|null $marginBottom PDF only. Bottom margin in inches, 0 to 5. Default 0.4.
     * @param int|float|null $marginLeft PDF only. Left margin in inches, 0 to 5. Default 0.4.
     * @param int|float|null $marginRight PDF only. Right margin in inches, 0 to 5. Default 0.4.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/screenshot-url/
     */
    public function captureFile(
        string $url,
        ?int $width = null,
        ?int $height = null,
        ?string $format = null,
        ?int $quality = null,
        ?int $delay = null,
        ?string $orientation = null,
        int|float|null $paperWidth = null,
        int|float|null $paperHeight = null,
        int|float|null $marginTop = null,
        int|float|null $scale = null,
        int|float|null $marginBottom = null,
        int|float|null $marginLeft = null,
        int|float|null $marginRight = null,
    ): File {
        return $this->dispatcher->file('screen', 'POST', '/screen/url/', [
            'url' => $url,
            'width' => $width,
            'height' => $height,
            'format' => $format,
            'quality' => $quality,
            'delay' => $delay,
            'orientation' => $orientation,
            'paperWidth' => $paperWidth,
            'paperHeight' => $paperHeight,
            'marginTop' => $marginTop,
            'scale' => $scale,
            'marginBottom' => $marginBottom,
            'marginLeft' => $marginLeft,
            'marginRight' => $marginRight,
            'download' => '1',
        ]);
    }

    /**
     * Renders an HTML document to an image or a PDF and returns its hosted link.
     *
     * @param string $html The HTML document to render, up to 2 MB. Larger bodies are rejected with `413`.
     * @param int|null $width Viewport width in pixels, 200 to 3840. Values outside the range are clamped. Default 1280.
     * @param int|null $height Viewport height in pixels, 150 to 4320. Values outside the range are clamped. Default 1024.
     * @param string|null $format Output type. `jpeg` (default), `png`, `webp`, or `pdf` for a paged document.
     * @param int|null $quality JPEG quality from 1 to 100. Default 100. Ignored for other formats.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/screenshot-html/
     */
    public function captureHtml(
        string $html,
        ?int $width = null,
        ?int $height = null,
        ?string $format = null,
        ?int $quality = null,
    ): Result {
        return $this->dispatcher->result('screen', 'POST', '/screen/html/', [
            'html' => $html,
            'width' => $width,
            'height' => $height,
            'format' => $format,
            'quality' => $quality,
        ]);
    }
}
