# Method reference

Every method of the client. Arguments are passed by name, and an argument you leave out is not sent, so the API applies its own default.

A method returns a `Zactonz\Result` unless its name ends in `File`, in which case it returns a `Zactonz\File`. The full description of each endpoint, with its response fields, is linked under each method.

<!-- Generated from the API specifications by tools/generate.php. Edits made here are lost the next time it runs. -->

## QR codes

### qr()->encode()

Generates a QR code and returns its hosted link, or the 0/1 matrix itself for the `text` format.

```php
$result = $zactonz->qr()->encode(content: 'https://zactonz.com', size: 6);
```

- **Endpoint:** `GET /qr/enc/`
- **Key:** `qr`
- **Full reference:** https://developers.zactonz.com/apis/qr-encoder/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `content` | `string` | yes |  | The text or URL to encode, up to 4000 characters. |
| `format` | `string` | no | `png` | `png` (default), `jpg`, `svg`, or `text` for a 0/1 matrix in JSON. |
| `size` | `int` | no | `4` | Pixels per QR module, from `1` to `20`. |
| `accuracy` | `string` | no | `normal` | Error-correction level. Higher levels survive more damage but produce denser codes. One of `low`, `normal`, `good`, `high`. |
| `padding` | `int` | no | `2` | Quiet zone around the code in modules, 0 to 20. Default 2. |
| `color` | `string` | no | `000000` | Foreground colour as six hex digits, for example `ff6700`, or a decimal RGB integer. Default black. |
| `bgcolor` | `string` | no | `FFFFFF` | Background colour as six hex digits, for example `ff6700`, or a decimal RGB integer. Default white. |

### qr()->encodeFile()

Generates a QR code and returns the image.

```php
$file = $zactonz->qr()->encodeFile(content: 'https://zactonz.com', size: 6);
```

- **Endpoint:** `GET /qr/enc/`
- **Key:** `qr`
- **Full reference:** https://developers.zactonz.com/apis/qr-encoder/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `content` | `string` | yes |  | The text or URL to encode, up to 4000 characters. |
| `format` | `string` | no | `png` | `png` (default), `jpg` or `svg`. |
| `size` | `int` | no | `4` | Pixels per QR module, from `1` to `20`. |
| `accuracy` | `string` | no | `normal` | Error-correction level. Higher levels survive more damage but produce denser codes. One of `low`, `normal`, `good`, `high`. |
| `padding` | `int` | no | `2` | Quiet zone around the code in modules, 0 to 20. Default 2. |
| `color` | `string` | no | `000000` | Foreground colour as six hex digits, for example `ff6700`, or a decimal RGB integer. Default black. |
| `bgcolor` | `string` | no | `FFFFFF` | Background colour as six hex digits, for example `ff6700`, or a decimal RGB integer. Default white. |

### qr()->decode()

Reads the contents of a QR code from an image.

```php
$result = $zactonz->qr()->decode(image: 'https://api.zactonz.com/qr/enc/i/qDB76_184XsQzKpYx4e.png');
```

- **Endpoint:** `POST /qr/dec/`
- **Key:** `qr`
- **Full reference:** https://developers.zactonz.com/apis/qr-decoder/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `image` | `string` | yes |  | A link to the image, or the base64-encoded image bytes when `format` is `base64`. A `data:image/...;base64,` prefix is accepted. |
| `format` | `string` | no | `url` | How `image` is given: `url` for a link to the image, `base64` for the image bytes. |

## Barcodes

### barcode()->encode()

Renders a barcode and returns a hosted link with its dimensions.

```php
$result = $zactonz->barcode()->encode(content: 'ZCTZ-0042', scale: 3);
```

