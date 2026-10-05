<?php

declare(strict_types=1);

namespace Zactonz\Service;

use Zactonz\Exception\ApiException;
use Zactonz\Exception\TransportException;
use Zactonz\Http\Dispatcher;
use Zactonz\Result;

/**
 * Web page to Markdown.
 *
 * Generated from the API specifications by tools/generate.php. Edits made here are lost the next time it runs.
 */
final class Markdown
{
    /**
     * @internal Use the accessor on Zactonz\Client instead.
     */
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    /**
     * Converts a web page to Markdown.
     *
     * @param string $url The page to convert.
     * @param string|null $mode `article` (default) keeps the main content. `full` converts the whole body.
     * @param bool|null $render When true, loads the page in a headless browser first.
     * @param bool|null $images When false, drops images from the output.
     * @param bool|null $links When false, replaces links with their text.
     * @param int|null $maxChars Maximum Markdown length, 1000 to 500000.
     * @param bool|null $fresh When true, bypasses the cache.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/markdown/
     */
    public function fromUrl(
        string $url,
        ?string $mode = null,
        ?bool $render = null,
        ?bool $images = null,
        ?bool $links = null,
        ?int $maxChars = null,
        ?bool $fresh = null,
    ): Result {
        return $this->dispatcher->result('markdown', 'GET', '/markdown/', [
            'url' => $url,
            'mode' => $mode,
            'render' => $render,
            'images' => $images,
            'links' => $links,
            'max_chars' => $maxChars,
            'fresh' => $fresh,
            'format' => 'json',
        ]);
    }

    /**
     * Converts HTML you already hold to Markdown.
     *
     * @param string $html The HTML to convert, up to 2 MB.
     * @param string|null $mode `article` (default) keeps the main content. `full` converts the whole body.
     * @param bool|null $images When false, drops images from the output.
     * @param bool|null $links When false, replaces links with their text.
     * @param int|null $maxChars Maximum Markdown length, 1000 to 500000.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/markdown/
     */
    public function fromHtml(
        string $html,
        ?string $mode = null,
        ?bool $images = null,
        ?bool $links = null,
        ?int $maxChars = null,
    ): Result {
        return $this->dispatcher->result('markdown', 'POST', '/markdown/', [
            'html' => $html,
            'mode' => $mode,
            'images' => $images,
            'links' => $links,
            'max_chars' => $maxChars,
            'format' => 'json',
        ]);
    }
}
