#!/usr/bin/env python3
"""Assemble tabbed main region for resources/js/pages/student/Research/Show.vue.
Requires: /tmp/umric-tabs/{qr,steps,history,comments,files,panels}.txt
(paper.txt lines 1045-1203 of original file) — run the sed in comments first.
"""
from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
FILE = ROOT / "resources/js/pages/student/Research/Show.vue"
PARTS = Path("/tmp/umric-tabs")

PLAGIARISM_BLOCK = """
                <div
                    v-if="paper.current_step === 'plagiarism_check' && (paper.plagiarism_attempts ?? 0) >= 2"
                    class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/20"
                >
                    <div class="flex items-start gap-3">
                        <AlertTriangle
                            class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400"
                        />
                        <div>
                            <p
                                class="text-sm font-bold text-amber-700 dark:text-amber-300"
                            >
                                Plagiarism Check Warning
                            </p>
                            <p
                                class="mt-1 text-xs text-amber-600 dark:text-amber-400"
                            >
                                You have used {{ paper.plagiarism_attempts }} of
                                3 attempts.
                                <template
                                    v-if="(paper.plagiarism_attempts ?? 0) >= 3"
                                    >No more attempts remaining.</template
                                >
                                <template v-else>1 attempt remaining.</template>
                            </p>
                        </div>
                    </div>
                </div>
"""


def load(name: str) -> str:
    return (PARTS / name).read_text()


def main() -> None:
    t = FILE.read_text()
    t = t.replace(
        "import { computed, ref } from 'vue';\nimport ResearchShowInPageNav from '@/components/research/ResearchShowInPageNav.vue';",
        "import { computed, ref } from 'vue';\nimport { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';",
    )
    t = re.sub(
        r"\nconst inPageNavItems = computed\(\(\): \{ id: string; label: string \}\[] => \{.*?\n\};\n",
        "\n",
        t,
        count=1,
        flags=re.DOTALL,
    )
    t = t.replace(
        'id="research-show-overview"\n            class="scroll-mt-20 overflow-hidden',
        'class="overflow-hidden',
    )
    a = t.index("        <ResearchShowInPageNav :items=\"inPageNavItems\" />")
    b = t.index("    <!-- QR Full-Page Modal -->")
    body = f"""        <div class="overflow-hidden rounded-2xl border border-border bg-card">
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
{load("qr.txt")}                    </TabsContent>
                    <TabsContent value="steps" class="p-0">
            <div class="space-y-4">
{load("steps.txt")}            </div>
                    </TabsContent>
                    <TabsContent value="history" class="p-0">
            <div class="space-y-4">
{load("history.txt")}            </div>
                    </TabsContent>
                    <TabsContent value="comments" class="p-0">
            <div class="space-y-4">
{load("comments.txt")}            </div>
                    </TabsContent>
                    <TabsContent value="files" class="p-0">
            <div v-if="paper.files && paper.files.length" class="space-y-4">
{load("files.txt")}            </div>
            <div
                v-else
                class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground"
            >
                No files uploaded yet.
            </div>
                    </TabsContent>
                    <TabsContent value="panels" class="p-0">
            <div v-if="(panelDefenses ?? []).length" class="space-y-4">
{load("panels.txt")}            </div>
            <div
                v-else
                class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground"
            >
                No panel defense records yet.
            </div>
                    </TabsContent>
                    <TabsContent value="paper" class="p-0">
            <div class="space-y-4">
{PLAGIARISM_BLOCK}
{load("paper.txt").replace("                </section>\n", "")}            </div>
                    </TabsContent>
                </div>
            </Tabs>
        </div>
"""
    t = t[:a] + body + t[b:]
    FILE.write_text(t)
    print("Updated", FILE)


if __name__ == "__main__":
    main()