- **Endpoint:** `GET /barcode/`
- **Key:** `barcode`
- **Full reference:** https://developers.zactonz.com/apis/barcode-encoder/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `content` | `string` | yes |  | The value to encode. Each symbology has its own alphabet and length, listed under the `type` parameter. |
| `type` | `string` | no | `code128` | `code128` (printable ASCII, up to 80 characters), `code39` (digits, capitals and `- . $ / + %`), `code93`, `ean13` (12 digits, or 13 with the check digit), `ean8` (7 or 8 digits), `upca` (11 or 12 digits), `upce` (6 to 8 digits), `itf14` (13 or 14 digits), `codabar` (a start and stop letter A to D around digits). |
| `format` | `string` | no | `png` | `png` (default), `svg` or `jpg`. |
| `scale` | `int` | no | `2` | Width of the narrowest bar in pixels, 1 to 10. |
| `height` | `int` | no | `60` | Bar height in pixels, 20 to 300. |
| `color` | `string` | no | `000000` | Bar colour as six hex digits. |
| `bgcolor` | `string` | no | `ffffff` | Background colour as six hex digits. |

### barcode()->encodeFile()

Renders a barcode and returns the image.

```php
$file = $zactonz->barcode()->encodeFile(content: 'ZCTZ-0042', scale: 3);
```

- **Endpoint:** `GET /barcode/`
- **Key:** `barcode`
- **Full reference:** https://developers.zactonz.com/apis/barcode-encoder/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `content` | `string` | yes |  | The value to encode. Each symbology has its own alphabet and length, listed under the `type` parameter. |
| `type` | `string` | no | `code128` | `code128` (printable ASCII, up to 80 characters), `code39` (digits, capitals and `- . $ / + %`), `code93`, `ean13` (12 digits, or 13 with the check digit), `ean8` (7 or 8 digits), `upca` (11 or 12 digits), `upce` (6 to 8 digits), `itf14` (13 or 14 digits), `codabar` (a start and stop letter A to D around digits). |
| `format` | `string` | no | `png` | `png` (default), `svg` or `jpg`. |
| `scale` | `int` | no | `2` | Width of the narrowest bar in pixels, 1 to 10. |
| `height` | `int` | no | `60` | Bar height in pixels, 20 to 300. |
| `color` | `string` | no | `000000` | Bar colour as six hex digits. |
| `bgcolor` | `string` | no | `ffffff` | Background colour as six hex digits. |

## Screenshots and PDFs

### screenshot()->capture()

Renders a web page to an image or a PDF and returns its hosted link.

```php
$result = $zactonz->screenshot()->capture(url: 'https://zactonz.com');
```

- **Endpoint:** `POST /screen/url/`
- **Key:** `screen`
- **Full reference:** https://developers.zactonz.com/apis/screenshot-url/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `url` | `string` | yes |  | Absolute URL of the page to capture. |
| `width` | `int` | no | `1280` | Viewport width in pixels, 200 to 3840. Values outside the range are clamped. Default 1280. |
| `height` | `int` | no | `1024` | Viewport height in pixels, 150 to 4320. Values outside the range are clamped. Default 1024. |
| `format` | `string` | no | `jpeg` | Output type. `jpeg` (default), `png`, `webp`, or `pdf` for a paged document. |
| `quality` | `int` | no | `100` | JPEG quality from 1 to 100. Default 100. Ignored for other formats. |
| `delay` | `int` | no | `5` | Seconds to wait after the page has loaded before capturing, 0 to 15. Default 5. |
| `orientation` | `string` | no | `portrait` | PDF only. One of `portrait`, `landscape`. |
| `paperWidth` | `int\|float` | no | `6` | PDF only. Paper width in inches. |
| `paperHeight` | `int\|float` | no | `6` | PDF only. Paper height in inches. |
| `marginTop` | `int\|float` | no | `0.4` | PDF only. Top margin in inches, 0 to 5. Default 0.4. |
| `scale` | `int\|float` | no | `1` | PDF only. Render scale. |
| `marginBottom` | `int\|float` | no |  | PDF only. Bottom margin in inches, 0 to 5. Default 0.4. |
| `marginLeft` | `int\|float` | no |  | PDF only. Left margin in inches, 0 to 5. Default 0.4. |
| `marginRight` | `int\|float` | no |  | PDF only. Right margin in inches, 0 to 5. Default 0.4. |

### screenshot()->captureFile()

Renders a web page to an image or a PDF and returns the file.

