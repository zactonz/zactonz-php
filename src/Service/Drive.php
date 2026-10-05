<?php

declare(strict_types=1);

namespace Zactonz\Service;

use Zactonz\Exception\ApiException;
use Zactonz\Exception\TransportException;
use Zactonz\Http\Dispatcher;
use Zactonz\Result;

/**
 * Google Drive links.
 *
 * Generated from the API specifications by tools/generate.php. Edits made here are lost the next time it runs.
 */
final class Drive
{
    /**
     * @internal Use the accessor on Zactonz\Client instead.
     */
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    /**
     * Turns a Google Drive share link into a direct download URL.
     *
     * @param string $url The Google Drive share link.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/gdrive-direct-link/
     */
    public function directLink(
        string $url,
    ): Result {
        return $this->dispatcher->result('gdrive', 'GET', '/gdrive/directlink/', [
            'url' => $url,
        ]);
    }
}
