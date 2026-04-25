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
    private const SIG_CARD_W_MM = 72.0;

    /**
     * Vertical offset between cards when multiple e-signatures are embedded on the same page
     * (earlier ones sit above later ones, anchored to the page bottom).
     */
    private const SIG_BOTTOM_STACK_GAP_MM = 36.0;

    private const SIG_IMG_MAX_H_MM = 10.0;

    private const SIG_CARD_PAD_MM = 2.5;

    /**
     * Embeds a PNG (public disk) on the last page in a right-aligned “card” with handoff
     * (sender → recipient), printed name, and datetime. New signatures stack on the same page;
     * slot index is taken from
     * {@see DocumentTransmissionItem::$pdf_esignature_embed_count} and incremented on success.
     */
    public function embedPngOnLastPage(
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

        $slotIndex = (int) $item->pdf_esignature_embed_count;

        try {
            file_put_contents($tmpPng, $public->get($pngPathRelativeToPublicDisk));
            file_put_contents($tmpIn, $fileDisk->get($item->file_path));
            if (! is_file($tmpIn) || filesize($tmpIn) < 1) {
                return false;
            }

            $pdf = new Fpdi;
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
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

                if ($pageNo === $pageCount) {
                    $item->loadMissing('transmission.sender', 'transmission.receiver');
                    $at = Carbon::parse($signedAt)->timezone((string) config('app.timezone'));
                    $dateStr = $at->format('Y-m-d H:i T');
                    $name = $this->sanitizeSignerLabel($signerName);
                    $handoffLine = $this->handoffLineForItem($item);
                    $metrics = $this->buildSignatureCardMetrics($pdf, $w, $tmpPng, $name, $dateStr, $handoffLine);
                    $yTop = $this->resolveSignatureCardTopMm(
                        $h,
                        $slotIndex,
                        (float) $metrics['cardH'],
                    );
                    $this->drawSignatureCard(
                        $pdf,
                        $h,
                        $tmpPng,
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
            if (is_file($tmpPng)) {
                @unlink($tmpPng);
            }
        }
    }

    public function isPdfItem(DocumentTransmissionItem $item): bool
    {
        if (! $item->hasAttachment() || ! is_string($item->disk) || $item->file_path === null || $item->file_path === '') {
            return false;
        }

        $ext = strtolower((string) pathinfo((string) $item->file_name, PATHINFO_EXTENSION));

        return $ext === 'pdf';
    }

    /**
     * Lays out the card using TCPDF line wrapping so the rounded rect is tall enough for the
     * “Printed name” (possibly long) and “Date & time” block.
     *
     * @return array{
     *     cardH: float,
     *     x: float,
     *     cardW: float,
     *     innerW: float,
     *     imgW: float,
     *     imgH: float,
     *     headerH: float,
     *     lineH: float,
     *     labelText: string
     * }
     */
    private function handoffLineForItem(DocumentTransmissionItem $item): string
    {
        $transmission = $item->transmission;
        if ($transmission === null) {
            return 'Handoff: — → —';
        }

        $from = $this->handoffPartyLabel($transmission->sender?->name);
        $to = $this->handoffPartyLabel($transmission->receiver?->name);

        return "Handoff: {$from} → {$to}";
    }

    private function handoffPartyLabel(?string $name): string
    {
        if ($name === null || trim($name) === '') {
            return '—';
        }

        return $this->sanitizeSignerLabel($name);
    }

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
        $imgW = min(60.0, $innerW);
        $imgH = $imgW * ($hPx / max($wPx, 1));
        if ($imgH > self::SIG_IMG_MAX_H_MM) {
            $scale = self::SIG_IMG_MAX_H_MM / $imgH;
            $imgH = self::SIG_IMG_MAX_H_MM;
            $imgW = $imgW * $scale;
        }
        $lineH = 3.3;
        $headerH = 3.2;
        $labelText = "{$handoffLine}\nPrinted name: {$name}\nDate & time: {$dateStr}";
        $fontPt = 6.5;
        $pdf->SetFont('dejavusans', '', $fontPt);
        $textH = max(
            $lineH * 3.0,
            $pdf->getStringHeight($innerW, $labelText, true, true, 0, 0),
        ) + 1.0;
        $cardH = self::SIG_CARD_PAD_MM + $headerH + 0.3 + $imgH + 0.4 + $textH + self::SIG_CARD_PAD_MM;

        return [
            'cardH' => $cardH,
            'x' => $x,
            'cardW' => $cardW,
            'innerW' => $innerW,
            'imgW' => $imgW,
            'imgH' => $imgH,
            'headerH' => $headerH,
            'lineH' => $lineH,
            'labelText' => $labelText,
        ];
    }

    /**
     * Places e-signature card(s) at the end of the last page: anchored to the bottom margin,
     * with earlier slots (previous embeds) stacked upward so multiple signatures do not overlap.
     */
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
            Log::warning('E-signature: page very full; card placed at top margin and may overlap prior marks.', [
                'page_height' => $pageHeightMm,
                'slot' => $slotIndex,
            ]);
            $yTop = 4.0;
        }

        return $yTop;
    }

    /**
     * Renders the e-signature card content (no border) so a dry-run can measure the true
     * bottom Y. Draw order: image first, then the “E-signature” title, then printed name and
     * datetime so the PNG sits behind all text in the stack (PDFs paint last object on top).
     *
     * @param  array{
     *     x: float,
     *     innerW: float,
     *     imgW: float,
     *     imgH: float,
     *     headerH: float,
     *     lineH: float,
     *     labelText: string
     * }  $metrics
     */
    private function renderSignatureCardInterior(
        Fpdi $pdf,
        float $yTop,
        string $tmpPngPath,
        array $metrics,
    ): void {
        $x = (float) $metrics['x'];
        $innerW = (float) $metrics['innerW'];
        $imgW = (float) $metrics['imgW'];
        $imgH = (float) $metrics['imgH'];
        $headerH = (float) $metrics['headerH'];
        $lineH = (float) $metrics['lineH'];
        $labelText = (string) $metrics['labelText'];
        $ix = $x + self::SIG_CARD_PAD_MM + max(0.0, ($innerW - $imgW) / 2.0);
        $iy = $yTop + self::SIG_CARD_PAD_MM + $headerH + 0.3;
        $fontPt = 6.5;
        $textLeft = $x + self::SIG_CARD_PAD_MM;
        $textTop = $yTop + self::SIG_CARD_PAD_MM;

        // Signature graphic first; title and name/date render on top if anything overlaps.
        $pdf->Image($tmpPngPath, $ix, $iy, $imgW, $imgH, 'PNG', '', '', true, 300);

        $pdf->SetFont('dejavusans', 'B', $fontPt);
        $pdf->SetTextColor(70, 75, 95);
        $pdf->SetXY($textLeft, $textTop);
        $pdf->Cell($innerW, $headerH, 'E-signature', 0, 1, 'L', false, '', 0, false, 'T', 'M');

        $yText = $iy + $imgH + 0.5;
        $pdf->SetXY($textLeft, $yText);
        $pdf->SetFont('dejavusans', '', $fontPt);
        $pdf->SetTextColor(30, 30, 32);
        // $ln must be 1: with ln=0, TCPDF resets Y to the MultiCell start Y after drawing, so
        // GetY() omits the label height and the rounded rect is sized too short (text renders below the card).
        $pdf->MultiCell(
            $innerW,
            $lineH,
            $labelText,
            0,
            'L',
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
    }

    private function drawSignatureCard(
        Fpdi $pdf,
        float $pageHeight,
        string $tmpPngPath,
        float $yTop,
        array $metrics,
    ): void {
        $margin = 10.0;
        $x = (float) $metrics['x'];
        $cardW = (float) $metrics['cardW'];
        $bottomPadMm = 1.5;

        $pdf->startTransaction();
        $this->renderSignatureCardInterior($pdf, $yTop, $tmpPngPath, $metrics);
        $cardH = (float) $pdf->GetY() - $yTop + $bottomPadMm;
        $pdf->rollbackTransaction(true);

        if ($yTop + $cardH > $pageHeight - $margin) {
            $yTop = max(4.0, $pageHeight - $margin - $cardH);
        }

        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(200, 205, 220);
        $pdf->SetLineWidth(0.2);
        $pdf->RoundedRect($x, $yTop, $cardW, $cardH, 1.2, '1111', 'DF');

        $this->renderSignatureCardInterior($pdf, $yTop, $tmpPngPath, $metrics);
    }

    private function sanitizeSignerLabel(string $name): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

        return $t === '' ? 'Unknown' : mb_substr($t, 0, 200);
    }
}