```php
$file = $zactonz->screenshot()->captureFile(url: 'https://zactonz.com');
```

- **Endpoint:** `POST /screen/url/`
- **Key:** `screen`
- **Full reference:** https://developers.zactonz.com/apis/screenshot-url/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `url` | `string` | yes |  | Absolute URL of the page to capture. |
| `width` | `int` | no | `1280` | Viewport width in pixels, 200 to 3840. Values outside the range are clamped. Default 1280. |
| `height` | `int` | no | `1024` | Viewport height in pixels, 150 to 4320. Values outside the range are clamped. Default 1024. |
| `format` | `string` | no | `jpeg` | Output type. `jpeg` (default), `png`, `webp`, or `pdf` for a paged document. |
| `quality` | `int` | no | `100` | JPEG quality from 1 to 100. Default 100. Ignored for other formats. |
| `delay` | `int` | no | `5` | Seconds to wait after the page has loaded before capturing, 0 to 15. Default 5. |
| `orientation` | `string` | no | `portrait` | PDF only. One of `portrait`, `landscape`. |
| `paperWidth` | `int\|float` | no | `6` | PDF only. Paper width in inches. |
| `paperHeight` | `int\|float` | no | `6` | PDF only. Paper height in inches. |
| `marginTop` | `int\|float` | no | `0.4` | PDF only. Top margin in inches, 0 to 5. Default 0.4. |
| `scale` | `int\|float` | no | `1` | PDF only. Render scale. |
| `marginBottom` | `int\|float` | no |  | PDF only. Bottom margin in inches, 0 to 5. Default 0.4. |
| `marginLeft` | `int\|float` | no |  | PDF only. Left margin in inches, 0 to 5. Default 0.4. |
| `marginRight` | `int\|float` | no |  | PDF only. Right margin in inches, 0 to 5. Default 0.4. |

### screenshot()->captureHtml()

Renders an HTML document to an image or a PDF and returns its hosted link.

```php
$result = $zactonz->screenshot()->captureHtml(html: '<html><body><h1>Invoice #1042</h1></body></html>');
```

- **Endpoint:** `POST /screen/html/`
- **Key:** `screen`
- **Full reference:** https://developers.zactonz.com/apis/screenshot-html/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `html` | `string` | yes |  | The HTML document to render, up to 2 MB. Larger bodies are rejected with `413`. |
| `width` | `int` | no | `1280` | Viewport width in pixels, 200 to 3840. Values outside the range are clamped. Default 1280. |
| `height` | `int` | no | `1024` | Viewport height in pixels, 150 to 4320. Values outside the range are clamped. Default 1024. |
| `format` | `string` | no | `jpeg` | Output type. `jpeg` (default), `png`, `webp`, or `pdf` for a paged document. |
| `quality` | `int` | no | `100` | JPEG quality from 1 to 100. Default 100. Ignored for other formats. |

## Social card images

### og()->generate()

Generates a social card image and returns a hosted link, valid for seven days.

```php
$result = $zactonz->og()->generate(title: 'Ship faster with the Zactonz APIs');
```

- **Endpoint:** `GET /og/`
- **Key:** `og`
- **Full reference:** https://developers.zactonz.com/apis/og-image/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `title` | `string` | yes |  | Main text, up to 120 characters. |
| `subtitle` | `string` | no |  | Secondary line, up to 200 characters. |
| `site` | `string` | no |  | Short label shown at the bottom, for example your domain, up to 60 characters. |
| `logo` | `string` | no |  | Public URL of a PNG, JPEG, WebP, GIF or SVG logo, up to 512 KB. |
| `template` | `string` | no | `card` | `card` (accent bar and logo), `minimal` (centred) or `split` (text beside an accent panel). |
| `theme` | `string` | no | `light` | `light` or `dark`. |
| `accent` | `string` | no | `ff6700` | Accent colour as six hex digits. |
| `bg` | `string` | no |  | Background colour as six hex digits, overriding the theme. |
| `size` | `string` | no | `1200x630` | Pixel size. One of `1200x630`, `1200x600`, `1080x1080`, `1600x900`. |
| `font` | `string` | no | `titillium` | `titillium`, `system` or `serif`. |
| `format` | `string` | no | `png` | `png` (default), `jpeg` or `webp`. |

