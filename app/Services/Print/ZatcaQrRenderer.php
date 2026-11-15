<?php

declare(strict_types=1);

namespace App\Services\Print;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class ZatcaQrRenderer
{
    /**
     * Render a ZATCA TLV Base64 payload into an inline SVG Data URI locally
     * without transmitting invoice data to any third-party QR service.
     */
    public function toDataUri(?string $tlv, int $size = 180): ?string
    {
        if ($tlv === null || $tlv === '') {
            return null;
        }

        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd));

        return 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($tlv));
    }
}
