<?php

namespace App\Services;

use App\Models\EvaluationCriterion;
use App\Models\EvaluationFormat;
use App\Models\PanelDefense;
use App\Models\PanelDefenseEvaluation;
use App\Models\ResearchPaper;
use App\Models\Sdg;
use App\Support\EvaluationPdfSettings;
use TCPDF;

class DefenseEvaluationPdfGenerator
{
    public function __construct() {}

    public function build(PanelDefenseEvaluation $evaluation): string
    {
        $evaluation->load(['evaluator', 'panelDefense.evaluationFormat', 'panelDefense.researchPaper.user']);
        $defense = $evaluation->panelDefense;
        if (! $defense instanceof PanelDefense) {
            throw new \InvalidArgumentException('Missing panel defense for evaluation.');
        }
        $format = $defense->evaluationFormat;
        $settings = EvaluationPdfSettings::forFormat($format);

        $lineItems = is_array($evaluation->line_items) ? $evaluation->line_items : [];
        $paper = $defense->researchPaper;

        $pdf = new class('P', 'mm', 'A4', true, 'UTF-8', false, false) extends TCPDF
        {
            public string $footerLeft = '';

            public function setFooterLeft(string $footerLeft): void
            {
                $this->footerLeft = $footerLeft;
            }

            public function Footer(): void
            {
                $this->SetY(-12);
                $this->SetX(12);
                $this->SetFont('times', '', 6.5);
                $this->Cell(132, 3, $this->footerLeft, 0, 0, 'L');
                $this->Cell(54, 3, 'Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages(), 0, 0, 'R');
            }
        };
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->setFontSubsetting(true);
        $pdf->SetFont('times', '', 10);
        $this->writeFooterOnAllPages($pdf, $settings);

        $pdf->AddPage();
        $this->writeBrandedHeader($pdf, $format, $settings, $defense, $paper);
        $bodyStartPage = (int) $pdf->getPage();
        $bodyStartY = $pdf->getY() + 4;
        $pdf->SetMargins(17, 12, 17);
        $pdf->SetX(17);
        $pdf->SetY($bodyStartY);
        $this->writeOptionalContext($pdf, $format, $settings, $paper, $evaluation);
        if (! empty($settings['show_rating_scale'])) {
            $this->writeRatingScale($pdf, $settings);
        }
        $this->writeCriteriaTable($pdf, $format, $lineItems);

        $summaryOnNewPage = $pdf->getPageHeight() - $pdf->getY() < 55;
        if ($summaryOnNewPage) {
            $pdf->AddPage();
        } else {
            $pdf->Ln(4);
        }
        $this->writeSummary($pdf, $format, $evaluation, $settings);

        if (! empty($settings['show_signature_block'])) {
            if ($pdf->getPageHeight() - $pdf->getY() < 36) {
                $pdf->AddPage();
            }
            $this->writeSignatureBlock($pdf);
        }

        $this->writeBodyBorder($pdf, $bodyStartPage, $bodyStartY);

        $pdf->lastPage();

        return $pdf->Output('', 'S');
    }

    /**
     * 20% bordered logo cell (upper left) + 80% bordered heading.
     *
     * @param  array<string, mixed>  $settings
     */
    private function writeBrandedHeader(
        TCPDF $pdf,
        ?EvaluationFormat $format,
        array $settings,
        PanelDefense $defense,
        ?ResearchPaper $paper,
    ): void {
        $title = (string) ($settings['form_title'] ?? 'DEFENSE EVALUATION');
        $sub = (string) ($settings['form_subtitle'] ?? '');
        $inst = (string) ($settings['header_institution'] ?? 'RESEARCH AND INNOVATION CENTER');

        $branches = (array) ($settings['branches'] ?? []);
        $branchLine = [];
        foreach ($branches as $b) {
            if (! is_array($b) || ! isset($b['label'])) {
                continue;
            }
            $mark = $this->checkboxHtml(! empty($b['default']));
            $branchLine[] = $mark.' '.htmlspecialchars((string) $b['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        $branchHtml = $branchLine !== [] ? '<span style="font-size:8pt">'.implode('   ', $branchLine).'</span><br/>' : '';

        $rightStack = '<span style="font-size:17pt"><b>'.htmlspecialchars($inst, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</b></span><br/>'
            .$branchHtml
            .'<span style="font-size:11pt"><b>'.htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</b></span><br/>'
            .'<span style="font-size:9pt">'.htmlspecialchars($sub, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</span>';

        $x = 12.0;
        $y = $pdf->getY();
        $width = $pdf->getPageWidth() - 24.0;
        $height = 27.0;
        $logoWidth = $width * 0.2;
        $textWidth = $width - $logoWidth;

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->Rect($x, $y, $logoWidth, $height);
        $pdf->Rect($x + $logoWidth, $y, $textWidth, $height);

        $this->drawHeaderLogo($pdf, $settings, $x, $y, $logoWidth, $height);

        $pdf->SetFont('times', '', 9);
        $pdf->writeHTMLCell(
            $textWidth - 4,
            $height,
            $x + $logoWidth + 2,
            $y + 4,
            $rightStack,
            0,
            0,
            false,
            true,
            'C',
            true,
        );
        $pdf->SetY($y + $height);
    }

    /**
     * Optional instruction, research title, proponents, and SDG lines (toggled per format).
     *
     * @param  array<string, mixed>  $settings
     */
    private function writeOptionalContext(
        TCPDF $pdf,
        ?EvaluationFormat $format,
        array $settings,
        ?ResearchPaper $paper,
        PanelDefenseEvaluation $evaluation,
    ): void {
        if (! empty($settings['show_research_title'])) {
            $paperTitle = $paper?->title !== null && $paper?->title !== '' ? (string) $paper->title : '—';
            $pdf->Ln(4);
            $pdf->SetFont('times', 'B', 9);
            $pdf->writeHTML('Research title:', true, false, true, false, '');
            $pdf->Ln(1);
            $pdf->SetFont('times', '', 9.5);
            $pdf->writeHTML(
                '<table width="100%" border="0" cellpadding="0" cellspacing="0">'
                .'<tr>'
                .'<td style="min-height:8mm;line-height:1.25">'
                .htmlspecialchars($paperTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                .'</td></tr></table>',
                true, false, true, false, '',
            );
        }

        if (! empty($settings['show_proponents'])) {
            $propLines = $this->proponentNameLines($paper);
            $pdf->Ln(3);
            $pdf->SetFont('times', 'B', 9);
            $pdf->writeHTML('Proponent(s):', true, false, true, false, '');
            $pdf->Ln(1);
            $pdf->SetFont('times', '', 9.5);
            $propRows = collect($propLines)
                ->map(function (string $n) {
                    return '<tr><td width="5mm"></td>'
                        .'<td style="line-height:1">'.htmlspecialchars($n, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</td></tr>';
                })->implode('');
            $pdf->writeHTML(
                '<table width="100%" border="0" cellpadding="0" cellspacing="0">'.$propRows.'</table>',
                true, false, true, false, '',
            );
        }

        if (! empty($settings['show_instruction'])) {
            $raw = (string) ($settings['instruction_text'] ?? '');
            if ($this->plainTextFromHtml($raw) !== '') {
                $pdf->Ln(3);
                $pdf->SetFont('times', 'B', 9);
                $pdf->Cell(0, 4, 'Instruction:', 0, 1, 'L');
                $pdf->SetFont('times', '', 8.5);
                $instructionText = nl2br(htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);
                $pdf->writeHTML(
                    '<table width="100%" border="0" cellpadding="0" cellspacing="0">'
                    .'<tr><td width="5mm"></td>'
                    .'<td style="line-height:1.25">'.$instructionText.'</td></tr></table>',
                    true, false, true, false, '',
                );
            }
        }

        if (! empty($settings['show_sdg'])) {
            $selected = is_array($evaluation->sdg_ids) && $evaluation->sdg_ids !== []
                ? $evaluation->sdg_ids
                : (is_array($paper?->sdg_ids) ? $paper->sdg_ids : []);
            $selectedIds = array_flip(array_map('strval', $selected));

            $rows = $selected !== []
                ? Sdg::query()->whereIn('id', $selected)->orderBy('number')->get(['id', 'number', 'name'])
                : collect();

            if ($rows->isNotEmpty()) {
                $pdf->Ln(3);
                $pdf->SetFont('times', 'B', 9);
                $pdf->Cell(0, 4, 'SDG(s) applied in this research:', 0, 1, 'L');
                $pdf->SetFont('times', '', 8.5);

                $cells = $rows->map(function (Sdg $s) use ($selectedIds): string {
                    $n = (int) $s->number;
                    $label = $n > 0 ? 'SDG '.$n.': ' : '';
                    $label .= (string) $s->name;
                    $checked = array_key_exists((string) $s->id, $selectedIds);

                    return $this->checkboxHtml($checked).' '.htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                })->values();

                $table = '<table width="98%" border="0" cellpadding="1" cellspacing="0" align="right">';
                foreach ($cells->chunk(2) as $pair) {
                    $left = $pair->get(0, '');
                    $right = $pair->get(1, '');
                    $table .= '<tr>'
                        .'<td width="50%" style="border:none;font-size:8.5pt">'.$left.'</td>'
                        .'<td width="50%" style="border:none;font-size:8.5pt">'.$right.'</td>'
                        .'</tr>';
                }
                $table .= '</table>';

                $pdf->writeHTML($table, true, false, true, false, '');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function writeRatingScale(TCPDF $pdf, array $settings): void
    {
        $rows = (array) ($settings['rating_scale'] ?? []);
        if ($rows === []) {
            return;
        }
        $pdf->Ln(4);
        $pdf->SetFont('times', 'B', 9);
        $pdf->Cell(0, 4, 'Rating scale:', 0, 1, 'L');

        $h = '<table width="100%" border="0" cellpadding="2" cellspacing="0" style="border:none">'
            .'<thead><tr><th width="20%" style="border:none" align="left"><b>Score</b></th>'
            .'<th width="20%" style="border:none" align="left"><b>Equivalent</b></th>'
            .'<th width="60%" style="border:none" align="left"><b>Narrative description</b></th></tr></thead><tbody>';
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $h .= '<tr><td width="20%" style="border:none">'.htmlspecialchars((string) ($row['range'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $h .= '</td><td width="20%" style="border:none">'.htmlspecialchars((string) ($row['equivalent'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $h .= '</td><td width="60%" style="border:none">'.htmlspecialchars((string) ($row['description'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $h .= '</td></tr>';
        }
        $h .= '</tbody></table>';
        $pdf->SetFont('times', '', 8.5);
        $pdf->writeHTML($h, true, false, true, false, '');
    }

    /**
     * @param  list<array<string, mixed>>  $lineItems
     * @param  array<string, mixed>  $settings
     */
    private function writeCriteriaTable(
        TCPDF $pdf,
        ?EvaluationFormat $format,
        array $lineItems,
    ): void {
        if ($format === null) {
            return;
        }
        $pdf->Ln(2);
        $pdf->SetFont('times', 'B', 9);
        $pdf->Cell(0, 4, 'Evaluation criteria and scores', 0, 1, 'L');

        $criterionById = EvaluationCriterion::query()
            ->where('evaluation_format_id', $format->id)
            ->orderBy('sort_order')
            ->get()
            ->keyBy(fn (EvaluationCriterion $c) => (string) $c->id);

        $lineById = [];
        foreach ($lineItems as $row) {
            if (is_array($row) && isset($row['criterion_id'])) {
                $lineById[(string) $row['criterion_id']] = $row;
            }
        }

        $scoreHeader = $format->isChecklist() ? 'Response' : 'Score';
        $h = '<table width="100%" border="1" cellpadding="2" cellspacing="0"><thead><tr style="background-color:#f2f2f2">'
            .'<th width="78%" align="left" style="padding:1mm"><b>Indicators</b></th>'
            .'<th width="22%" align="center" style="padding:1mm"><b>'.$scoreHeader.'</b></th></tr></thead><tbody>';

        foreach ($criterionById as $cId => $criterion) {
            $row = $lineById[$cId] ?? null;
            $section = $criterion->section_heading;
            if (is_string($section) && $section !== '') {
                $h .= '<tr><td colspan="2" style="background-color:#fafafa;padding:1mm"><b>'.htmlspecialchars($section, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</b></td></tr>';
            }
            $text = $this->criterionContentHtml($criterion);
            $scoreCell = '—';
            if (is_array($row) && array_key_exists('score', $row)) {
                if ($format->isChecklist()) {
                    $scoreCell = (int) $row['score'] >= 1 ? 'Yes' : 'No';
                } else {
                    $scoreCell = (string) (int) $row['score'];
                }
            }
            $h .= '<tr><td width="78%" style="font-size:8pt;padding:1mm">'.$text.'</td><td width="22%" align="center" style="font-size:8pt;padding:1mm"><b>'.htmlspecialchars($scoreCell, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</b></td></tr>';
        }

        $h .= '</tbody></table>';
        $pdf->SetFont('times', '', 8);
        $pdf->writeHTML($h, true, false, true, false, '');
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function writeSummary(TCPDF $pdf, ?EvaluationFormat $format, PanelDefenseEvaluation $evaluation, array $settings): void
    {
        $passing = (int) ($settings['passing_score'] ?? 85);
        $final = (int) $evaluation->final_score;

        $scoreLabel = 'Total';
        $scoreValue = (string) $final;
        if ($format?->isChecklist()) {
            $items = 0;
            if (is_array($evaluation->line_items)) {
                $items = count($evaluation->line_items);
            }
            $scoreValue .= $items > 0 ? ' / '.$items.' items' : '';
        } elseif ($format?->isScoring()) {
            $scoreLabel = 'Average / weighted total score';
            $scoreValue = $final.' / 100';
        }

        $passingNote = $format?->isScoring() ? ' <span style="font-size:8pt">(Passing score: '.$passing.')</span>' : '';
        $summaryRows = '<tr>'
            .'<td width="100%">'
            .'<b>'.htmlspecialchars($scoreLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').':</b> '
            .'<b>'.htmlspecialchars($scoreValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</b>'
            .$passingNote
            .'</td></tr>';

        if (! empty($settings['show_pass_fail']) && $format?->isScoring()) {
            $passed = $final >= $passing;
            $summaryRows .= '<tr>'
                .'<td width="100%"><b>Remarks:</b> '
                .$this->checkboxHtml($passed).' Passed&nbsp;&nbsp;&nbsp;&nbsp;'.$this->checkboxHtml(! $passed).' Failed'
                .'</td></tr>';
        }

        $pdf->SetFont('times', '', 9.5);
        $pdf->writeHTML(
            '<div style="padding-left: 40px; width: 96%;">
                <table width="100%" align="left" border="0" cellpadding="3" cellspacing="0">'.$summaryRows.'</table>
            </div>',
            true,
            false,
            true,
            false,
            '',
        );

        $pdf->Ln(2);
        $c = (string) ($evaluation->comments ?? '');
        if ($c === '') {
            $c = '—';
        }
        $pdf->writeHTML(
            '<div style="padding-left: 40px; width: 96%;">
            <table width="100%" align="left" border="0" cellpadding="3" cellspacing="0">'
            .'<tr><td width="100%"><b>Comments:</b></td></tr>'
            .'<tr><td width="100%" style="min-height:18mm;padding-left:6mm;padding-right:3mm;text-indent:6mm">'
            .nl2br(htmlspecialchars($c, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false)
            .'</td></tr></table>
            </div>',
            true,
            false,
            true,
            false,
            '',
        );
    }

    private function writeSignatureBlock(TCPDF $pdf): void
    {
        $pdf->Ln(4);
        $pdf->SetFont('times', '', 9);
        $pdf->writeHTML(
            '<table width="100%" border="0" cellpadding="2" cellspacing="0">'
            .'<tr><td width="62%" align="center">________________________________________</td><td width="38%" align="center">____________________</td></tr>'
            .'<tr><td width="62%" align="center" style="font-size:7.5pt">Signature above printed name of the panel member</td><td width="38%" align="center" style="font-size:7.5pt">Date</td></tr>'
            .'</table>',
            true,
            false,
            true,
            false,
            '',
        );
    }

    private function writeBodyBorder(TCPDF $pdf, int $startPage, float $startY): void
    {
        $currentPage = (int) $pdf->getPage();
        $currentY = $pdf->getY();
        $bottomY = $pdf->getPageHeight() - 18;
        $x = 12.0;
        $width = $pdf->getPageWidth() - 24.0;

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);

        for ($page = $startPage; $page <= $currentPage; $page++) {
            $pdf->setPage($page);
            $top = $page === $startPage ? $startY : 12.0;
            $bottom = $page === $currentPage
                ? min(max($currentY + 2, $top + 8), $bottomY)
                : $bottomY;

            $pdf->Rect($x, $top, $width, $bottom - $top, 'D');
        }

        $pdf->setPage($currentPage);
        $pdf->setY($currentY);
    }

    private function logoPath(array $settings): string
    {
        $path = trim((string) ($settings['logo_path'] ?? ''));
        if ($path === '') {
            return (string) config('evaluation_pdf.logo_path', '');
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/')) {
            $stored = storage_path('app/public/'.substr($path, strlen('storage/')));
            if (is_file($stored)) {
                return $stored;
            }
        }

        return public_path(ltrim($path, '/'));
    }

    private function drawHeaderLogo(TCPDF $pdf, array $settings, float $x, float $y, float $width, float $height): void
    {
        $logoPath = $this->logoPath($settings);
        $realLogo = $logoPath !== '' && is_file($logoPath) ? realpath($logoPath) : false;
        if (! is_string($realLogo) || $realLogo === '') {
            return;
        }

        $padding = 3.0;
        $imageX = $x + $padding;
        $imageY = $y + $padding;
        $imageWidth = $width - ($padding * 2);
        $imageHeight = $height - ($padding * 2);
        $extension = strtolower(pathinfo($realLogo, PATHINFO_EXTENSION));

        if ($extension === 'svg') {
            $pdf->ImageSVG($realLogo, $imageX, $imageY, $imageWidth, $imageHeight, '', '', '', 0, false);

            return;
        }

        $pdf->Image($realLogo, $imageX, $imageY, $imageWidth, $imageHeight, '', '', '', false, 300, '', false, false, 0, true);
    }

    private function criterionContentHtml(EvaluationCriterion $criterion): string
    {
        $raw = (string) ($criterion->content ?? '');
        if ($raw === '' || $this->plainTextFromHtml($raw) === '') {
            return htmlspecialchars((string) $criterion->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return $this->richTextForPdf($raw);
    }

    private function checkboxHtml(bool $checked): string
    {
        $glyph = $checked ? '☑' : '☐';

        return '<span style="font-family:dejavusans;font-size:8pt">'.$glyph.'</span>';
    }

    private function richTextForPdf(string $html): string
    {
        $html = (string) preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = (string) preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
        $html = strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><blockquote><h2><h3>');
        $html = preg_replace('/<(\/?)(p|strong|b|em|i|u|ul|ol|li|blockquote|h2|h3)\b[^>]*>/i', '<$1$2>', $html) ?? '';

        $html = preg_replace('/<p>/i', '', $html) ?? '';
        $html = preg_replace('/<\/p>/i', '<br/>', $html) ?? '';
        $html = preg_replace('/<ul>/i', '<ul style="margin:0;padding-left:1mm">', $html) ?? '';
        $html = preg_replace('/<ol>/i', '<ol style="margin:0;padding-left:1mm">', $html) ?? '';
        $html = preg_replace('/<li>/i', '<li style="margin:0;padding:0">', $html) ?? '';
        $html = preg_replace('/<blockquote>/i', '<blockquote style="margin:0;padding-left:1mm">', $html) ?? '';
        $html = preg_replace('/<h2>/i', '<b style="font-size:8.5pt">', $html) ?? '';
        $html = preg_replace('/<\/h2>/i', '</b><br/>', $html) ?? '';
        $html = preg_replace('/<h3>/i', '<b style="font-size:8pt">', $html) ?? '';
        $html = preg_replace('/<\/h3>/i', '</b><br/>', $html) ?? '';
        $html = preg_replace('/(<br\s*\/?>\s*){2,}/i', '<br/>', $html) ?? '';

        return '<span style="font-size:8pt">'.$html.'</span>';
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function writeFooterOnAllPages(TCPDF $pdf, array $settings): void
    {
        $doc = (array) ($settings['document'] ?? []);
        $code = (string) ($doc['code'] ?? '');
        $rev = (string) ($doc['revision'] ?? '');
        $eff = (string) ($doc['effectivity'] ?? '');
        $parts = [];
        if ($code !== '') {
            $parts[] = 'Form Code: '.$code;
        }
        if ($rev !== '') {
            $parts[] = 'Revision No.: '.$rev;
        }
        if ($eff !== '') {
            $parts[] = 'Effectivity Date: '.$eff;
        }
        $left = implode(' | ', $parts);

        if (method_exists($pdf, 'setFooterLeft')) {
            call_user_func([$pdf, 'setFooterLeft'], $left);
        }
    }

    /**
     * @return list<string>
     */
    private function proponentNameLines(?ResearchPaper $paper): array
    {
        if (! $paper) {
            return ['—'];
        }
        $names = collect();
        if ($paper->user?->name) {
            $names->push((string) $paper->user->name);
        }
        foreach ((array) ($paper->proponents ?? []) as $p) {
            if (is_array($p) && ! empty($p['name'])) {
                $names->push((string) $p['name']);
            }
        }

        if ($names->isEmpty()) {
            return ['—'];
        }

        return $names->filter()->unique()->values()->all();
    }

    private function plainTextFromHtml(string $html): string
    {
        $html = (string) preg_replace('/<br\s*\/?>/i', "\n", $html);
        $t = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = trim(preg_replace("/[\r\n\t]+/", ' ', $t) ?? '');

        return (string) preg_replace('/\s+/', ' ', $t) ?? '';
    }
}