### og()->generateFile()

Generates a social card image and returns the image.

```php
$file = $zactonz->og()->generateFile(title: 'Ship faster with the Zactonz APIs');
```

- **Endpoint:** `GET /og/`
- **Key:** `og`
- **Full reference:** https://developers.zactonz.com/apis/og-image/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `title` | `string` | yes |  | Main text, up to 120 characters. |
| `subtitle` | `string` | no |  | Secondary line, up to 200 characters. |
| `site` | `string` | no |  | Short label shown at the bottom, for example your domain, up to 60 characters. |
| `logo` | `string` | no |  | Public URL of a PNG, JPEG, WebP, GIF or SVG logo, up to 512 KB. |
| `template` | `string` | no | `card` | `card` (accent bar and logo), `minimal` (centred) or `split` (text beside an accent panel). |
| `theme` | `string` | no | `light` | `light` or `dark`. |
| `accent` | `string` | no | `ff6700` | Accent colour as six hex digits. |
| `bg` | `string` | no |  | Background colour as six hex digits, overriding the theme. |
| `size` | `string` | no | `1200x630` | Pixel size. One of `1200x630`, `1200x600`, `1080x1080`, `1600x900`. |
| `font` | `string` | no | `titillium` | `titillium`, `system` or `serif`. |
| `format` | `string` | no | `png` | `png` (default), `jpeg` or `webp`. |

## Image conversion

### image()->convert()

Converts, resizes or crops an image and returns a hosted link with its metadata.

```php
$result = $zactonz->image()->convert(quality: 80);
```

- **Endpoint:** `POST /image/`
- **Key:** `image`
- **Full reference:** https://developers.zactonz.com/apis/image-convert/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `file` | `string` | no |  | Path of a local file. The image to convert: JPEG, PNG, GIF, WebP, AVIF or BMP, up to the upload limit of your plan. |
| `url` | `string` | no |  | Public URL of the image, instead of a local file. |
| `format` | `string` | no | `webp` | Output format: `webp` (default), `avif`, `jpeg`, `png`, `gif`, or `keep` to keep the source format. |
| `quality` | `int` | no | `82` | 1 to 100 for lossy formats. |
| `width` | `int` | no |  | Target width in pixels, up to 8000. Omit to derive from the height. |
| `height` | `int` | no |  | Target height in pixels, up to 8000. Omit to derive from the width. |
| `fit` | `string` | no | `inside` | `inside` (default, shrink to fit, never enlarge), `cover` (fill and crop), `contain` (fit with padding, see `background`) or `fill` (stretch). |
| `background` | `string` | no |  | Six hex digits used to flatten transparency and to pad `contain`. JPEG output is flattened on white when unset. |
| `rotate` | `int` | no |  | Clockwise degrees, 0 to 359. EXIF orientation is always applied first. |
| `flip` | `string` | no |  | `h`, `v` or `both`. |
| `grayscale` | `bool` | no |  | When true, converts to grayscale. |
| `blur` | `int` | no |  | Gaussian blur passes, 0 to 20. |
| `sharpen` | `bool` | no |  | When true, applies a sharpening kernel. |

### image()->convertFile()

Converts, resizes or crops an image and returns the image.

```php
$file = $zactonz->image()->convertFile(quality: 80);
```

