<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

class DocumentExtractorService
{
    /**
     * Extract title, rationale (stored in `abstract` column), and keywords from an uploaded document.
     *
     * @return array{title: string, abstract: string, keywords: list<string>}
     */
    public function extract(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        $base = match ($extension) {
            'pdf' => $this->extractPdf($path),
            'docx' => $this->extractDocx($path),
            default => ['title' => '', 'abstract' => ''],
        };

        return array_merge($base, [
            'keywords' => $this->extractKeywords(($base['title'] ?? '').' '.($base['abstract'] ?? '')),
        ]);
    }

    /**
     * Extract text paragraphs from a PDF using smalot/pdfparser.
     *
     * @return array{title: string, abstract: string}
     */
    private function extractPdf(string $path): array
    {
        try {
            $parser = new PdfParser;
            $pdf = $parser->parseFile($path);
            $text = $pdf->getText();
        } catch (\Throwable) {
            return ['title' => '', 'abstract' => ''];
        }

        $text = trim($text);
        if ($text === '') {
            return ['title' => '', 'abstract' => ''];
        }

        $paragraphs = $this->splitForPdfExtraction($text);
        $result = $this->detectTitleAndRationale($paragraphs);

        if ($result['title'] !== '' && $result['abstract'] === '' && count($paragraphs) >= 2) {
            $first = trim($paragraphs[0]);
            if ($first === $result['title'] || str_starts_with($first, $result['title'])) {
                $result['abstract'] = Str::limit(
                    trim(implode(' ', array_slice($paragraphs, 1))),
                    2000,
                    '',
                );
            }
        }

        if ($result['title'] === '' && $result['abstract'] === '') {
            $result = $this->fallbackFromUnstructuredText($text);
        }

        return $result;
    }

    /**
     * Extract text paragraphs from a DOCX using ZipArchive + SimpleXML.
     *
     * @return array{title: string, abstract: string}
     */
    private function extractDocx(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return ['title' => '', 'abstract' => ''];
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            return ['title' => '', 'abstract' => ''];
        }

        try {
            $doc = new \SimpleXMLElement($xml);
        } catch (\Throwable) {
            return ['title' => '', 'abstract' => ''];
        }

        $doc->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $paragraphs = [];
        $paras = $doc->xpath('//w:p') ?: [];

        foreach ($paras as $para) {
            $runs = $para->xpath('.//w:t') ?: [];
            $text = '';

            foreach ($runs as $run) {
                $text .= (string) $run;
            }

            $text = trim($text);

            if ($text !== '') {
                $paragraphs[] = $text;
            }
        }

