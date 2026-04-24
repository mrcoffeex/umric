#!/usr/bin/env python3
"""
One-off: replace in-page nav + card grid in student Research/Show.vue with tabbed layout.
"""
from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
FILE = ROOT / "resources/js/pages/student/Research/Show.vue"

NEW_IMPORT = """import { computed, ref } from 'vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { getStepBadgeClass } from '@/lib/step-colors';
"""

# Remove inPageNavItems block
REMOVE_INPAGE = re.compile(
    r"\nconst inPageNavItems = computed\(\): \{ id: string; label: string \}\[] => \{"
    r".*?^\};\n",
    re.MULTILINE | re.DOTALL,
)

# Remove old nav import
OLD_NAV = "import ResearchShowInPageNav from '@/components/research/ResearchShowInPageNav.vue';\n"

TAB_BLOCK_START = """        <div class="overflow-hidden rounded-2xl border border-border bg-card">
            <Tabs
                default-value="steps"
                :unmount-on-hide="false"
                class="w-full"
            >
                <div
                    class="sticky top-0 z-[5] border-b border-border/80 bg-muted/30 px-2 py-2"
                >
                    <TabsList
                        class="h-auto w-full min-h-10 flex-nowrap sm:flex-wrap"
                    >
                        <TabsTrigger value="steps">Progress</TabsTrigger>
                        <TabsTrigger value="qr">QR & links</TabsTrigger>
                        <TabsTrigger value="history">History</TabsTrigger>
                        <TabsTrigger value="comments">Comments</TabsTrigger>
                        <TabsTrigger value="files">Files</TabsTrigger>
                        <TabsTrigger value="panels">Panels</TabsTrigger>
                        <TabsTrigger value="paper">Paper</TabsTrigger>
                    </TabsList>
                </div>
                <div class="p-4 md:p-5">
                    <TabsContent value="qr" class="p-0">
            """

TAB_SEP_QR = """
                    </TabsContent>
                    <TabsContent value="steps" class="p-0">
            """

# ... we build the rest in main after extracting


def _strip_card_section(open_line: str) -> str:
    return re.sub(
        r"^(?P<sp>\s*)<section(?P<att>[^>]*)>\s*$",
        lambda m: f'{m.group("sp")}<div class="space-y-4">',
        open_line,
        count=1,
    )

