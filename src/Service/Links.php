<?php

declare(strict_types=1);

namespace Zactonz\Service;

use Zactonz\Exception\ApiException;
use Zactonz\Exception\TransportException;
use Zactonz\Http\Dispatcher;
use Zactonz\Result;

/**
 * Link previews.
 *
 * Generated from the API specifications by tools/generate.php. Edits made here are lost the next time it runs.
 */
final class Links
{
    /**
     * @internal Use the accessor on Zactonz\Client instead.
     */
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    /**
     * Reads the title, description, image, favicon and feeds of a public URL.
     *
     * @param string $url The page to preview. `https://` is assumed when the scheme is omitted.
     * @param bool|null $render When true, loads the page in a headless browser before reading its tags.
     * @param bool|null $verifyImage When true, downloads the preview image to confirm it exists and to report its real dimensions and type.
     * @param bool|null $fresh When true, bypasses the one-hour cache.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/link-preview/
     */
    public function preview(
        string $url,
        ?bool $render = null,
        ?bool $verifyImage = null,
        ?bool $fresh = null,
    ): Result {
        return $this->dispatcher->result('unfurl', 'GET', '/unfurl/', [
            'url' => $url,
            'render' => $render,
            'verify_image' => $verifyImage,
            'fresh' => $fresh,
        ]);
    }
}