        return $this->detectTitleAndRationale($paragraphs);
    }

    /**
     * Split a block of text into non-empty paragraphs.
     *
     * @return list<string>
     */
    private function splitIntoParagraphs(string $text): array
    {
        // Normalise line endings, then split on blank lines.
        $text = str_replace("\r\n", "\n", $text);
        $chunks = preg_split('/\n{2,}/', $text) ?: [];

        $paragraphs = [];

        foreach ($chunks as $chunk) {
            // Collapse internal whitespace / newlines inside a paragraph.
            $line = trim(preg_replace('/\s+/', ' ', $chunk) ?? '');

            if ($line !== '') {
                $paragraphs[] = $line;
            }
        }

        return $paragraphs;
    }

    /**
     * PDFs often return one long line or one huge block. Split so title/rationale heuristics can work.
     *
     * @return list<string>
     */
    private function splitForPdfExtraction(string $text): array
    {
        $basic = $this->splitIntoParagraphs($text);

        if (count($basic) > 1) {
            return $basic;
        }

        if (count($basic) === 1) {
            $one = $basic[0];
            if (strlen($one) < 500) {
                return $basic;
            }

            // One huge block: split on hard line breaks first.
            $lines = array_values(
                array_filter(
                    array_map('trim', preg_split('/\R+/u', $one) ?: []),
                    static fn (string $l) => $l !== '',
                )
            );
            if (count($lines) > 1) {
                return $lines;
            }

            // Single line, very long: split on sentence end for paragraph-like chunks.
            $sentences = preg_split('/(?<=[.!?…])\s+/u', $one) ?: [];
            $out = array_values(
                array_filter(
                    array_map('trim', $sentences),
                    static fn (string $s) => strlen($s) > 2,
                )
            );

            if (count($out) > 1) {
                return $out;
            }
        }

        return $basic;
    }

    /**
     * Best-effort when no "Abstract" / "Rationale" section was found (common in PDFs).
     *
     * @return array{title: string, abstract: string}
     */
    private function fallbackFromUnstructuredText(string $text): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if (mb_strlen($text) < 10) {
            return ['title' => '', 'abstract' => ''];
        }

        if (mb_strlen($text) <= 200) {
            return ['title' => $text, 'abstract' => ''];
        }

        $title = $this->truncateAtLastWord($text, 200);
        $rest = mb_substr($text, mb_strlen($title));
        $rest = ltrim((string) preg_replace("/^[\s:–—\-.]+/u", '', $rest));

        if ($rest === '' || $rest === $text) {
            $rest = (string) Str::substr($text, 200, 2000);
        }

        return [
            'title' => $title,
            'abstract' => $rest === '' ? '' : Str::limit($rest, 2000, ''),
        ];
    }

    private function truncateAtLastWord(string $text, int $maxLen): string
    {
        if (mb_strlen($text) <= $maxLen) {
            return $text;
        }

        $slice = mb_substr($text, 0, $maxLen);
        $pos = mb_strrpos($slice, ' ');

        if ($pos !== false && $pos > 15) {
            return mb_substr($text, 0, $pos);
        }

        return (string) Str::of($text)->limit($maxLen, '');
    }

    /**
     * Find title and body text (rationale) from a paragraph list. Source PDFs often use an "Abstract" section;
     * the body is returned under the `abstract` key for API/DB compatibility.
     *
     * @param  list<string>  $paragraphs
     * @return array{title: string, abstract: string}
     */
    private function detectTitleAndRationale(array $paragraphs): array
    {
        $title = '';
        $abstract = '';

        // --- Title heuristic ---
        // First paragraph that looks like a title:
        //  • Not a lone number / Roman numeral / page marker
        //  • Not a keyword like "abstract", "introduction", "chapter…"
        //  • Short enough to be a title (≤ 300 chars)
        $titleBlocklist = ['abstract', 'rationale', 'introduction', 'acknowledgements', 'acknowledgments', 'references', 'bibliography'];

        foreach ($paragraphs as $para) {
            if (strlen($para) < 3) {
                continue;
            }

            if (is_numeric($para)) {
                continue;
            }

            $lower = strtolower(trim($para));

            if (in_array($lower, $titleBlocklist, true)) {
                continue;
            }

            if (preg_match('/^(chapter|section|page)\s*\d+/i', $para)) {
                continue;
            }

            if (strlen($para) <= 300) {
                $title = $para;
                break;
            }
        }

        // --- Body / rationale heuristic (locates common "Abstract" section heading in source files) ---
        $count = count($paragraphs);
        for ($i = 0; $i < $count; $i++) {
            $lower = strtolower(trim($paragraphs[$i]));
            $startIdx = -1;
            $firstChunk = '';

            // Standalone section headings.
            if ($lower === 'abstract' || $lower === 'rationale') {
                $startIdx = $i + 1;
            }
            // Inline "Abstract:" / "Rationale:" (and similar) prefix on same line.
            elseif (preg_match('/^(abstract|rationale)[\s:—.\-–]+/i', $paragraphs[$i])) {
                $firstChunk = trim((string) preg_replace('/^(abstract|rationale)[\s:—.\-–]+/i', '', $paragraphs[$i]));
                $startIdx = $i + 1;
            }

            if ($startIdx === -1) {
                continue;
            }

            $parts = $firstChunk !== '' ? [$firstChunk] : [];
            for ($j = $startIdx; $j < $count; $j++) {
                if ($this->isSectionBoundary($paragraphs[$j])) {
                    break;
                }
                $parts[] = trim($paragraphs[$j]);
                if (strlen(implode(' ', $parts)) > 2000) {
                    break;
                }
            }

            // Strip a trailing "Keywords: ..." that sometimes bleeds into the body.
            $raw = trim(implode(' ', $parts));
            $raw = trim((string) preg_replace('/\s*keywords?[\s:—.\-–].*/i', '', $raw));
            $abstract = $raw;
            break;
        }

        return ['title' => $title, 'abstract' => $abstract];
    }

    /**
     * Whether a paragraph is a section heading that ends the title-proposal body text.
     */
    private function isSectionBoundary(string $para): bool
    {
        $lower = strtolower(trim($para));
        $sectionHeadings = ['abstract', 'rationale', 'introduction', 'acknowledgements', 'acknowledgments', 'references', 'bibliography'];

        if (in_array($lower, $sectionHeadings, true)) {
            return true;
        }

        if (preg_match('/^(introduction|keywords?|background|methodology|methods?|related\s+work|conclusion|acknowledgements?|references|bibliography|\d+[.\s])/i', $para)) {
            return true;
        }

        // Short all-caps line is almost certainly a section heading.
        $trimmed = trim($para);
        if (strlen($trimmed) < 60 && $trimmed === strtoupper($trimmed) && preg_match('/[A-Z]/', $trimmed)) {
            return true;
        }

        return false;
    }

    /**
     * Extract the top N keywords from a block of text.
     *
     * @return list<string>
     */
    private function extractKeywords(string $text, int $count = 5): array
    {
        static $stopwords = [
            'a', 'an', 'the', 'and', 'or', 'but', 'is', 'are', 'was', 'were', 'be',
            'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
            'shall', 'should', 'may', 'might', 'must', 'can', 'could', 'that', 'this',
            'these', 'those', 'it', 'its', 'in', 'on', 'at', 'by', 'for', 'with', 'about',
            'against', 'between', 'into', 'through', 'during', 'of', 'to', 'from', 'up',
            'down', 'out', 'off', 'over', 'under', 'again', 'further', 'then', 'once',
            'here', 'there', 'when', 'where', 'why', 'how', 'all', 'both', 'each', 'few',
            'more', 'most', 'other', 'some', 'such', 'no', 'not', 'only', 'same', 'so',
            'than', 'too', 'very', 's', 't', 'just', 'also', 'as', 'if', 'while', 'which',
            'who', 'whom', 'what', 'we', 'they', 'he', 'she', 'i', 'you', 'me', 'him',
            'her', 'us', 'them', 'my', 'your', 'his', 'our', 'their', 'any', 'paper',
            'study', 'research', 'using', 'used', 'use', 'based', 'results', 'result',
        ];

        // Lowercase, strip non-alpha, split on whitespace.
        $words = preg_split('/\s+/', preg_replace('/[^a-z\s]/i', ' ', strtolower($text))) ?: [];

        $freq = [];

        foreach ($words as $word) {
            if (strlen($word) < 4) {
                continue;
            }

            if (in_array($word, $stopwords, true)) {
                continue;
            }

            $freq[$word] = ($freq[$word] ?? 0) + 1;
        }

        // Sort by frequency descending.
        arsort($freq);

        // Return the top N words that appear more than once.
        $keywords = array_keys(array_filter($freq, fn ($n) => $n > 1));

        if (count($keywords) < $count) {
            // Fallback: take single-occurrence words if not enough.
            $keywords = array_keys($freq);
        }

        return array_slice($keywords, 0, $count);
    }
}
