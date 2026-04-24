import { unzip } from 'fflate';
import { ref } from 'vue';

export type ExtractState =
    | 'idle'
    | 'extracting'
    | 'success'
    | 'partial'
    | 'failed';

/** `abstract` matches the API/DB field; content is the title-proposal rationale. */
export interface ExtractResult {
    title: string;
    abstract: string;
    keywords: string[];
}

// ─── Shared helpers ────────────────────────────────────────────────────────────

const STOPWORDS = new Set([
    'a',
    'an',
    'the',
    'and',
    'or',
    'but',
    'is',
    'are',
    'was',
    'were',
    'be',
    'been',
    'being',
    'have',
    'has',
    'had',
    'do',
    'does',
    'did',
    'will',
    'would',
    'shall',
    'should',
    'may',
    'might',
    'must',
    'can',
    'could',
    'that',
    'this',
    'these',
    'those',
    'it',
    'its',
    'in',
    'on',
    'at',
    'by',
    'for',
    'with',
    'about',
    'against',
    'between',
    'into',
    'through',
    'during',
    'of',
    'to',
    'from',
    'up',
    'down',
    'out',
    'off',
    'over',
    'under',
    'again',
    'further',
    'then',
    'once',
    'here',
    'there',
    'when',
    'where',
    'why',
    'how',
    'all',
    'both',
    'each',
    'few',
    'more',
    'most',
    'other',
    'some',
    'such',
    'no',
    'not',
    'only',
    'same',
    'so',
    'than',
    'too',
    'very',
    's',
    't',
    'just',
    'also',
    'as',
    'if',
    'while',
    'which',
    'who',
    'whom',
    'what',
    'we',
    'they',
    'he',
    'she',
    'i',
    'you',
    'me',
    'him',
    'her',
    'us',
    'them',
    'my',
    'your',
    'his',
    'our',
    'their',
    'any',
    'paper',
    'study',
    'research',
    'using',
    'used',
    'use',
    'based',
    'results',
    'result',
]);

const TITLE_BLOCKLIST = new Set([
    'abstract',
    'introduction',
    'acknowledgements',
    'acknowledgments',
    'references',
    'bibliography',
    'contents',
    'table of contents',
]);

function extractKeywords(text: string, count = 5): string[] {
    const words = text
        .toLowerCase()
        .replace(/[^a-z\s]/g, ' ')
        .split(/\s+/)
        .filter((w) => w.length > 3 && !STOPWORDS.has(w) && /^[a-z]/.test(w));

    const freq = new Map<string, number>();

    for (const w of words) {
        freq.set(w, (freq.get(w) ?? 0) + 1);
    }

    const sorted = [...freq.entries()]
        .filter(([, n]) => n > 1)
        .sort((a, b) => b[1] - a[1]);

    // Fall back to single-occurrence words if we don't have enough.
    const pool =
        sorted.length >= count
            ? sorted
            : [...freq.entries()].sort((a, b) => b[1] - a[1]);

    return pool.slice(0, count).map(([w]) => w);
}

const SECTION_BOUNDARY_RE =
    /^(introduction|keywords?|background|methodology|methods?|related\s+work|conclusion|acknowledgements?|references|bibliography|\d+[.\s])/i;

function isSectionBoundary(para: string): boolean {
    const t = para.trim();

    if (TITLE_BLOCKLIST.has(t.toLowerCase())) {
        return true;
    }

    if (SECTION_BOUNDARY_RE.test(t)) {
        return true;
    }

    // Short all-caps line is almost certainly a section heading.
    if (t.length < 60 && t === t.toUpperCase() && /[A-Z]/.test(t)) {
        return true;
    }

    return false;
}

/** Parse local DOCX text: map document sections to title + rationale (`abstract` key) + keywords. */
function parseTitleRationaleAndKeywords(paragraphs: string[]): ExtractResult {
    let title = '';
    let abstract = '';

    for (const para of paragraphs) {
        if (para.length < 3 || /^\d+$/.test(para)) {
            continue;
        }

        const lower = para.toLowerCase().trim();

        if (TITLE_BLOCKLIST.has(lower)) {
            continue;
        }

        if (/^(chapter|section|page)\s*\d+/i.test(para)) {
            continue;
        }

        if (para.length <= 300) {
            title = para;
            break;
        }
    }

    for (let i = 0; i < paragraphs.length; i++) {
        const lower = paragraphs[i].toLowerCase().trim();
        let startIdx = -1;
        let firstChunk = '';

        if (lower === 'abstract') {
            startIdx = i + 1;
        } else if (/^abstract[\s:—.–-]+/i.test(paragraphs[i])) {
            firstChunk = paragraphs[i]
                .replace(/^abstract[\s:—.–-]+/i, '')
                .trim();
            startIdx = i + 1;
        }

        if (startIdx === -1) {
            continue;
        }

        const parts: string[] = firstChunk ? [firstChunk] : [];

        for (let j = startIdx; j < paragraphs.length; j++) {
            if (isSectionBoundary(paragraphs[j])) {
                break;
            }

            parts.push(paragraphs[j].trim());

            if (parts.join(' ').length > 2000) {
                break;
            }
        }

        // Strip a trailing "Keywords: ..." that sometimes bleeds into the body.
        let raw = parts.join(' ').trim();
        raw = raw.replace(/\s*keywords?[\s:—.–-].*/i, '').trim();

        // Advance past any leading tokens that are not valid starting words:
        // non-letters, single letters, or Roman numerals (i, ii, iii, iv, v, vi…, x…).
        const ROMAN_RE =
            /^m{0,4}(cm|cd|d?c{0,3})(xc|xl|l?x{0,3})(ix|iv|v?i{0,3})$/i;
        const words = raw.split(/\s+/);
        let startAt = -1;

        for (let wi = 0; wi < words.length; wi++) {
            const w = words[wi].replace(/^[^A-Za-z]+|[^A-Za-z]+$/g, '');

            if (w.length >= 2 && !ROMAN_RE.test(w)) {
                startAt = wi;
                break;
            }
        }

        // No valid starting word found in this candidate — keep searching.
        if (startAt === -1) {
            continue;
        }

        const candidate = words
            .slice(startAt)
            .join(' ')
            .replace(/^[^A-Za-z]+/, '')
            .trim();

        // A real rationale block is at least a sentence; skip short fragments and keep looking.
        if (candidate.split(/\s+/).filter(Boolean).length < 10) {
            continue;
        }

        abstract = candidate;
        break;
    }

    const keywords = extractKeywords(paragraphs.join(' '));

    return { title, abstract, keywords };
}

