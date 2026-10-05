# Zactonz PHP SDK

PHP client for the [Zactonz APIs](https://developers.zactonz.com/apis/): QR codes and barcodes, screenshots and PDFs, social card images, image conversion, link previews, web page to Markdown, SSL, DNS and WHOIS lookups, email checks and translation.

- PHP 8.1 or newer with the `curl` and `json` extensions. No other dependencies.
- Typed, named arguments for every endpoint, with the API's own parameter descriptions in your editor.
- Exceptions for every failure, automatic handling of rate limits, and a fake transport for your tests.

## Installation

```bash
composer require zactonz/zactonz-php
```

## Usage

Create a key in the [API console](https://developers.zactonz.com/console/), then:

```php
use Zactonz\Client;

$zactonz = new Client('zk_qr_your_key');

$qr = $zactonz->qr()->encode(content: 'https://zactonz.com', size: 6);

echo $qr['qr'];
```

Arguments are passed by name. One you leave out is not sent, so the API applies its default.

```php
$page = $zactonz->markdown()->fromUrl(url: 'https://example.com/article', maxChars: 20000);
echo $page['markdown'];

$records = $zactonz->domain()->dns(name: 'example.com', type: ['A', 'MX', 'TXT']);
$days    = $zactonz->domain()->ssl(host: 'example.com')['days_remaining'];

$checked = $zactonz->email()->verify(emails: ['jane@example.com', 'sales@example.com']);
```

### Services

| Accessor | Methods | Key product |
|---|---|---|
| `qr()` | `encode`, `encodeFile`, `decode` | `qr` |
| `barcode()` | `encode`, `encodeFile` | `barcode` |
| `screenshot()` | `capture`, `captureFile`, `captureHtml` | `screen` |
| `og()` | `generate`, `generateFile` | `og` |
| `image()` | `convert`, `convertFile`, `info` | `image` |
| `links()` | `preview` | `unfurl` |
| `markdown()` | `fromUrl`, `fromHtml` | `markdown` |
| `domain()` | `ssl`, `dns`, `whois` | `domain` |
| `email()` | `health`, `verify` | `email`, `mverifier` |
| `translator()` | `translate` | `translator` |
| `drive()` | `directLink` | `gdrive` |

[docs/reference.md](docs/reference.md) lists every method with its arguments. The [developer portal](https://developers.zactonz.com/apis/) documents each endpoint's response.

## API keys

Each Zactonz key works for one product, and the product is part of the key: `zk_qr_…` is a QR key, `zk_screen_…` a screenshot key. Pass all the keys you use and the client picks the right one for each call.

```php
$zactonz = new Client(['zk_qr_…', 'zk_screen_…', 'zk_domain_…']);
```

To keep keys out of your code, put them in `ZACTONZ_API_KEYS`, separated by commas:

```php
$zactonz = Client::fromEnvironment();
```

The "Key product" column above is the name inside the key. Use it with `hasKeyFor()` and `keyStatus()`:

```php
if ($zactonz->hasKeyFor('screen')) {
    $left = $zactonz->keyStatus('screen')->get('quota.day.remaining');
}
```

Calling a product without a key throws `MissingKeyException` before any request is made. Key values are hidden from `var_dump()` and stack traces. Keep them on the server; do not ship them to a browser.

## Results

Methods return a `Zactonz\Result`.

```php
$preview = $zactonz->links()->preview(url: 'https://zactonz.com');

$preview['title'];                   // a field
$preview->get('image.url');          // a nested field, null when absent
$preview->data;                      // the whole payload
$preview->rateLimit->remaining;      // requests left this minute
$preview->rateLimit->dayRemaining;   // units left today
$preview->requestId();               // for support requests
```

Most endpoints return an object, and its fields are what you read. Three return a single value instead, available as `$result->data`: `screenshot()` methods return a link (with `width` and `height` readable as fields), `qr()->decode()` returns the decoded text, and `drive()->directLink()` returns a URL.

## Files

A method whose name ends in `File` returns the bytes as a `Zactonz\File` instead of a link.

```php
$zactonz->barcode()->encodeFile(content: 'ZCTZ-0042')->save('label.png');

$card = $zactonz->og()->generateFile(title: 'Ship faster', theme: 'dark');
$card->contentType;   // image/png
$card->dataUri();     // for an <img src>
```

`download()` fetches a link from a result:

```php
$pdf = $zactonz->screenshot()->captureHtml(html: $invoiceHtml, format: 'pdf');
$zactonz->download($pdf->data)->save('invoice.pdf');
```

To upload a local image, pass its path:

```php
$zactonz->image()->convertFile(file: 'photo.jpg', format: 'webp', width: 800)->save('photo.webp');
```

Links to generated files are public to anyone who has them and expire. How long each lasts is on the endpoint's reference page.

## Errors

| Exception | Meaning |
|---|---|
| `AuthenticationException` | The key is missing, unknown or expired. |
| `PermissionException` | The key belongs to another product, or the account is suspended. |
| `InvalidRequestException` | The request was understood and refused: a missing argument, an unreachable URL, a file that is too large. |
| `RateLimitException` | A rate limit or quota was reached. `$e->retryAfter` holds the seconds to wait, when known. |
| `ServerException` | The API or a service it depends on failed. |
| `UnexpectedResponseException` | The response was not what the endpoint documents. |
| `ConnectionException` | The API could not be reached. Nothing was sent. |
| `TimeoutException` | No response arrived in time. The request may still have been processed. |
| `ConfigurationException` | A key is missing (`MissingKeyException`) or malformed (`InvalidKeyException`). |

The first six extend `ApiException`, which carries `status`, the decoded `body`, the response `headers` and `requestId()`. `ConnectionException` and `TimeoutException` extend `TransportException`. All of them extend `ZactonzException`. Passing an argument that cannot be sent, such as a path that does not exist, throws PHP's `InvalidArgumentException`.

```php
use Zactonz\Exception\ApiException;
use Zactonz\Exception\RateLimitException;

try {
    $shot = $zactonz->screenshot()->capture(url: 'https://example.com');
} catch (RateLimitException $e) {
    $retryIn = $e->retryAfter;
} catch (ApiException $e) {
    error_log(sprintf('%d %s (%s)', $e->status, $e->getMessage(), $e->requestId()));
}
```

Some endpoints report a problem with the input using status `401` or `403` in the reply body, for example a QR image that cannot be loaded. Those are `InvalidRequestException`. `AuthenticationException` and `PermissionException` are reserved for the key itself.

## Retries and timeouts

A request is sent again, up to `maxRetries` times (default 2), only when doing so cannot repeat work:

- after a `429`, once the `Retry-After` period has passed;
- after a `502`, `503` or `504`, for `GET` requests;
- when the connection could not be made;
- once, ten seconds later, when the API's request burst limit rejected the call (more than 20 requests to one endpoint within two seconds).

One call waits at most 30 seconds in total between attempts. A longer `Retry-After`, such as a spent daily quota, is thrown as `RateLimitException` without waiting. Timeouts are never retried.

The default timeout is 90 seconds, because rendering a page or verifying an address can take most of a minute.

```php
$zactonz = new Client($keys, timeout: 30, maxRetries: 0);
```

## Signed image URLs

An `og:image` tag cannot send an `Authorization` header, so the OG Image endpoint also accepts a signed URL. `SignedUrl::og()` builds one from the key id and signing secret shown in the console. No request is made until something fetches the image.

```php
use Zactonz\SignedUrl;

$url = SignedUrl::og($keyId, $secret, [
    'title'    => $post->title,
    'subtitle' => $post->excerpt,
    'theme'    => 'dark',
]);
```

## Testing your code

`Zactonz\Testing\FakeTransport` replaces the network. Queue the responses your code should receive and inspect the requests it made.

```php
use Zactonz\Client;
use Zactonz\Testing\FakeTransport;

$transport = new FakeTransport(
    FakeTransport::json(['status' => 200, 'data' => ['qr' => 'https://api.zactonz.com/qr/enc/i/x.png']]),
);
$zactonz = new Client('zk_qr_test', maxRetries: 0, transport: $transport);

$zactonz->qr()->encode(content: 'hello');

$transport->lastRequest()->url;   // https://api.zactonz.com/qr/enc/?content=hello&resp=json
```

## Custom HTTP client

Requests go through `Zactonz\Http\Transport`, a one-method interface. Implement it to use your own HTTP client, proxy or logging, and pass it as `transport`. An implementation must not follow redirects.

## Versioning

This library follows [semantic versioning](https://semver.org/). While it is at `0.x`, a minor release may change the public API; such changes are listed in the [changelog](CHANGELOG.md). The public API is everything documented here except classes marked `@internal`.

Supported PHP versions are those that still receive security fixes from the PHP project, and 8.1 for as long as it stays practical.

## Contributing and security

See [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities privately as described in [SECURITY.md](SECURITY.md).

## Licence

MIT. See [LICENSE](LICENSE). Maintained by [Zactonz Technologies](https://zactonz.com).
