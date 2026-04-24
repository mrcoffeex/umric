#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "resources/js/pages/student/Research/Show.vue"
PARTS = Path("/tmp/umric-tabs")
OUT = SRC.with_suffix(SRC.suffix + ".tmp")

BEFORE = (
    "        <ResearchShowInPageNav :items=\"inPageNavItems\" />\n\n"
    "        <!-- QR Code Panel -->\n"
    "        <section\n"
    "            id=\"research-show-qr\"\n"
    "            class=\"scroll-mt-24 overflow-hidden rounded-2xl border border-border bg-card\"\n"
    "        >\n"
    "            <div class=\"flex items-start gap-3 p-4\">\n"
)
AFTER_LINE = "    <!-- QR Full-Page Modal -->"  # first line to keep from this point

# assembled tab area (between hero close and this marker — user must not include modal)
# We replace from BEFORE through line before AFTER_LINE (exclusive of modal)


def read_until(lines: list[str], start: int, end: int) -> str:
    return "".join(lines[start : end + 1]) if end >= start else ""


def main() -> None:
    lines = SRC.read_text().splitlines(keepends=True)
    t = SRC.read_text()
    i0 = t.index(BEFORE)
    i1 = t.index(f"\n{AFTER_LINE}")
    pre = t[:i0]
    post = t[i1 + 1 :]  # keep newline + modal
    tab_block = f"""        <div class="overflow-hidden rounded-2xl border border-border bg-card">
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
            {(PARTS / "qr.txt").read_text()}</TabsContent>
                    <TabsContent value="steps" class="p-0">
            <div class="space-y-4">{(PARTS / "steps.txt").read_text()}</div>
                    </TabsContent>
                    <TabsContent value="history" class="p-0">
            <div class="space-y-4">{(PARTS / "history.txt").read_text()}</div>
                    </TabsContent>
                    <TabsContent value="comments" class="p-0">
            <div class="space-y-4">{(PARTS / "comments.txt").read_text()}</div>
                    </TabsContent>
                    <TabsContent value="files" class="p-0">
            <div v-if="paper.files && paper.files.length > 0" class="space-y-4">{(PARTS / "files.txt").read_text()}</div>
            <div
                v-else
                class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground"
            >
                No files uploaded yet.
            </div>
                    </TabsContent>
                    <TabsContent value="panels" class="p-0">
            <div v-if="(panelDefenses ?? []).length" class="space-y-4">{(PARTS / "panels.txt").read_text()}</div>
            <div
                v-else
                class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground"
            >
                No panel defense records yet.
            </div>
                    </TabsContent>
                    <TabsContent value="paper" class="p-0">
            <div class="space-y-4">{(PARTS / "alert.txt").read_text() if (Path("/tmp/umric-tabs/alert.txt").read_text()[:1]) else ""} {(PARTS / "paper.txt").read_text()}</div>
                    </TabsContent>
                </div>
            </Tabs>
        </div>
"""
    # Fix paper tab: put alert (plagiarism) before paper content - do properly
    alert = (PARTS / "alert.txt").read_text()
    paper = (PARTS / "paper.txt").read_text()
    # alert file includes section with v-if - use inner only
    alert_inner = _strip_section(alert) if "section" in alert else alert
    # paper section opening has h3 Paper Info
    paper_inner = _strip_paper_opening(paper)
    tab_block = tab_block.split("<TabsContent value=\"paper\">")[0]  # oops, rebuild

    # rebuild paper content block manually
    mid = f"""        <div class="overflow-hidden rounded-2xl border border-border bg-card">
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
            {(PARTS / "qr.txt").read_text()}</TabsContent>
                    <TabsContent value="steps" class="p-0">
            <div class="space-y-4">{(PARTS / "steps.txt").read_text()}</div>
                    </TabsContent>
                    <TabsContent value="history" class="p-0">
            <div class="space-y-4">{(PARTS / "history.txt").read_text()}</div>
                    </TabsContent>
                    <TabsContent value="comments" class="p-0">
            <div class="space-y-4">{(PARTS / "comments.txt").read_text()}</div>
                    </TabsContent>
                    <TabsContent value="files" class="p-0">
            <div v-if="paper.files && paper.files.length" class="space-y-4">{(PARTS / "files.txt").read_text()}</div>
            <div
                v-else
                class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground"
            >No files uploaded yet.</div>
                    </TabsContent>
                    <TabsContent value="panels" class="p-0">
            <div v-if="(panelDefenses ?? []).length" class="space-y-4">{(PARTS / "panels.txt").read_text()}</div>
            <div
                v-else
                class="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground"
            >No panel defense records yet.</div>
                    </TabsContent>
                    <TabsContent value="paper" class="p-0">
            <div class="space-y-4">
                <div
                    v-if="paper.current_step === 'plagiarism_check' && (paper.plagiarism_attempts ?? 0) >= 2"
                    class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/20"
                >
                    <div class="flex items-start gap-3">
                        <AlertTriangle class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />
                        <div>
                            <p class="text-sm font-bold text-amber-700 dark:text-amber-300">Plagiarism Check Warning</p>
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                You have used {{ paper.plagiarism_attempts }} of 3 attempts.
                                <template v-if="(paper.plagiarism_attempts ?? 0) >= 3">No more attempts remaining.</template>
                                <template v-else>1 attempt remaining.</template>
                            </p>
                        </div>
                    </div>
                </div>
{paper}
            </div>
                    </TabsContent>
                </div>
            </Tabs>
        </div>
"""
    p.write_text(pre + mid + post)  # noqa
    # Fix variable name for Path - use write to OUT
    OUT.write_text(pre + mid + post)
    print("Wrote", OUT)


def _strip_section(s: str) -> str:
    return s  # not used
def _strip_paper_opening(s: str) -> str:
    return s


if __name__ == "__main__":
    main()
