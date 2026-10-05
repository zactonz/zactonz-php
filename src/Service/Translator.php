<?php

declare(strict_types=1);

namespace Zactonz\Service;

use Zactonz\Exception\ApiException;
use Zactonz\Exception\TransportException;
use Zactonz\Http\Dispatcher;
use Zactonz\Result;

/**
 * Translation.
 *
 * Generated from the API specifications by tools/generate.php. Edits made here are lost the next time it runs.
 */
final class Translator
{
    /**
     * @internal Use the accessor on Zactonz\Client instead.
     */
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    /**
     * Translates text between 46 languages.
     *
     * @param string $text The text to translate.
     * @param string $to Target language code.
     * @param string|null $from Source language code.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/translator/
     */
    public function translate(
        string $text,
        string $to,
        ?string $from = null,
    ): Result {
        return $this->dispatcher->result('translator', 'POST', '/translator/', [
            'text' => $text,
            'to' => $to,
            'from' => $from,
        ]);
    }
}