def main() -> None:
    text = FILE.read_text()
    if "ResearchShowInPageNav" not in text:
        print("Already patched?")
        return
    if OLD_NAV in text:
        text = text.replace(OLD_NAV, "", 1)
    text = text.replace(
        "import { computed, ref } from 'vue';\nimport ResearchShowInPageNav",
        NEW_IMPORT,
        1,
    )
    m = REMOVE_INPAGE.search(text)
    if m:
        text = REMOVE_INPAGE.sub("\n", text, count=1)
    # hero: remove id and scroll
    text = text.replace(
        'id="research-show-overview"\n            class="scroll-mt-20 overflow-hidden',
        'class="overflow-hidden',
        1,
    )
    a = "        <ResearchShowInPageNav :items=\"inPageNavItems\" />\n\n        <!-- QR Code Panel -->\n        <section\n            id=\"research-show-qr\"\n            class=\"scroll-mt-24 overflow-hidden rounded-2xl border border-border bg-card\"\n        >\n            <div class=\"flex items-start gap-3 p-4\">"
    b = TAB_BLOCK_START + "            <div class=\"flex items-start gap-3 p-1 sm:p-2\">"
    if a not in text:
        print("Anchor A not found")
        return
    text = text.replace(a, b, 1)
    c = "            </div>\n        </section>\n\n        <!-- Body Grid -->\n        <div class=\"grid gap-6 xl:grid-cols-[2fr_1fr]\">\n            <!-- Left: Step Details + History -->\n            <div class=\"space-y-6\">"
    d = TAB_SEP_QR
    if c not in text:
        print("Anchor C not found")
        return
    text = text.replace(c, d, 1)
    # first section: steps
    text = text.replace(
        "                <section\n                    id=\"research-show-steps\"\n                    class=\"scroll-mt-24 rounded-2xl border border-border bg-card p-5\"\n                >",
        "            <div class=\"space-y-4\">",
        1,
    )
    text = text.replace(
        "                </section>\n\n                <!-- Tracking History -->\n                <section\n                    id=\"research-show-history\"",
        "            </div>\n                    </TabsContent>\n                    <TabsContent value=\"history\" class=\"p-0\">\n                <section\n",
        1,
    )
    # fix history: remove id/class from first section
    text = text.replace(
        '                    class="scroll-mt-24 rounded-2xl border border-border bg-card p-5"\n                >\n                    <div class="mb-4 flex items-center gap-2">',
        '                class="space-y-4">\n                    <div class="mb-4 flex items-center gap-2">',
        1,
    )
    text = text.replace(
        "                </section>\n                <!-- Comments (read-only) -->\n                <section\n                    id=\"research-show-comments\"",
        "            </div>\n                    </TabsContent>\n                    <TabsContent value=\"comments\" class=\"p-0\">\n                <section\n",
        1,
    )
    text = text.replace(
        '                    class="scroll-mt-24 rounded-2xl border border-border bg-card p-5"\n                >\n                    <div class="mb-4 flex items-center gap-2">\n                        <MessageSquare',
        '                class="space-y-4">\n                    <div class="mb-4 flex items-center gap-2">\n                        <MessageSquare',
        1,
    )
    # documents: remove v-if on section, move to content
    text = text.replace(
        "                </section>\n\n                <!-- Documents -->\n                <section\n                    v-if=\"paper.files && paper.files.length > 0\"\n                    id=\"research-show-documents\"",
        "            </div>\n                    </TabsContent>\n                    <TabsContent value=\"files\" class=\"p-0\">\n                <div v-if=\"paper.files && paper.files.length > 0\"",
        1,
    )
    text = text.replace(
        '                    class="scroll-mt-24 rounded-2xl border border-border bg-card p-5"\n                >\n                    <div class="mb-4 flex items-center gap-2">\n                        <svg',
        '                class="space-y-4">\n                    <div class="mb-4 flex items-center gap-2">\n                        <svg',
        1,
    )
    text = text.replace(
        "                    </div>\n                </section>\n            </div>\n\n            <!-- Right Sidebar -->\n            <div class=\"space-y-6\">",
        "                    </div>\n                </div>\n                <div\n                    v-else\n                    class=\"rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground\"\n                >\n                    No files uploaded yet.\n                </div>\n            </div>\n                    </TabsContent>\n                    <TabsContent value=\"panels\" class=\"p-0\">",
        1,
    )
    text = text.replace(
        "                <section\n                    v-if=\"(panelDefenses ?? []).length\"\n                    id=\"research-show-panels\"",
        "                <div v-if=\"(panelDefenses ?? []).length\"",
        1,
    )
    text = text.replace(
        '                    class="scroll-mt-24 rounded-2xl border border-border bg-card p-5"\n                >\n                    <div class="mb-4 flex items-center gap-2">\n                        <Users',
        '                class="space-y-4">\n                    <div class="mb-4 flex items-center gap-2">\n                        <Users',
        1,
    )
    text = text.replace(
        "                    </div>\n                </section>\n\n                <!-- Paper Info -->\n                <section\n                    id=\"research-show-paper\"",
        "                    </div>\n                </div>\n                <div\n                    v-else\n                    class=\"rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground\"\n                >\n                    No panel defense records yet.\n                </div>\n            </div>\n                    </TabsContent>\n                    <TabsContent value=\"paper\" class=\"p-0\">\n                <div class=\"space-y-4\">",
        1,
    )
    # remove old paper section opening
    text = text.replace(
        '                    class="scroll-mt-24 rounded-2xl border border-border bg-card p-5"\n                >\n                    <h3\n                        class="mb-4 flex items-center gap-2 text-base font-bold text-foreground"\n                    >\n                        <FileSearch class="h-5 w-5 text-orange-500" />\n                        Paper Info\n                    </h3>',
        '                <div\n                    v-if="\n                        paper.current_step === \'plagiarism_check\' &&\n                        (paper.plagiarism_attempts ?? 0) >= 2\n                    "\n                    class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/20"\n                >\n                    <div class="flex items-start gap-3">\n                        <AlertTriangle\n                            class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400"\n                        />\n                        <div>\n                            <p\n                                class="text-sm font-bold text-amber-700 dark:text-amber-300"\n                            >\n                                Plagiarism Check Warning\n                            </p>\n                            <p\n                                class="mt-1 text-xs text-amber-600 dark:text-amber-400"\n                            >\n                                You have used {{ paper.plagiarism_attempts }} of\n                                3 attempts.\n                                <template\n                                    v-if="(paper.plagiarism_attempts ?? 0) >= 3"\n                                    >No more attempts remaining.</template\n                                >\n                                <template v-else>1 attempt remaining.</template>\n                            </p>\n                        </div>\n                    </div>\n                </div>\n                <h3\n                    class="mb-4 flex items-center gap-2 text-base font-bold text-foreground"\n                >\n                    <FileSearch class="h-5 w-5 text-orange-500" />\n                    Paper info\n                </h3>',
        1,
    )
    # remove duplicate alerts at bottom
    text = text.replace(
        re.compile(
            r"\n                <!-- Alerts -->\n                <section\n                    v-if=\"\n                        paper.current_step === 'plagiarism_check' &&\n                        \(paper.plagiarism_attempts \?\? 0\) >= 2\n                    \"\n                    id=\"research-show-plagiarism\"\n                    class=\"scroll-mt-24 rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950/20\"\n                >\n                    <div class=\"flex items-start gap-3\">.*?</div>\n                </section>\n            </div>\n        </div>\n    </div>\n",
            re.DOTALL,
        ),
        "\n                </div>\n                    </TabsContent>\n                </div>\n            </Tabs>\n        </div>\n    </div>\n",
    )
    if '                </div>\n                    </TabsContent>\n                </div>\n            </Tabs>\n        </div>\n    </div>\n' not in text:
        print("Final replace may have failed; check manually")
    FILE.write_text(text)
    print("Wrote", FILE)


if __name__ == "__main__":
    main()
