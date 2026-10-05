<?php

declare(strict_types=1);

namespace Zactonz\Service;

use InvalidArgumentException;
use Zactonz\Exception\ApiException;
use Zactonz\Exception\TransportException;
use Zactonz\Http\Dispatcher;
use Zactonz\Result;

/**
 * Email checks.
 *
 * Generated from the API specifications by tools/generate.php. Edits made here are lost the next time it runs.
 */
final class Email
{
    /**
     * @internal Use the accessor on Zactonz\Client instead.
     */
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    /**
     * Audits the MX, SPF, DKIM, DMARC, MTA-STS, TLS-RPT and BIMI records of a domain and scores the result.
     *
     * @param string $domain Domain to audit. An email address is accepted and reduced to its domain.
     * @param string|list<string>|null $selectors Comma-separated DKIM selectors to check in addition to the common ones, up to 20. A list is also accepted.
     * @param bool|null $probe When true, connects to the primary MX to read its banner and STARTTLS support.
     *
     * @throws InvalidArgumentException When an argument cannot be sent as given.
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/email-domain/
     */
    public function health(
        string $domain,
        string|array|null $selectors = null,
        ?bool $probe = null,
    ): Result {
        return $this->dispatcher->result('email', 'GET', '/email/domain/', [
            'domain' => $domain,
            'selectors' => $selectors,
            'probe' => $probe,
        ]);
    }

    /**
     * Checks whether up to ten email addresses can receive mail.
     *
     * @param string|list<string> $emails One or more addresses separated by commas. At most ten per request. A list is also accepted.
     *
     * @throws InvalidArgumentException When an argument cannot be sent as given.
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/mail-verifier/
     */
    public function verify(
        string|array $emails,
    ): Result {
        return $this->dispatcher->result('mverifier', 'POST', '/mverifier/', [
            'emails' => $emails,
        ]);
    }
}
