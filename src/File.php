<?php

declare(strict_types=1);

namespace Zactonz;

use Zactonz\Exception\ZactonzException;
use Zactonz\Http\ReadsRequestId;

/**
 * A file the API returned: an image, a PDF or a text document.
 */
final class File
{
    use ReadsRequestId;

    private const EXTENSIONS = [
        'image/png'       => 'png',
        'image/jpeg'      => 'jpg',
        'image/webp'      => 'webp',
        'image/avif'      => 'avif',
        'image/gif'       => 'gif',
        'image/svg+xml'   => 'svg',
        'application/pdf' => 'pdf',
        'text/markdown'   => 'md',
        'text/plain'      => 'txt',
    ];

    public readonly RateLimit $rateLimit;

    /**
     * @param string                $bytes       The file's contents.
     * @param string                $contentType Media type, such as `image/png`.
     * @param array<string, string> $headers     Response headers, keyed by lower-case name.
     */
    public function __construct(
        public readonly string $bytes,
        public readonly string $contentType,
        public readonly array $headers = [],
    ) {
        $this->rateLimit = RateLimit::fromHeaders($headers);
    }

    public function size(): int
    {
        return strlen($this->bytes);
    }

    /**
     * A file extension matching the content type, without the dot. `bin` when the type is not a known one.
     */
    public function extension(): string
    {
        return self::EXTENSIONS[$this->contentType] ?? 'bin';
    }

    /**
     * Writes the file to disk and returns the path.
     *
     * @throws ZactonzException When the path cannot be written.
     */
    public function save(string $path): string
    {
        // The failure is reported as an exception. Without this, the PHP warning would
        // reach the application's error handler first and often be thrown from there.
        set_error_handler(static fn (): bool => true);
        try {
            $written = file_put_contents($path, $this->bytes);
        } finally {
            restore_error_handler();
        }
        if ($written === false) {
            throw new ZactonzException('The file could not be written to "' . $path . '"');
        }
        return $path;
    }

    public function base64(): string
    {
        return base64_encode($this->bytes);
    }

    /**
     * The file as a `data:` URI, usable directly as an image source.
     */
    public function dataUri(): string
    {
        return 'data:' . $this->contentType . ';base64,' . $this->base64();
    }
}