- **Endpoint:** `POST /image/`
- **Key:** `image`
- **Full reference:** https://developers.zactonz.com/apis/image-convert/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `file` | `string` | no |  | Path of a local file. The image to convert: JPEG, PNG, GIF, WebP, AVIF or BMP, up to the upload limit of your plan. |
| `url` | `string` | no |  | Public URL of the image, instead of a local file. |
| `format` | `string` | no | `webp` | Output format: `webp` (default), `avif`, `jpeg`, `png`, `gif`, or `keep` to keep the source format. |
| `quality` | `int` | no | `82` | 1 to 100 for lossy formats. |
| `width` | `int` | no |  | Target width in pixels, up to 8000. Omit to derive from the height. |
| `height` | `int` | no |  | Target height in pixels, up to 8000. Omit to derive from the width. |
| `fit` | `string` | no | `inside` | `inside` (default, shrink to fit, never enlarge), `cover` (fill and crop), `contain` (fit with padding, see `background`) or `fill` (stretch). |
| `background` | `string` | no |  | Six hex digits used to flatten transparency and to pad `contain`. JPEG output is flattened on white when unset. |
| `rotate` | `int` | no |  | Clockwise degrees, 0 to 359. EXIF orientation is always applied first. |
| `flip` | `string` | no |  | `h`, `v` or `both`. |
| `grayscale` | `bool` | no |  | When true, converts to grayscale. |
| `blur` | `int` | no |  | Gaussian blur passes, 0 to 20. |
| `sharpen` | `bool` | no |  | When true, applies a sharpening kernel. |

### image()->info()

Reads the dimensions, format and dominant colours of an image without converting it.

```php
$result = $zactonz->image()->info(url: 'https://zactonz.com/assets/images/logo.png');
```

- **Endpoint:** `POST /image/`
- **Key:** `image`
- **Full reference:** https://developers.zactonz.com/apis/image-convert/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `file` | `string` | no |  | Path of a local file. The image to convert: JPEG, PNG, GIF, WebP, AVIF or BMP, up to the upload limit of your plan. |
| `url` | `string` | no |  | Public URL of the image, instead of a local file. |

## Link previews

### links()->preview()

Reads the title, description, image, favicon and feeds of a public URL.

```php
$result = $zactonz->links()->preview(url: 'https://zactonz.com/products/');
```

- **Endpoint:** `GET /unfurl/`
- **Key:** `unfurl`
- **Full reference:** https://developers.zactonz.com/apis/link-preview/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `url` | `string` | yes |  | The page to preview. `https://` is assumed when the scheme is omitted. |
| `render` | `bool` | no | `false` | When true, loads the page in a headless browser before reading its tags. |
| `verifyImage` | `bool` | no | `false` | When true, downloads the preview image to confirm it exists and to report its real dimensions and type. |
| `fresh` | `bool` | no | `false` | When true, bypasses the one-hour cache. |

## Web page to Markdown

### markdown()->fromUrl()

Converts a web page to Markdown.

```php
$result = $zactonz->markdown()->fromUrl(url: 'https://developers.zactonz.com/apis/authentication/');
```

- **Endpoint:** `GET /markdown/`
- **Key:** `markdown`
- **Full reference:** https://developers.zactonz.com/apis/markdown/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `url` | `string` | yes |  | The page to convert. |
| `mode` | `string` | no | `article` | `article` (default) keeps the main content. `full` converts the whole body. |
| `render` | `bool` | no | `false` | When true, loads the page in a headless browser first. |
| `images` | `bool` | no | `true` | When false, drops images from the output. |
| `links` | `bool` | no | `true` | When false, replaces links with their text. |
| `maxChars` | `int` | no | `100000` | Maximum Markdown length, 1000 to 500000. |
| `fresh` | `bool` | no | `false` | When true, bypasses the cache. |

### markdown()->fromHtml()

Converts HTML you already hold to Markdown.

```php
$result = $zactonz->markdown()->fromHtml(html: '<h1>Hello</h1>');
```

- **Endpoint:** `POST /markdown/`
- **Key:** `markdown`
- **Full reference:** https://developers.zactonz.com/apis/markdown/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `html` | `string` | yes |  | The HTML to convert, up to 2 MB. |
| `mode` | `string` | no | `article` | `article` (default) keeps the main content. `full` converts the whole body. |
| `images` | `bool` | no | `true` | When false, drops images from the output. |
| `links` | `bool` | no | `true` | When false, replaces links with their text. |
| `maxChars` | `int` | no | `100000` | Maximum Markdown length, 1000 to 500000. |

## Domains: SSL, DNS and WHOIS

### domain()->ssl()

