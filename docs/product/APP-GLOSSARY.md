# App glossary — OJS · OMP · OPS term map and capability names

**Contract.** This file is the single home for multi-app vocabulary. Specs are
written in OJS terms, always; reading a spec for OMP or OPS, substitute via the
tables below. No spec repeats these tables or inlines a translation ("journal
(press in OMP)"). The lint's forbidden-term checks are built from the OMP/OPS
columns of this file — nowhere else. On-screen names win: every cell is the name
the app's UI shows, and a probe that contradicts a cell fixes the cell. One
definition per term.

**Absence is not a synonym.** A "—" cell means the concept does not exist in
that app. Any rule or scenario built on a "—" term is implicitly absent there
even without a badge (the lint still expects the badge — the dash is the
reader's safety net, the badge is the contract). Where an app has a
*counterpart feature* instead (issues → catalog), the dash says so, but the
counterpart is a different feature with its own spec, never a substitution.

Terms not in this file mean the same thing in all three apps.

## 1. Vocabulary map

### Context and content nouns

| OJS term (as written in specs) | OMP | OPS |
|---|---|---|
| journal | press | preprint server |
| article / submission | monograph (work types on screen: Monograph, Edited Volume) | preprint |
| section | series (optional; categories carry more weight) | section (unchanged; seed section "Preprints") |
| issue | — no issues. Counterpart feature: catalog (New Releases / Featured) | — no issues; continuous posting |
| issue assignment | — counterpart feature: catalog entry | — |
| galley | publication format (+ ONIX metadata) | galley (unchanged) |
| Archive (back issues) | Catalog | Preprints archive listing |
| Hosted Journals (site admin) | Hosted Presses | Hosted Servers |

### Roles (default user-group names)

| OJS | OMP | OPS |
|---|---|---|
| Journal Manager | Press Manager | Preprint Server Manager |
| Editor | Press Editor | — no editor group |
| Section Editor (sub-editor slot) | Series Editor | Moderator |
| Reviewer | Reviewer (split: Internal Reviewer / External Reviewer) | — no reviewer group |
| Copyeditor | Copyeditor | — not seeded; no copyediting stage |
| Layout Editor | Layout Editor (+ Designer) | — |
| Translator (journal user group) | Volume Editor, Chapter Author, Translator (chapter roles — OMP's Translator is a chapter role, distinct from OJS's group of the same name) | — |

*Note*: report A pairs OPS "Moderator" with the Editor slot, report B (checkout-
verified) with the Section-editor slot; B's mapping is recorded here — confirmed
in live OPS Moderator sessions, 2026-07-26.

### Workflow stages and decisions

| OJS | OMP | OPS |
|---|---|---|
| stages: Submission → Review → Copyediting → Production | Submission → **Internal Review** → External Review → Copyediting → Production | **Production only** (single stage) |
| Review (the stage) | External Review (Internal Review is a distinct, OMP-unique stage) | — |
| Copyediting | Copyediting | — |
| Send to Review (decision) | Send to External Review (plus Send to Internal Review, Skip Internal, Accept from Internal, internal recommend-only variants) | — decisions are Decline / Revert Decline only |
| Publish | Publish to Catalog (approved proof) | **Post the preprint** |
| Schedule for publication (issue) | — catalog entry instead | — posts continuously; moderation-before-posting is a context setting |

### Payments and access

| OJS | OMP | OPS |
|---|---|---|
| subscriptions | — | — |
| payments (APC / subscription) | direct sales of publication formats (different feature, shared paymethod plugins only) | — no payments code at all |

### Seed-data names (test fixtures)

| OJS | OMP | OPS |
|---|---|---|
| context "Journal of Public Knowledge" (path `publicknowledge`) | "Public Knowledge Press" (same path) | "Public Knowledge Preprint Server" (same path) |
| default genre "Article Text" | "Book Manuscript" | "Preprint Text" |
| seeded sections ART / REV | series (seeded separately); no default section | one section "Preprints" |

## 2. Capability names (`app.context.js` — canonical, verbatim)

Tests gate on these flags, never on app names (`if (!ctx.hasReviewStage)`, never
`if (app === 'ops')`). The names below are the canonical spelling; `app.context.js`
in each app repo (G4) and shared specs must use them verbatim.

| Capability | Gates | OJS | OMP | OPS |
|---|---|:-:|:-:|:-:|
| `hasReviewStage` | any review stage exists: the whole review cluster (send-to-review, reviewers, rounds, forms, anonymity, recommend-only) | ✓ | ✓ | ✗ |
| `hasInternalReview` | the OMP-unique Internal Review stage: internal decision roster, internal/external `reviewRounds` seeding, internal-stage companions | ✗ | ✓ | ✗ |
| `hasCopyediting` | the Copyediting stage and its participants/files | ✓ | ✓ | ✗ |
| `hasProduction` | the Production stage (all apps; kept for completeness) | ✓ | ✓ | ✓ |
| `hasIssues` | issues, issue assignment, back-issue archive/TOC | ✓ | ✗ | ✗ |
| `hasGalleys` | galley representation model (OMP uses publication formats instead) | ✓ | ✗ | ✓ |
| `hasSubscriptions` | subscriptions management and subscription access | ✓ | ✗ | ✗ |
| `hasSections` | sections as the content grouping (OMP uses optional series) | ✓ | ✗ | ✓ |
| `hasReviewerRoles` | reviewer user groups exist / can be seeded | ✓ | ✓ | ✗ |

## 3. Spec-badge ↔ capability translation

Spec badges are reader-facing and name apps; tests are capability-gated and
never name apps. The translation is mechanical via this table — a test author
maps a badge or variation note to a flag here, in one place.

| Spec marking (typical) | Test gate |
|---|---|
| `{OJS OMP}` on a review rule / "OPS: hidden (no review stage)" | `test.skip(!ctx.hasReviewStage, …)` |
| OMP variation "applies to Internal Review too" / internal-stage scenario | OMP companion gated on `ctx.hasInternalReview` |
| `{OJS OMP}` on a copyediting item | `test.skip(!ctx.hasCopyediting, …)` |
| `{OJS}` on an issue rule / "no issues" | `test.skip(!ctx.hasIssues, …)` |
| `{OJS OPS}` on a galley item (OMP: publication formats) | `test.skip(!ctx.hasGalleys, …)` |
| `{OJS}` on a subscriptions item | `test.skip(!ctx.hasSubscriptions, …)` |
| `{OJS OPS}` on a section-grouping item | `test.skip(!ctx.hasSections, …)` |
| reviewer-persona steps ("as reviewer.julia…") | `test.skip(!ctx.hasReviewerRoles, …)` |

Vocabulary deltas (§1) never gate anything: labels and payload nouns come from
`appContext.vocab` / `appContext.seed`, so a shared test runs unchanged wherever
the capability holds.