// ─── DOCX (client-side via fflate) ─────────────────────────────────────────────

async function extractDocx(file: File): Promise<ExtractResult> {
    const buffer = await file.arrayBuffer();
    const uint8 = new Uint8Array(buffer);

    return new Promise((resolve) => {
        unzip(uint8, (err, files) => {
            const xmlBytes = files?.['word/document.xml'];

            if (err || !xmlBytes) {
                resolve({ title: '', abstract: '', keywords: [] });

                return;
            }

            const xmlText = new TextDecoder().decode(xmlBytes);
            const NS =
                'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
            let doc: Document;

            try {
                doc = new DOMParser().parseFromString(
                    xmlText,
                    'application/xml',
                );
            } catch {
                resolve({ title: '', abstract: '', keywords: [] });

                return;
            }

            const paragraphs: string[] = [];
            const paras = doc.getElementsByTagNameNS(NS, 'p');

            for (const para of paras) {
                const runs = para.getElementsByTagNameNS(NS, 't');
                let text = '';

                for (const run of runs) {
                    text += run.textContent ?? '';
                }

                text = text.trim();

                if (text) {
                    paragraphs.push(text);
                }
            }

            resolve(parseTitleRationaleAndKeywords(paragraphs));
        });
    });
}

// ─── PDF (server-side, small files only to avoid 413) ──────────────────────────

/** Max file size to attempt server-side PDF extraction, read from VITE_UPLOAD_MAX_SIZE_MB. */
const PDF_MAX_BYTES =
    Number(import.meta.env.VITE_UPLOAD_MAX_SIZE_MB ?? 25) * 1024 * 1024;

async function extractPdfViaServer(
    file: File,
    extractUrl: string,
    csrfToken: string,
): Promise<ExtractResult> {
    if (file.size > PDF_MAX_BYTES) {
        // File too large for the server's upload limit — skip gracefully.
        return { title: '', abstract: '', keywords: [] };
    }

    const body = new FormData();
    body.append('file', file);

    const res = await fetch(extractUrl, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
        body,
    });

    if (!res.ok) {
        return { title: '', abstract: '', keywords: [] };
    }

    const data = await res.json();

    return {
        title: data.title ?? '',
        abstract: data.abstract ?? '',
        keywords: Array.isArray(data.keywords)
            ? (data.keywords as string[])
            : [],
    };
}

// ─── Composable ────────────────────────────────────────────────────────────────

function getCsrf(): string {
    return decodeURIComponent(
        document.cookie
            .split('; ')
            .find((c) => c.startsWith('XSRF-TOKEN='))
            ?.split('=')[1] ?? '',
    );
}

export function useDocumentExtractor(extractUrl: string) {
    const state = ref<ExtractState>('idle');

    async function extract(file: File): Promise<ExtractResult | null> {
        const ext = file.name.split('.').pop()?.toLowerCase();

        if (ext !== 'pdf' && ext !== 'docx') {
            return null;
        }

        state.value = 'extracting';

        try {
            const result =
                ext === 'docx'
                    ? await extractDocx(file)
                    : await extractPdfViaServer(file, extractUrl, getCsrf());

            const hasTitle = result.title.length > 0;
            const hasAbstract = result.abstract.length > 0;
            const hasKeywords = result.keywords.length > 0;

            if (!hasTitle && !hasAbstract && !hasKeywords) {
                state.value = 'failed';
            } else if (hasTitle && hasAbstract) {
                state.value = 'success';
            } else {
                state.value = 'partial';
            }

            return result;
        } catch {
            state.value = 'failed';

            return null;
        }
    }

    function reset() {
        state.value = 'idle';
    }

    return { state, extract, reset };
}
