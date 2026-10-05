<?php

/**
 * How each API endpoint is presented in the client.
 *
 * The parameters, their types and their descriptions come from the
 * specifications in specs/. This file decides only the names: which service
 * an endpoint belongs to, what its methods are called, and which parameters
 * a method hides, fixes or adds.
 *
 * Per endpoint:
 *   service     key of the service the methods are added to
 *   methods     one entry per generated method (see below)
 *   hide        parameters no method exposes
 *   lists       parameters that also accept a list, sent comma-separated
 *   files       parameters that take the path of a local file to upload
 *   requireOne  parameters of which at least one must be given
 *   add         parameters the specification leaves out
 *   describe    replacement descriptions, by parameter
 *
 * Per method:
 *   name, summary
 *   returns     'file' when the method returns the bytes; a Result otherwise
 *   http        request method, when it differs from the specification
 *   force       parameters sent with a fixed value and not exposed
 *   hide, only, require, add, describe, values (allowed values, by parameter)
 */

declare(strict_types=1);

$margin = static fn (string $side): array => [
    'name'        => 'margin' . $side,
    'type'        => 'number',
    'description' => 'PDF only. ' . $side . ' margin in inches, 0 to 5. Default 0.4.',
];

return [
    'services' => [
        'qr'         => ['class' => 'Qr', 'title' => 'QR codes'],
        'barcode'    => ['class' => 'Barcode', 'title' => 'Barcodes'],
        'screenshot' => ['class' => 'Screenshot', 'title' => 'Screenshots and PDFs'],
        'og'         => ['class' => 'Og', 'title' => 'Social card images'],
        'image'      => ['class' => 'Image', 'title' => 'Image conversion'],
        'links'      => ['class' => 'Links', 'title' => 'Link previews'],
        'markdown'   => ['class' => 'Markdown', 'title' => 'Web page to Markdown'],
        'domain'     => ['class' => 'Domain', 'title' => 'Domains: SSL, DNS and WHOIS'],
        'email'      => ['class' => 'Email', 'title' => 'Email checks'],
        'translator' => ['class' => 'Translator', 'title' => 'Translation'],
        'drive'      => ['class' => 'Drive', 'title' => 'Google Drive links'],
    ],
    'endpoints' => [
        'qr-encoder' => [
            'service'  => 'qr',
            'describe' => ['content' => 'The text or URL to encode, up to 4000 characters.'],
            'methods'  => [
                ['name' => 'encode', 'summary' => 'Generates a QR code and returns its hosted link, or the 0/1 matrix itself for the `text` format.', 'force' => ['resp' => 'json']],
                [
                    'name'     => 'encodeFile',
                    'summary'  => 'Generates a QR code and returns the image.',
                    'returns'  => 'file',
                    'force'    => ['resp' => 'image'],
                    'values'   => ['format' => ['png', 'jpg', 'svg']],
                    'describe' => ['format' => '`png` (default), `jpg` or `svg`.'],
                ],
            ],
        ],
        'qr-decoder' => [
            'service'  => 'qr',
            'describe' => [
                'image'  => 'A link to the image, or the base64-encoded image bytes when `format` is `base64`. A `data:image/...;base64,` prefix is accepted.',
                'format' => 'How `image` is given: `url` for a link to the image, `base64` for the image bytes.',
            ],
            'methods'  => [
                ['name' => 'decode', 'summary' => 'Reads the contents of a QR code from an image.', 'http' => 'POST'],
            ],
        ],
        'barcode-encoder' => [
            'service' => 'barcode',
            'methods' => [
                ['name' => 'encode', 'summary' => 'Renders a barcode and returns a hosted link with its dimensions.', 'force' => ['resp' => 'json']],
                ['name' => 'encodeFile', 'summary' => 'Renders a barcode and returns the image.', 'returns' => 'file', 'force' => ['resp' => 'image']],
            ],
        ],
        'screenshot-url' => [
            'service'  => 'screenshot',
            'add'      => [$margin('Bottom'), $margin('Left'), $margin('Right')],
            'describe' => ['marginTop' => 'PDF only. Top margin in inches, 0 to 5. Default 0.4.'],
            'methods'  => [
                ['name' => 'capture', 'summary' => 'Renders a web page to an image or a PDF and returns its hosted link.', 'hide' => ['download']],
                ['name' => 'captureFile', 'summary' => 'Renders a web page to an image or a PDF and returns the file.', 'returns' => 'file', 'force' => ['download' => '1']],
            ],
        ],
        'screenshot-html' => [
            'service' => 'screenshot',
            'methods' => [
                ['name' => 'captureHtml', 'summary' => 'Renders an HTML document to an image or a PDF and returns its hosted link.'],
            ],
        ],
        'og-image' => [
            'service' => 'og',
            'hide'    => ['k', 'sig'],
            'methods' => [
                ['name' => 'generate', 'summary' => 'Generates a social card image and returns a hosted link, valid for seven days.', 'force' => ['resp' => 'json']],
                ['name' => 'generateFile', 'summary' => 'Generates a social card image and returns the image.', 'returns' => 'file', 'force' => ['resp' => 'image']],
            ],
        ],
        'image-convert' => [
            'service'    => 'image',
            'files'      => ['file'],
            'requireOne' => ['file', 'url'],
            'describe'   => [
                'file' => 'The image to convert: JPEG, PNG, GIF, WebP, AVIF or BMP, up to the upload limit of your plan.',
                'url'  => 'Public URL of the image, instead of a local file.',
            ],
            'methods'    => [
                ['name' => 'convert', 'summary' => 'Converts, resizes or crops an image and returns a hosted link with its metadata.', 'force' => ['resp' => 'json'], 'hide' => ['info']],
                ['name' => 'convertFile', 'summary' => 'Converts, resizes or crops an image and returns the image.', 'returns' => 'file', 'force' => ['resp' => 'image'], 'hide' => ['info']],
                ['name' => 'info', 'summary' => 'Reads the dimensions, format and dominant colours of an image without converting it.', 'force' => ['info' => '1'], 'only' => ['file', 'url']],
            ],
        ],
        'link-preview' => [
            'service' => 'links',
            'methods' => [
                ['name' => 'preview', 'summary' => 'Reads the title, description, image, favicon and feeds of a public URL.'],
            ],
        ],
        'markdown' => [
            'service' => 'markdown',
            'methods' => [
                ['name' => 'fromUrl', 'summary' => 'Converts a web page to Markdown.', 'force' => ['format' => 'json'], 'require' => ['url'], 'describe' => ['url' => 'The page to convert.']],
                [
                    'name'    => 'fromHtml',
                    'summary' => 'Converts HTML you already hold to Markdown.',
                    'http'    => 'POST',
                    'force'   => ['format' => 'json'],
                    'hide'    => ['url', 'render', 'fresh'],
                    'add'     => [['name' => 'html', 'type' => 'string', 'required' => true, 'description' => 'The HTML to convert, up to 2 MB.', 'example' => '<h1>Hello</h1>']],
                ],
            ],
        ],
        'domain-ssl' => [
            'service' => 'domain',
            'methods' => [
                ['name' => 'ssl', 'summary' => 'Inspects the certificate, chain, expiry and TLS details of a host.'],
            ],
        ],
        'domain-dns' => [
            'service' => 'domain',
            'lists'   => ['type'],
            'methods' => [
                ['name' => 'dns', 'summary' => 'Looks up DNS records, with DNSSEC status.'],
            ],
        ],
        'domain-whois' => [
            'service' => 'domain',
            'methods' => [
                ['name' => 'whois', 'summary' => 'Reads the registrar, dates, status and nameservers of a domain.'],
            ],
        ],
        'email-domain' => [
            'service' => 'email',
            'lists'   => ['selectors'],
            'methods' => [
                ['name' => 'health', 'summary' => 'Audits the MX, SPF, DKIM, DMARC, MTA-STS, TLS-RPT and BIMI records of a domain and scores the result.'],
            ],
        ],
        'mail-verifier' => [
            'service' => 'email',
            'hide'    => ['key'],
            'lists'   => ['emails'],
            'methods' => [
                ['name' => 'verify', 'summary' => 'Checks whether up to ten email addresses can receive mail.'],
            ],
        ],
        'translator' => [
            'service' => 'translator',
            'hide'    => ['key'],
            'methods' => [
                ['name' => 'translate', 'summary' => 'Translates text between 46 languages.'],
            ],
        ],
        'gdrive-direct-link' => [
            'service' => 'drive',
            'methods' => [
                ['name' => 'directLink', 'summary' => 'Turns a Google Drive share link into a direct download URL.'],
            ],
        ],
    ],
];