Inspects the certificate, chain, expiry and TLS details of a host.

```php
$result = $zactonz->domain()->ssl(host: 'zactonz.com');
```

- **Endpoint:** `GET /domain/ssl/`
- **Key:** `domain`
- **Full reference:** https://developers.zactonz.com/apis/domain-ssl/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `host` | `string` | yes |  | Host name, or a URL from which the host is taken. |
| `port` | `int` | no | `443` | TLS port: 443, 8443, 465, 993, 995, 636 or 5061. One of `443`, `8443`, `465`, `993`, `995`, `636`, `5061`. |
| `fresh` | `bool` | no | `false` | When true, bypasses the cache. |

### domain()->dns()

Looks up DNS records, with DNSSEC status.

```php
$result = $zactonz->domain()->dns(name: 'zactonz.com', type: 'A,MX,TXT');
```

- **Endpoint:** `GET /domain/dns/`
- **Key:** `domain`
- **Full reference:** https://developers.zactonz.com/apis/domain-dns/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `name` | `string` | yes |  | Host or domain name. |
| `type` | `string\|list<string>` | no | `ALL` | Comma-separated record types, or `ALL` for A, AAAA, MX, TXT, NS, CNAME, SOA and CAA. A list is also accepted. |

### domain()->whois()

Reads the registrar, dates, status and nameservers of a domain.

```php
$result = $zactonz->domain()->whois(domain: 'zactonz.com');
```

- **Endpoint:** `GET /domain/whois/`
- **Key:** `domain`
- **Full reference:** https://developers.zactonz.com/apis/domain-whois/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `domain` | `string` | yes |  | Domain name. A host name or URL is reduced to its registrable domain. |
| `raw` | `bool` | no | `false` | When true, includes the raw WHOIS text when the fallback service was used. |

## Email checks

### email()->health()

Audits the MX, SPF, DKIM, DMARC, MTA-STS, TLS-RPT and BIMI records of a domain and scores the result.

```php
$result = $zactonz->email()->health(domain: 'zactonz.com');
```

- **Endpoint:** `GET /email/domain/`
- **Key:** `email`
- **Full reference:** https://developers.zactonz.com/apis/email-domain/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `domain` | `string` | yes |  | Domain to audit. An email address is accepted and reduced to its domain. |
| `selectors` | `string\|list<string>` | no |  | Comma-separated DKIM selectors to check in addition to the common ones, up to 20. A list is also accepted. |
| `probe` | `bool` | no | `false` | When true, connects to the primary MX to read its banner and STARTTLS support. |

### email()->verify()

Checks whether up to ten email addresses can receive mail.

```php
$result = $zactonz->email()->verify(emails: 'jane@example.com, sales@zactonz.com');
```

- **Endpoint:** `POST /mverifier/`
- **Key:** `mverifier`
- **Full reference:** https://developers.zactonz.com/apis/mail-verifier/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `emails` | `string\|list<string>` | yes |  | One or more addresses separated by commas. At most ten per request. A list is also accepted. |

## Translation

### translator()->translate()

Translates text between 46 languages.

```php
$result = $zactonz->translator()->translate(text: 'Good morning, how are you?', to: 'es');
```

- **Endpoint:** `POST /translator/`
- **Key:** `translator`
- **Full reference:** https://developers.zactonz.com/apis/translator/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `text` | `string` | yes |  | The text to translate. |
| `to` | `string` | yes |  | Target language code. |
| `from` | `string` | no | `en` | Source language code. |

## Google Drive links

### drive()->directLink()

Turns a Google Drive share link into a direct download URL.

```php
$result = $zactonz->drive()->directLink(url: 'https://drive.google.com/file/d/0B8vQ0mnbCTFRcW1ZclhtS3ZKZGs/view');
```

- **Endpoint:** `GET /gdrive/directlink/`
- **Key:** `gdrive`
- **Full reference:** https://developers.zactonz.com/apis/gdrive-direct-link/

| Argument | Type | Required | Default | Description |
|---|---|---|---|---|
| `url` | `string` | yes |  | The Google Drive share link. |
