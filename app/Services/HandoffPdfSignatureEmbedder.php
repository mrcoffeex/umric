<?php

namespace App\Services;

use App\Models\DocumentTransmissionItem;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;
use Throwable;

class HandoffPdfSignatureEmbedder
{
    /** Compact stamp width on the page. */
    private const SIG_CARD_W_MM = 42.0;

    /**
     * Vertical offset between stamps when multiple e-signatures are embedded
     * (earlier ones sit above later ones, anchored to the page bottom).
     */
    private const SIG_BOTTOM_STACK_GAP_MM = 22.0;

    private const SIG_IMG_MAX_H_MM = 7.0;

    private const SIG_IMG_MAX_W_MM = 34.0;

    private const SIG_CARD_PAD_MM = 1.0;

    /**
     * Embeds a PNG (public disk) on the first page as a small transparent stamp
     * with handoff (sender → recipient), printed name, and datetime. The handwritten
     * signature sits above the stamp labels; both are drawn after the page template
     * so they appear above existing document text. New stamps stack upward from the
     * bottom-right; slot index is taken from
     * {@see DocumentTransmissionItem::$pdf_esignature_embed_count} and incremented on success.
     */
    public function embedPngOnFirstPage(
        DocumentTransmissionItem $item,
        string $pngPathRelativeToPublicDisk,
        string $signerName,
        DateTimeInterface $signedAt,
    ): bool {
        if (! $this->isPdfItem($item)) {
            return false;
        }

        $public = Storage::disk('public');
        if (! $public->exists($pngPathRelativeToPublicDisk)) {
            return false;
        }

        $fileDisk = Storage::disk($item->disk);
        if (! $fileDisk->exists($item->file_path)) {
            return false;
        }

        $tmpIn = tempnam(sys_get_temp_dir(), 'hpdf_in_');
        $tmpPng = tempnam(sys_get_temp_dir(), 'hpdf_sig_');

        if ($tmpIn === false || $tmpPng === false) {
            return false;
        }

        // TCPDF Image() needs a .png extension to detect the format.
        $tmpPngPath = $tmpPng.'.png';
        @unlink($tmpPng);

        $slotIndex = (int) $item->pdf_esignature_embed_count;

        try {
            file_put_contents($tmpPngPath, $public->get($pngPathRelativeToPublicDisk));
            file_put_contents($tmpIn, $fileDisk->get($item->file_path));
            if (! is_file($tmpIn) || filesize($tmpIn) < 1) {
                return false;
            }

            $this->makeNearWhitePixelsTransparent($tmpPngPath);

            $pdf = new Fpdi;
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetAutoPageBreak(false);
            $pageCount = $pdf->setSourceFile($tmpIn);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($tplId);
                $w = (float) $size['width'];
                $h = (float) $size['height'];
                $pdf->AddPage(
                    (string) $size['orientation'],
                    [$w, $h],
                );
                $pdf->useTemplate($tplId, 0, 0, $w, $h, true);

                if ($pageNo === 1) {
                    $item->loadMissing('transmission.sender', 'transmission.receiver');
                    $at = Carbon::parse($signedAt)->timezone((string) config('app.timezone'));
                    $dateStr = $at->format('Y-m-d H:i T');
                    $name = $this->sanitizeSignerLabel($signerName);
                    $handoffLine = $this->handoffLineForItem($item);
                    $metrics = $this->buildSignatureCardMetrics($pdf, $w, $tmpPngPath, $name, $dateStr, $handoffLine);
                    $yTop = $this->resolveSignatureCardTopMm(
                        $h,
                        $slotIndex,
                        (float) $metrics['cardH'],
                    );
                    $this->drawSignatureStamp(
                        $pdf,
                        $h,
                        $tmpPngPath,
                        $yTop,
                        $metrics,
                    );
                }
            }

            $newBytes = $pdf->Output('', 'S');
            if (! is_string($newBytes) || $newBytes === '') {
                return false;
            }

            $fileDisk->put($item->file_path, $newBytes);
            $item->forceFill([
                'file_size' => strlen($newBytes),
                'pdf_esignature_embed_count' => $slotIndex + 1,
            ])->save();

            return true;
        } catch (Throwable $e) {
            Log::warning('Handoff PDF e-signature embed failed', [
                'item_id' => $item->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return false;
        } finally {
            if (is_file($tmpIn)) {
                @unlink($tmpIn);
            }
            if (is_file($tmpPngPath)) {
                @unlink($tmpPngPath);
            }
        }
    }

    /**
     * @deprecated Use {@see embedPngOnFirstPage()} — stamps always go on page 1.
     */
    public function embedPngOnLastPage(
        DocumentTransmissionItem $item,
        string $pngPathRelativeToPublicDisk,
        string $signerName,
        DateTimeInterface $signedAt,
    ): bool {
        return $this->embedPngOnFirstPage($item, $pngPathRelativeToPublicDisk, $signerName, $signedAt);
    }

    public function isPdfItem(DocumentTransmissionItem $item): bool
    {
        if (! $item->hasAttachment() || ! is_string($item->disk) || $item->file_path === null || $item->file_path === '') {
            return false;
        }

        $ext = strtolower((string) pathinfo((string) $item->file_name, PATHINFO_EXTENSION));

        return $ext === 'pdf';
    }

    private function handoffLineForItem(DocumentTransmissionItem $item): string
    {
        $transmission = $item->transmission;
        if ($transmission === null) {
            return 'Handoff: — → —';
        }

        $from = $this->handoffPartyLabel($transmission->sender?->name);
        $to = $this->handoffPartyLabel($transmission->receiver?->name);

        return "{$from} → {$to}";
    }

    private function handoffPartyLabel(?string $name): string
    {
        if ($name === null || trim($name) === '') {
            return '—';
        }

        return $this->sanitizeSignerLabel($name);
    }

    /**
     * @return array{
     *     cardH: float,
     *     x: float,
     *     cardW: float,
     *     innerW: float,
     *     imgW: float,
     *     imgH: float,
     *     lineH: float,
     *     labelText: string
     * }
     */
    private function buildSignatureCardMetrics(
        Fpdi $pdf,
        float $pageWidth,
        string $tmpPngPath,
        string $name,
        string $dateStr,
        string $handoffLine,
    ): array {
        $margin = 10.0;
        $cardW = min(self::SIG_CARD_W_MM, $pageWidth - 2 * $margin);
        $x = $pageWidth - $margin - $cardW;
        $innerW = $cardW - 2 * self::SIG_CARD_PAD_MM;
        $info = @getimagesize($tmpPngPath);
        $wPx = is_array($info) ? (int) ($info[0] ?? 1) : 1;
        $hPx = is_array($info) ? (int) ($info[1] ?? 1) : 1;
        $imgW = min(self::SIG_IMG_MAX_W_MM, $innerW);
        $imgH = $imgW * ($hPx / max($wPx, 1));
        if ($imgH > self::SIG_IMG_MAX_H_MM) {
            $scale = self::SIG_IMG_MAX_H_MM / $imgH;
            $imgH = self::SIG_IMG_MAX_H_MM;
            $imgW = $imgW * $scale;
        }
        $lineH = 2.8;
        $labelText = "{$handoffLine}\n{$name}\n{$dateStr}";
        $fontPt = 5.5;
        $pdf->SetFont('dejavusans', '', $fontPt);
        $textH = max(
            $lineH * 3.0,
            $pdf->getStringHeight($innerW, $labelText, true, true, 0, 0),
        ) + 0.5;
        // Signature image sits above the stamp labels.
        $cardH = self::SIG_CARD_PAD_MM + $imgH + 0.8 + $textH + self::SIG_CARD_PAD_MM;

        return [
            'cardH' => $cardH,
            'x' => $x,
            'cardW' => $cardW,
            'innerW' => $innerW,
            'imgW' => $imgW,
            'imgH' => $imgH,
            'lineH' => $lineH,
            'labelText' => $labelText,
        ];
    }

    private function resolveSignatureCardTopMm(
        float $pageHeightMm,
        int $slotIndex,
        float $cardH,
    ): float {
        $margin = 10.0;
        $yTop = $pageHeightMm
            - $margin
            - $cardH
            - $slotIndex * self::SIG_BOTTOM_STACK_GAP_MM;

        if ($yTop < 4.0) {
            Log::warning('E-signature: page very full; stamp placed at top margin and may overlap prior marks.', [
                'page_height' => $pageHeightMm,
                'slot' => $slotIndex,
            ]);
            $yTop = 4.0;
        }

        return $yTop;
    }

    /**
     * Renders stamp content with no filled background. Stamp labels are drawn first,
     * then the handwritten signature so the ink sits above the stamp and the page text.
     *
     * @param  array{
     *     x: float,
     *     innerW: float,
     *     imgW: float,
     *     imgH: float,
     *     lineH: float,
     *     labelText: string
     * }  $metrics
     */
    private function renderSignatureStampInterior(
        Fpdi $pdf,
        float $yTop,
        string $tmpPngPath,
        array $metrics,
    ): void {
        $x = (float) $metrics['x'];
        $innerW = (float) $metrics['innerW'];
        $imgW = (float) $metrics['imgW'];
        $imgH = (float) $metrics['imgH'];
        $lineH = (float) $metrics['lineH'];
        $labelText = (string) $metrics['labelText'];
        $textLeft = $x + self::SIG_CARD_PAD_MM;
        $ix = $x + self::SIG_CARD_PAD_MM + max(0.0, ($innerW - $imgW) / 2.0);
        $iy = $yTop + self::SIG_CARD_PAD_MM;
        $fontPt = 5.5;

        // Stamp labels sit below the signature image.
        $yText = $iy + $imgH + 0.8;
        $pdf->SetXY($textLeft, $yText);
        $pdf->SetFont('dejavusans', '', $fontPt);
        $pdf->SetTextColor(40, 42, 48);
        $pdf->MultiCell(
            $innerW,
            $lineH,
            $labelText,
            0,
            'C',
            false,
            1,
            '',
            '',
            true,
            0,
            false,
            true,
            0,
            'T',
            false
        );

        // Draw signature last so ink sits above stamp labels and document text.
        $pdf->Image($tmpPngPath, $ix, $iy, $imgW, $imgH, 'PNG', '', '', true, 300, '', false, false, 0, false, false, false);
    }

    private function drawSignatureStamp(
        Fpdi $pdf,
        float $pageHeight,
        string $tmpPngPath,
        float $yTop,
        array $metrics,
    ): void {
        $margin = 10.0;
        $bottomPadMm = 1.0;

        $pdf->startTransaction();
        $this->renderSignatureStampInterior($pdf, $yTop, $tmpPngPath, $metrics);
        $cardH = (float) $pdf->GetY() - $yTop + $bottomPadMm;
        $pdf->rollbackTransaction(true);

        if ($yTop + $cardH > $pageHeight - $margin) {
            $yTop = max(4.0, $pageHeight - $margin - $cardH);
        }

        // Drawn after useTemplate so stamp + signature sit above existing page text.
        $this->renderSignatureStampInterior($pdf, $yTop, $tmpPngPath, $metrics);
    }

    /**
     * Converts near-white pixels to fully transparent so saved signatures with a white
     * pad background do not cover document content.
     */
    private function makeNearWhitePixelsTransparent(string $pngPath): void
    {
        if (! function_exists('imagecreatefrompng') || ! is_file($pngPath)) {
            return;
        }

        $source = @imagecreatefrompng($pngPath);
        if ($source === false) {
            return;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $dest = imagecreatetruecolor($width, $height);
        if ($dest === false) {
            imagedestroy($source);

            return;
        }

        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefilledrectangle($dest, 0, 0, $width, $height, $transparent);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgba = imagecolorat($source, $x, $y);

                if (imageistruecolor($source)) {
                    $a = ($rgba & 0x7F000000) >> 24;
                    $r = ($rgba >> 16) & 0xFF;
                    $g = ($rgba >> 8) & 0xFF;
                    $b = $rgba & 0xFF;
                    $alpha = $a;
                } else {
                    $colors = imagecolorsforindex($source, $rgba);
                    $r = (int) ($colors['red'] ?? 0);
                    $g = (int) ($colors['green'] ?? 0);
                    $b = (int) ($colors['blue'] ?? 0);
                    $alpha = (int) ($colors['alpha'] ?? 0);
                }

                if ($r >= 245 && $g >= 245 && $b >= 245) {
                    continue;
                }

                $color = imagecolorallocatealpha($dest, $r, $g, $b, $alpha);
                imagesetpixel($dest, $x, $y, $color);
            }
        }

        imagepng($dest, $pngPath);
        imagedestroy($source);
        imagedestroy($dest);
    }

    private function sanitizeSignerLabel(string $name): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

        return $t === '' ? 'Unknown' : mb_substr($t, 0, 200);
    }
}
