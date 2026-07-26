# Spec template

Copy this file to `specs/<feature>.md` and fill every section (or mark it `N/A —
<reason>`). HTML comments are guidance — delete them in the real spec.

**Who these specs are for** (the maintainer's standing principle): a QA person or
product owner who wants to **learn** the feature, **review** whether it behaves as
intended, or **add** to it. Concise, but covering all the relevant details for the
area. The spec is the **source of truth**: everything the campaign knows about the
feature — behavior in all three apps, divergences, bugs, open product questions —
lives here, and everything else (tests, bug lists, coverage views) is derived from
it. If a reader needs a developer or an internal report to understand a sentence,
the sentence is wrong.

**One spec covers OJS, OMP and OPS.** The body describes shared behavior; an
UNMARKED claim asserts "probed identical in every app that has the surface" —
absence of a marker is itself a claim, never "not yet checked". Divergences carry
an inline marker linking to the Findings register. A feature an app lacks
entirely: title badge (`{OJS OMP}`) + one absence paragraph up front, written as
an install fact ("OPS does not install X by default"), never an impossibility.
Cross-app vocabulary (press/server for journal, monograph/preprint for
submission…) follows `APP-GLOSSARY.md`; write the OJS term once with the recast
noted in the preamble, not re-badged on every mention.

**Altitude — two principles, no length quota** (maintainer, 2026-07-10). (1)
*Complete*: enough to accurately recreate the feature from the spec alone. (2)
*Compressed*: state each fact once, at its home section, and reference it
elsewhere — never repeat mechanically. Length is whatever those two principles
produce.

**The lint gate (contract, not a script):** a mechanical gate is rebuilt
alongside the FIRST new spec — written against that corpus's actual format,
kept small — and every spec must pass it with ZERO findings before test
authoring (RUNBOOK step 5). It checks: the leak rule (rule 1), the
deviation-vocabulary density ceiling (rule 5), glossary vocabulary and app
badges, Findings-register integrity (markers ↔ entries both ways, badges
present, summary table agrees with entries), and link resolution (every
link/anchor/footnote resolves). The previous 1,500-line implementation was
deleted in the 2026-07-26 reset; rebuild the checks, not the script.

The non-negotiable rules the gate enforces:

1. **Business-language bodies, code in footnotes.** No section BODY may contain a
   class/method name, route, Vue component, DB table/column, constant, or HTTP
   status code. Describe what a user OBSERVES or DOES. Every code symbol is
   PROVENANCE and lives in a `<sup>x</sup>` footnote — and ALL footnote blocks
   live in one `## Footnotes — mechanism & evidence` section at the document
   tail, so the upper spec stays pure product language. Anchor to STABLE SYMBOLS
   (`ClassName::method()`, a constant, a route path), never line numbers.
2. **Concrete role names, never umbrellas.** Use the app's actual role names:
   **Site Administrator, Journal Manager, Section Editor, Assistant, Author,
   Reviewer, Reader** (OMP/OPS analogues per APP-GLOSSARY). Never "editorial
   staff", "editors", "the full manager". If several roles qualify, LIST them;
   if assignment- or scope-based, say so. One canonical name per role per spec.
3. **One home for permissions.** Actors & permissions is the single source of
   who-may. Rules & state describes behavior and state and never restates the
   permission matrix; where behavior depends on a role, name the state and defer
   the who to Actors.
4. **Probe evidence is provenance, exactly like code anchors.** Status codes,
   redirect targets, "live-probed" notes and dates, seeded usernames and
   journals prove a claim; they are not the claim. They live ONLY in footnotes.
   The body states the observable outcome in the user's terms.
   - **Bad**: "`/authorDashboard/submission/{id}` redirects to My Submissions
     … (live-probed 302, both author kinds)"
   - **Good**: "An old bookmarked author-dashboard link lands on My Submissions
     with that submission's tracking view open. <sup>g</sup>"
5. **Neutral deviation phrasing — everywhere, findings and footnotes included.**
   A finding states expected behavior, observed behavior, and impact in the same
   neutral product voice as the rest of the spec. **Write it as what it is: a
   product defect report for the team that maintains this code** — the screen
   and the server disagree about who may do something, here is the difference,
   fix one of them. That framing is not a euphemism for something else; it is
   the actual purpose, and stating it plainly is what makes a finding useful to
   a PO, a QA tester and a developer at once. What it is NOT is a walkthrough:
   no "bypass"/"ungated" framing, no step-by-step reproduction in the spec, no
   accumulated narrative — that detail stays in `.reports/` (read on demand),
   the spec links or summarizes the outcome. (A PO reader needs the outcome;
   and accumulated attack-flavored prose measurably trips model safeguards for
   every downstream agent that reads the spec — 2026-07-22 forensics.
   The lint gate enforces a term-density ceiling.)
   - **Bad**: "⚠ the demotion is UI-only — a hand-crafted save still succeeds
     ungated; the server guard can never match."
   - **Good**: "⚠ the restriction applies on screen only; a change submitted
     another way is applied rather than refused [A3](#a3)."
6. **Shared mechanisms: one owner, real links — never a restatement.** A
   mechanism serving several features is described fully in exactly one spec —
   the one whose subject it is — and every other spec links instead of
   retelling: the owner marks the passage `<a id="stage-access"></a>` (explicit,
   never heading-derived); a referencing spec keeps only what its own reader
   needs, then points `[→ stage access](workflow-stage-navigation.md#stage-access)`.
   The lint gate resolves every link (missing file/anchor/duplicate id =
   finding). Before describing any cross-feature behavior, grep `specs/` for
   its user-facing string: if another spec owns it, link; if this spec is the
   natural owner, take the passage over and leave links behind.
   **Ownership follows the screen, not the trigger** (maintainer, 2026-07-26):
   a status, notice or other display belongs to the feature that owns the
   screen where it renders, even when another feature's actions drive its
   transitions — a status may read from many sources but it is a status OF one
   surface. The triggering feature keeps one side-effect line plus a pointer
   to the owning spec.
7. **Findings enter at the weight their impact earns.** Probe and verification
   reports are raw material, not spec content. The writer (Fable) judges each
   candidate finding: does it belong to THIS feature, is it user-relevant, is
   its severity what the evidence shows — and writes it symptom-first in
   product language, at proportionate length. Trivia, fixture accidents, and
   neighboring features' findings stay in `.reports/` or move to their owning
   spec. Investigation vocabulary ("probe", "verify chunk", "the trial",
   "orchestrator") never appears in a spec; evidence citations live in
   footnotes as opaque pointers.

**Everything clickable.** Body markers link to register entries; register IDs
link back from the summary table; `<sup>` marks link to their footnotes; cross-
spec pointers are real links. Anchors are explicit `<a id="…"></a>` on their own
line. A reference a reader might want to follow IS a link — lint enforces
resolution both ways.

---

```markdown
---
name: <feature-slug>
scope: <one-line: the user job this feature serves>
apps: [ojs, omp, ops]       # apps that have the feature (all three unless absent)
shared: pkp-lib | no        # implemented in lib/pkp or app-only
status: draft | verified    # verified = adversarial pass findings resolved
atlas-claims: [<atom IDs this spec owns>]
---

# <Feature name> {OJS OMP OPS}

<!-- Title badge only when an app LACKS the feature; omit when all three have it.
     If badged, follow the Purpose section with a one-paragraph absence note. -->

## How to read this file

<!-- ~15 lines max. Define ONLY what this spec actually uses: the unmarked-claim
     rule (one sentence), the marker forms with one example each, the badge
     values, and the everything-is-clickable note. No format history, no policy,
     no campaign vocabulary. If a construct needs more than a line here, the
     construct is too complicated — simplify the construct, not the legend. -->

## Purpose

<!-- One paragraph. Whose job, what job, why it exists. A new PM should get it. -->

## Actors & permissions

<!-- One ROW PER CAPABILITY (View, Create, Edit, Remove…), so a reader compares a
     capability across roles in one row. Two columns: Action | Who may — and when.
     Cells are PLAIN PRODUCT LANGUAGE, one bullet per role-group/condition
     (`<br>• `), each bullet: actor(s) then condition. Lead with a short
     paragraph defining recurring terms (assigned, participant…) and site-wide
     baselines, so cells stay terse. FRONTEND-FIRST: what the role sees and can
     do in the UI; verify backend agreement, and where they diverge the UI
     reality is the headline with a ⚠ marker into the register. An ability with
     no UI control is never a plain capability — ⚠ "no UI control" + register.
     ONE sentence per fact: keep each cell's rule here, and the register entry
     carries only the finding — not a retelling of the cell. Anchors hang off
     `<sup>a</sup>` markers; the blocks live in the Footnotes tail. -->

| Action | Who may — and when |
|--------|--------------------|
| **<Action>** | • <actor(s)> — <condition><br>• <actor(s)> — <condition; ⚠ [A1](#a1) for oddities> <sup>a</sup> |

## Fields & validation

<!-- Fields by on-screen LABEL, not internal attribute names. Validation in plain
     terms. Drop purely server-set fields. Three columns; anchors as footnotes. -->

| Field (UI label) | Required? | Rules |
|------------------|-----------|-------|

## Rules & state

<!-- The heart: state machine, invariants, computed behavior, ordering rules.
     Numbered rules; if a rule is really two rules, split it (10a/10b) so other
     sections can cite the half they mean. ONE sentence, one rule: prefer three
     short sentences over one 90-word chain — front-load the condition, then the
     consequence. Name states and fields as the UI shows them. Enumerations of
     3+ parallel items get bullets or a compact table, never a prose run.
     ⚠-mark as-built oddities with a register link — symptom stated ONCE here
     (its home), bare marker anywhere else it surfaces. -->

## Side effects

<!-- Emails (mailable, recipients, opt-outs), notifications (type, where
     surfaced), log entries, jobs, cross-entity mutations. One bullet per
     effect — don't stack five findings in one bullet. -->

## Settings that modify behavior

<!-- Site/context settings, config vars, plugin toggles that change the rules
     above — and HOW. -->

## Cross-feature interactions

<!-- Other specs this one touches; who owns each shared rule (rule 6 links). -->

## Canonical scenarios

<!-- Named narrative journeys a QA person can act out on any install — these are
     the units tests map onto (each app's suite implements them per the RUNBOOK
     multi-app rules). ORDER: first the scenarios COMMON to every app that has
     the feature, then the app-specific ones (title the block or badge the
     scenario). A common scenario is written once, app-neutral, and each app
     implements it in its own context — roles, data and vocabulary per
     APP-GLOSSARY — so keep any app-varying step behind an inline marker
     rather than baking one app's nouns into the flow.
     Name actors BY ROLE, never seeded accounts.
     WRITE EACH AS A MANUAL TEST SCRIPT: what the tester does and what appears
     on screen, quoting real UI labels; no builder's-seat verbs (pins, fires,
     wires), no nouns invisible on screen. Scenarios must stand alone — a
     reader executes them without a trip back into Rules. Mark per-app
     divergence inline ([OMP2](#omp2)); a scenario an app cannot run gets its
     analogue or absence noted at the end of the scenario. Seeding recipes and
     usernames live in the scenario's footnote.
     Acceptance test: a QA person who has NEVER opened the screen can execute
     the scenario and judge pass/fail. -->

1. **<Scenario name>** — <actor(s)>: <flow in 2–4 sentences, including the
   observable outcome>. <sup>s1</sup>

## Findings register

<!-- THE single home for everything as-built that deviates, diverges, or needs a
     product ruling — there is no separate Known-deviations or Open-questions
     section, and no external bug ledger. Structure:

     Preamble (3–5 lines): "Verdicts are the author's judgment (claude, <date>),
     unreviewed unless an entry notes otherwise; the team settles them on spec
     review." + the sort rule + "each entry opens with the user-observable
     symptom; mechanism and evidence live in the entry's footnote."

     Summary table — the triage view, sorted 🐞 → ❓ → ✅, mirroring the entries
     (entries are the source):

     | ID | Finding (one line, symptom) | Bug? | Impact | Review |

     Badges: 🐞 defect (author's call) · ❓ needs a product ruling · ✅ intended
     divergence. Impact: one plain value (user-visible / invisible / latent /
     minor). Review: — until someone reviews; then their name+date.

     Entries under `### All apps` / `### OMP` / `### OPS`. IDs are LOCAL and
     DENSE — A1, A2… / OMP1… / OPS1… — no gaps, no foreign keys. Anchor each:
     `<a id="a1"></a>`. Body markers: `⚠ [A1](#a1)` when the entry is 🐞/❓
     (⚠ means "as-built deviation here", any scope); plain `[OMP2](#omp2)` for
     ✅ intended divergences.

     Entry anatomy (5–8 lines):
     **A1 — <short title>** · 🐞 · user-visible.
     <Symptom, 1–3 sentences: present tense, user as subject, expected vs
     observed. State it at the weight it earns (rule 7).>
     <For ❓ only> Question: <the one sentence the team answers>. Lean: <the
     author's lean and why, one sentence>.
     Since: <date (age)> · Basis: probe | commit | judgment. <sup>f-a1</sup>
     <Reviewed: name, date — confirmed | overturned (was 🐞). Only when it
     happens; never pre-printed.>

     `Since:` only when dated (omit the line otherwise). One rationale sentence
     for a 🐞-vs-✅ call is welcome ("worked for OPS's whole life; broke in the
     2025 stage removal — regression, not choice"); the commit archaeology goes
     in the footnote. A finding another feature owns: one line + link to that
     spec, full entry there. -->

---

<a id="footnotes"></a>
## Footnotes — mechanism & evidence

<!-- ALL `<sup>` blocks from every section above, in section order, each anchored
     (`<a id="fn-a1"></a>`) so marks link down. Code symbols, probe dates,
     seeded accounts, commit archaeology — the developer's layer. A reader can
     ignore this section entirely and lose no behavior. -->

## Reference — entry points & surfaces

<!-- Where the feature is reached: UI paths, API endpoints, CLI, email links.
     One row per entry point with its atlas atom ID. -->

| Entry | Path | Atom |
|-------|------|------|

## Reference — code anchors

<!-- The load-bearing files (handler/controller/manager/schema). Not exhaustive. -->
```
