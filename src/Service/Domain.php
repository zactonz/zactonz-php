<?php

declare(strict_types=1);

namespace Zactonz\Service;

use InvalidArgumentException;
use Zactonz\Exception\ApiException;
use Zactonz\Exception\TransportException;
use Zactonz\Http\Dispatcher;
use Zactonz\Result;

/**
 * Domains: SSL, DNS and WHOIS.
 *
 * Generated from the API specifications by tools/generate.php. Edits made here are lost the next time it runs.
 */
final class Domain
{
    /**
     * @internal Use the accessor on Zactonz\Client instead.
     */
    public function __construct(private readonly Dispatcher $dispatcher)
    {
    }

    /**
     * Inspects the certificate, chain, expiry and TLS details of a host.
     *
     * @param string $host Host name, or a URL from which the host is taken.
     * @param int|null $port TLS port: 443, 8443, 465, 993, 995, 636 or 5061. One of `443`, `8443`, `465`, `993`, `995`, `636`, `5061`.
     * @param bool|null $fresh When true, bypasses the cache.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/domain-ssl/
     */
    public function ssl(
        string $host,
        ?int $port = null,
        ?bool $fresh = null,
    ): Result {
        return $this->dispatcher->result('domain', 'GET', '/domain/ssl/', [
            'host' => $host,
            'port' => $port,
            'fresh' => $fresh,
        ]);
    }

    /**
     * Looks up DNS records, with DNSSEC status.
     *
     * @param string $name Host or domain name.
     * @param string|list<string>|null $type Comma-separated record types, or `ALL` for A, AAAA, MX, TXT, NS, CNAME, SOA and CAA. A list is also accepted.
     *
     * @throws InvalidArgumentException When an argument cannot be sent as given.
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/domain-dns/
     */
    public function dns(
        string $name,
        string|array|null $type = null,
    ): Result {
        return $this->dispatcher->result('domain', 'GET', '/domain/dns/', [
            'name' => $name,
            'type' => $type,
        ]);
    }

    /**
     * Reads the registrar, dates, status and nameservers of a domain.
     *
     * @param string $domain Domain name. A host name or URL is reduced to its registrable domain.
     * @param bool|null $raw When true, includes the raw WHOIS text when the fallback service was used.
     *
     * @throws ApiException When the API refuses the request.
     * @throws TransportException When the request does not complete.
     *
     * @see https://developers.zactonz.com/apis/domain-whois/
     */
    public function whois(
        string $domain,
        ?bool $raw = null,
    ): Result {
        return $this->dispatcher->result('domain', 'GET', '/domain/whois/', [
            'domain' => $domain,
            'raw' => $raw,
        ]);
    }
}
