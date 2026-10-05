<?php

declare(strict_types=1);

namespace Zactonz\Service;

/**
 * Accessors for every service, used by Zactonz\Client.
 *
 * Generated from the API specifications by tools/generate.php. Edits made here are lost the next time it runs.
 *
 * @internal
 */
trait Services
{
    /**
     * QR codes.
     */
    public function qr(): Qr
    {
        return new Qr($this->dispatcher);
    }

    /**
     * Barcodes.
     */
    public function barcode(): Barcode
    {
        return new Barcode($this->dispatcher);
    }

    /**
     * Screenshots and PDFs.
     */
    public function screenshot(): Screenshot
    {
        return new Screenshot($this->dispatcher);
    }

    /**
     * Social card images.
     */
    public function og(): Og
    {
        return new Og($this->dispatcher);
    }

    /**
     * Image conversion.
     */
    public function image(): Image
    {
        return new Image($this->dispatcher);
    }

    /**
     * Link previews.
     */
    public function links(): Links
    {
        return new Links($this->dispatcher);
    }

    /**
     * Web page to Markdown.
     */
    public function markdown(): Markdown
    {
        return new Markdown($this->dispatcher);
    }

    /**
     * Domains: SSL, DNS and WHOIS.
     */
    public function domain(): Domain
    {
        return new Domain($this->dispatcher);
    }

    /**
     * Email checks.
     */
    public function email(): Email
    {
        return new Email($this->dispatcher);
    }

    /**
     * Translation.
     */
    public function translator(): Translator
    {
        return new Translator($this->dispatcher);
    }

    /**
     * Google Drive links.
     */
    public function drive(): Drive
    {
        return new Drive($this->dispatcher);
    }
}
