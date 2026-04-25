<?php

namespace App\Services;

use App\Models\EvaluationCriterion;
use App\Models\EvaluationFormat;
use App\Models\PanelDefense;
use App\Models\PanelDefenseEvaluation;
use App\Models\ResearchPaper;
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

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false, false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->setFontSubsetting(true);
        $pdf->SetFont('dejavusans', '', 9);

        $pdf->AddPage();
        $this->writeHeader($pdf, $settings);
        $this->writeTitleBlock($pdf, $format, $settings, $paper, $defense);
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

        $this->writeFooterOnAllPages($pdf, $settings);

        $pdf->lastPage();

        return $pdf->Output('', 'S');
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function writeHeader(TCPDF $pdf, array $settings): void
    {
        $logo = (string) config('evaluation_pdf.logo_path', '');
        if ($logo !== '' && is_file($logo)) {
            $pdf->Image($logo, 12, 12, 32, 0, '', '', '', true, 300, '', false, false, 0, false, false, false);
            $pdf->setY(12);
        } else {
            $pdf->SetFont('dejavusans', 'B', 10);
            $pdf->Cell(0, 4, 'University of Mindanao', 0, 1, 'C');
        }

        $pdf->SetFont('dejavusans', 'B', 10);
        $inst = (string) ($settings['header_institution'] ?? 'RESEARCH AND INNOVATION CENTER');
        $pdf->Cell(0, 4, $inst, 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', 7.5);
        $branches = (array) ($settings['branches'] ?? []);
        $line = [];
        foreach ($branches as $b) {
            if (! is_array($b) || ! isset($b['label'])) {
                continue;
            }
            $mark = ! empty($b['default']) ? '☑' : '☐';
            $line[] = $mark.' '.htmlspecialchars((string) $b['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        if ($line !== []) {
            $pdf->Cell(0, 3, implode('   ', $line), 0, 1, 'C');
        }
        $pdf->Ln(1);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function writeTitleBlock(
        TCPDF $pdf,
        ?EvaluationFormat $format,
        array $settings,
        ?ResearchPaper $paper,
        PanelDefense $defense,
    ): void {
        $title = (string) ($settings['form_title'] ?? 'DEFENSE EVALUATION');
        $sub = (string) ($settings['form_subtitle'] ?? '');
        $defLabel = (string) ($defense->defense_type_label ?? '');

        $html = '<table width="100%" border="1" cellpadding="3"><tr><td align="center"><b>'
            .htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</b><br><span style="font-size:8.5pt">'
            .htmlspecialchars($sub, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            .($defLabel !== '' ? '<br>'.htmlspecialchars($defLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '')
            .'</span></td></tr></table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        $paperTitle = $paper?->title !== null && $paper?->title !== '' ? (string) $paper->title : '—';
        $props = $this->formatProponents($paper);
        $pdf->Ln(2);
        $pdf->SetFont('dejavusans', 'B', 8);
        $pdf->writeHTML('Title:', true, false, true, false, '');
        $pdf->SetFont('dejavusans', '', 8.5);
        $pdf->writeHTML('<div style="min-height:12mm;border-bottom:0.1mm solid #000">'.htmlspecialchars($paperTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</div>', true, false, true, false, '');

        $pdf->SetFont('dejavusans', 'B', 8);
        $pdf->writeHTML('Proponent(s):', true, false, true, false, '');
        $pdf->SetFont('dejavusans', '', 8.5);
        $pdf->writeHTML('<div style="min-height:10mm;border-bottom:0.1mm solid #000">'.htmlspecialchars($props, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</div>', true, false, true, false, '');

        if ($format?->name) {
            $pdf->SetFont('dejavusans', '', 7.5);
            $pdf->Cell(0, 3, 'Rubric: '.htmlspecialchars($format->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), 0, 1, 'L');
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
        $pdf->Ln(2);
        $pdf->SetFont('dejavusans', 'B', 8.5);
        $pdf->Cell(0, 4, 'Rating scale:', 0, 1, 'L');

        $h = '<table width="100%" border="1" cellpadding="2" cellspacing="0"><thead><tr style="background-color:#eee">'
            .'<th width="20%"><b>Score / range</b></th><th width="20%"><b>Equivalent</b></th><th width="60%"><b>Narrative description</b></th></tr></thead><tbody>';
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $h .= '<tr><td>'.htmlspecialchars((string) ($row['range'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $h .= '</td><td>'.htmlspecialchars((string) ($row['equivalent'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $h .= '</td><td>'.htmlspecialchars((string) ($row['description'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $h .= '</td></tr>';
        }
        $h .= '</tbody></table>';
        $pdf->SetFont('dejavusans', '', 7.5);
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
        $pdf->SetFont('dejavusans', 'B', 8.5);
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

        $h = '<table width="100%" border="1" cellpadding="3" cellspacing="0"><thead><tr style="background-color:#f2f2f2">'
            .'<th width="80%"><b>Indicators</b></th><th width="20%" align="center"><b>'.($format->isChecklist() ? 'Response' : 'Score').'</b></th></tr></thead><tbody>';

        foreach ($criterionById as $cId => $criterion) {
            $row = $lineById[$cId] ?? null;
            $section = $criterion->section_heading;
            if (is_string($section) && $section !== '') {
                $h .= '<tr><td colspan="2" style="background-color:#fafafa"><b>'.htmlspecialchars($section, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</b></td></tr>';
            }
            $text = (string) ($criterion->content ?? '');
            if ($text === '' || $this->plainTextFromHtml($text) === '') {
                $text = htmlspecialchars((string) $criterion->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            } else {
                $text = nl2br(htmlspecialchars($this->plainTextFromHtml($text), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false);
            }
            $scoreCell = '—';
            if (is_array($row) && array_key_exists('score', $row)) {
                if ($format->isChecklist()) {
                    $scoreCell = (int) $row['score'] >= 1 ? 'Yes' : 'No';
                } else {
                    $scoreCell = (string) (int) $row['score'];
                }
            }
            $h .= '<tr><td>'.$text.'</td><td align="center"><b>'.htmlspecialchars($scoreCell, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</b></td></tr>';
        }

        $h .= '</tbody></table>';
        $pdf->SetFont('dejavusans', '', 7.5);
        $pdf->writeHTML($h, true, false, true, false, '');
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function writeSummary(TCPDF $pdf, ?EvaluationFormat $format, PanelDefenseEvaluation $evaluation, array $settings): void
    {
        $passing = (int) ($settings['passing_score'] ?? 85);
        $final = (int) $evaluation->final_score;
        $pdf->SetFont('dejavusans', 'B', 9);
        if ($format?->isChecklist()) {
            $items = 0;
            if (is_array($evaluation->line_items)) {
                $items = count($evaluation->line_items);
            }
            $pdf->Cell(0, 4, 'Total: '.$final.($items > 0 ? ' / '.$items.' items' : ''), 0, 1, 'L');
        } else {
            $pdf->Cell(0, 4, 'Average / weighted total score: '.$final.' / 100', 0, 1, 'L');
            $pdf->SetFont('dejavusans', '', 8);
            $pdf->Cell(0, 3, '(Passing score: '.$passing.')', 0, 1, 'L');
        }
        if (! empty($settings['show_pass_fail']) && $format?->isScoring()) {
            $passed = $final >= $passing;
            $boxP = $passed ? '☑' : '☐';
            $boxF = $passed ? '☐' : '☑';
            $pdf->SetFont('dejavusans', '', 8.5);
            $pdf->Cell(0, 4, 'Remarks:  '.$boxP.' Passed   '.$boxF.' Failed', 0, 1, 'L');
        }
        $pdf->Ln(1);
        $pdf->SetFont('dejavusans', 'B', 8.5);
        $pdf->Cell(0, 4, 'Comments:', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 8.5);
        $c = (string) ($evaluation->comments ?? '');
        if ($c === '') {
            $c = '—';
        }
        $pdf->writeHTML(
            '<div style="min-height:22mm;border:0.2mm solid #000;padding:2mm">'.nl2br(htmlspecialchars($c, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), false).'</div>',
            true,
            false,
            true,
            false,
            '',
        );
        $evalName = $evaluation->evaluator?->name;
        if (is_string($evalName) && $evalName !== '') {
            $pdf->Ln(2);
            $pdf->SetFont('dejavusans', '', 7.5);
            $pdf->Cell(0, 3, 'Evaluator: '.htmlspecialchars($evalName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), 0, 1, 'L');
        }
    }

    private function writeSignatureBlock(TCPDF $pdf): void
    {
        $pdf->Ln(3);
        $pdf->SetFont('dejavusans', '', 8.5);
        $pdf->writeHTML(
            '<div style="min-height:12mm;border-bottom:0.2mm solid #000"></div><div style="text-align:center;font-size:7.5pt">Signature above printed name of panel member</div>',
            true,
            false,
            true,
            false,
            '',
        );
        $pdf->Ln(2);
        $pdf->Cell(0, 4, 'Date: ___________________________', 0, 1, 'L');
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
        $left = trim($code.($code !== '' && $rev !== '' ? ' / ' : ' ').'Rev. #'.$rev.($eff !== '' ? ' / Effectivity: '.$eff : ''));
        if ($left === '') {
            return;
        }
        $np = (int) $pdf->getNumPages();
        for ($i = 1; $i <= $np; $i++) {
            $pdf->setPage($i);
            $pdf->setY(-12);
            $pdf->SetFont('dejavusans', '', 6.5);
            $pdf->Cell(90, 3, $left, 0, 0, 'L');
            $label = 'Page '.$i.' of '.$np;
            $pdf->Cell(90, 3, $label, 0, 0, 'R');
        }
    }

    private function formatProponents(?ResearchPaper $paper): string
    {
        if (! $paper) {
            return '—';
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
            return '—';
        }

        return $names->filter()->unique()->implode(', ');
    }

    private function plainTextFromHtml(string $html): string
    {
        $html = (string) preg_replace('/<br\s*\/?>/i', "\n", $html);
        $t = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = trim(preg_replace("/[\r\n\t]+/", ' ', $t) ?? '');

        return (string) preg_replace('/\s+/', ' ', $t) ?? '';
    }
}
