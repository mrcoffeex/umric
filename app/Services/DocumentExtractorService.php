<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

class DocumentExtractorService
{
    /**
     * Extract title, abstract, and keywords from an uploaded document.
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

        $paragraphs = $this->splitIntoParagraphs($text);

        return $this->detectTitleAndAbstract($paragraphs);
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

        return $this->detectTitleAndAbstract($paragraphs);
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
     * Apply heuristics to find the title and abstract from a paragraph list.
     *
     * @param  list<string>  $paragraphs
     * @return array{title: string, abstract: string}
     */
    private function detectTitleAndAbstract(array $paragraphs): array
    {
        $title = '';
        $abstract = '';

        // --- Title heuristic ---
        // First paragraph that looks like a title:
        //  • Not a lone number / Roman numeral / page marker
        //  • Not a keyword like "abstract", "introduction", "chapter…"
        //  • Short enough to be a title (≤ 300 chars)
        $titleBlocklist = ['abstract', 'introduction', 'acknowledgements', 'acknowledgments', 'references', 'bibliography'];

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

        // --- Abstract heuristic ---
        $count = count($paragraphs);
        for ($i = 0; $i < $count; $i++) {
            $lower = strtolower(trim($paragraphs[$i]));
            $startIdx = -1;
            $firstChunk = '';

            // Standalone "Abstract" heading — content starts on the next paragraph.
            if ($lower === 'abstract') {
                $startIdx = $i + 1;
            }
            // Inline "Abstract:" / "Abstract—" / "Abstract." prefix.
            elseif (preg_match('/^abstract[\s:—.\-–]+/i', $paragraphs[$i])) {
                $firstChunk = trim((string) preg_replace('/^abstract[\s:—.\-–]+/i', '', $paragraphs[$i]));
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

            // Strip a trailing "Keywords: ..." that sometimes bleeds into the abstract.
            $raw = trim(implode(' ', $parts));
            $raw = trim((string) preg_replace('/\s*keywords?[\s:—.\-–].*/i', '', $raw));
            $abstract = $raw;
            break;
        }

        return ['title' => $title, 'abstract' => $abstract];
    }

    /**
     * Detect whether a paragraph is a section heading that marks the end of the abstract.
     */
    private function isSectionBoundary(string $para): bool
    {
        $lower = strtolower(trim($para));
        $sectionHeadings = ['abstract', 'introduction', 'acknowledgements', 'acknowledgments', 'references', 'bibliography'];

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
