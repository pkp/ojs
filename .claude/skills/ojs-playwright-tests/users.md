# Users & Roles Reference

> **Live again** (rebuilt 2026-07-27) — the harness files named below exist and
> match this file; corrections from the rebuild are folded in (auth probe,
> maxlength handling, self-healing install).

Everything auth-related for writing OJS Playwright tests. If you need to decide which user to log in as, or you need to know the password, start here.

## Role constants (PHP side)

Defined in `lib/pkp/classes/security/Role.php:24-31`. These are the integer IDs the backend uses. They map to the string role keys in the test user data (next section).

| Constant | ID | String key | Description |
|---|---|---|---|
| `ROLE_ID_SITE_ADMIN` | 1 | `siteAdmin` (in `users[].roles` only) | Site-wide administrator. Outside any journal — the group has a **null context id**. |
| `ROLE_ID_MANAGER` | 16 | `manager` | Journal manager — journal settings, users, plugins |
| `ROLE_ID_SUB_EDITOR` | 17 | `editor`, `sectionEditor` | Editor / section editor — both map to sub-editor |
| `ROLE_ID_ASSISTANT` | 4097 | `copyeditor`, `layoutEditor`, `proofreader`, `funding`, `editorialBoardMember` | Assistants. On OJS/OMP, `funding` (Funding coordinator) is the one default assistant group with review-stage access (stages 1,3). **OPS ships neither `funding` nor the production assistants** — its only assistant-slot group is `editorialBoardMember` (Editorial Board Member), which is what `assistant.rita` is enrolled in there (verified 2026-07-28) |
| `ROLE_ID_REVIEWER` | 4096 | `reviewer` | Peer reviewer |
| `ROLE_ID_AUTHOR` | 65536 | `author` (also implicit on submit) | Author — anyone can become one by submitting |
| `ROLE_ID_READER` | 1048576 | `reader` | Reader — any registered user |
| `ROLE_ID_SUBSCRIPTION_MANAGER` | 2097152 | — (OJS-only) | Manages subscriptions. **Not seeded in baseline users.** OJS-only role. |

**Note on string keys:** `sectionEditor` corresponds to `ROLE_ID_SUB_EDITOR`. **CAUTION (verified wave 11):** on default scratch-journal user groups, the scenario role string `editor` resolves to the "Journal editor" group, which is **`ROLE_ID_MANAGER`** per `registry/userGroups.xml:18` — NOT sub-editor. A `users: [{roles: ['editor']}]` throwaway therefore passes manager-level gates (canPublish, settings access). Use `sectionEditor` when you need a non-manager editorial role.

### Scenario role keys, per app

Every scenario key that names a role — `users[].roles`, `invitations[].roles` — is
resolved by `PKPTestApiController::resolveUserGroup()` against the group's stored
`nameLocaleKey`, so the vocabulary is exactly the set of default groups the app
ships. **There is no `reviewer` key anywhere: it is `externalReviewer`** (OMP also
has `internalReviewer`). A key the app does not ship throws a 400 that lists the
whole set, which is also the cheapest way to re-check this list (verified
2026-07-28):

| App | Keys |
|---|---|
| OJS | `manager`, `editor`, `productionEditor`, `sectionEditor`, `guestEditor`, `copyeditor`, `designer`, `funding`, `indexer`, `layoutEditor`, `marketing`, `proofreader`, `author`, `translator`, `externalReviewer`, `reader`, `subscriptionManager`, `editorialBoardMember` |
| OMP | `manager`, `editor`, `productionEditor`, `sectionEditor`, `copyeditor`, `designer`, `funding`, `indexer`, `layoutEditor`, `marketing`, `proofreader`, `author`, `volumeEditor`, `chapterAuthor`, `translator`, `internalReviewer`, `externalReviewer`, `reader`, `editorialBoardMember` |
| OPS | `manager`, `sectionEditor`, `author`, `reader`, `editorialBoardMember` — **no `funding`**, no reviewer keys, no production assistants |

The label a screen shows is the app's own: `sectionEditor` renders as "Section
editor" on OJS, "Series editor" on OMP and "Moderator" on OPS.

#### `siteAdmin` — the one key that is not in that table

`users[].roles: ['siteAdmin']` seeds a throwaway **site administrator** (U53,
2026-07-29; all three apps, both `bootstrap` and `scenarios/context`):

```js
users: [
    {username: 'u53top.admin.ojs', roles: ['siteAdmin']},            // administrator only
    {username: 'u53top.both.ojs',  roles: ['siteAdmin', 'manager']}, // and a context role
]
```

Everything else about a user spec is unchanged — password is still the username
doubled, the response still echoes `{username: id}`, a failed build still rolls
the account back. It is absent from the per-app table above (and from the 400's
"available roles" list) because it does not live in a context: the site
administrator group is installed once for the whole site with a null context id
and no `nameLocaleKey`, so `resolveRoleGroup()` finds it by role id instead of
by the context-scoped name-key lookup. Enrolment is the app's own
`Repo::userGroup()->assignUserToGroup()`, the same call the installer makes.

Two things to remember:

- **`invitations[].roles` rejects it on purpose.** No screen invites anyone to
  the site administrator role, so the invitation seeder does not either — you
  get the usual "No default user group for role 'siteAdmin'" 400 there.
- **This is the ONLY way to get a second administrator.** No screen grants the
  role: the Users & Roles user form intersects the posted group ids with
  `UserGroup::withContextIds([$contextId])` before saving, and the site admin
  group is in no context. The installer's `admin` is therefore the only other
  one, and the suite keeps it **enabled and unmerged** — every test about
  administrator behaviour (self-disable, merge, admin-acting-on-admin) seeds its
  own throwaway.

## Seeded test users

Rebuild home: `lib/pkp/playwright/data/users.js`. All 18 users are seeded into the `publicknowledge` journal (admin is a site-level user, created by the installer; others are created by `bootstrap.setup.js` via `/api/v1/_test/bootstrap`).

The roster is **role-keyed** (maintainer decision 2026-07-10): usernames take the `role.firstname` form, display names read "Firstname Role" (so UI screenshots say the role), emails match usernames, and there is one account per permission archetype. All first names are unique across the roster.

When a test needs a user with a given role, use the first one listed for that role (the `users` helper map at the bottom of `users.js` does exactly this).

| Username | Role | Use this when you need... |
|---|---|---|
| `admin` | site admin | Admin console, multi-journal operations, plugin management |
| `manager.maya` | manager | Journal settings, managing users |
| `editor.diana` | editor | A senior editor of `publicknowledge` (also a sectionEditor in both sections) |
| `sectioneditor.ana` | sectionEditor | Section editor for **Articles** (`ART`). Default pick when you need "a section editor". |
| `sectioneditor.ravi` | sectionEditor | Section editor for **Reviews** (`REV`) |
| `sectioneditor.omar` | sectionEditor | Another Articles section editor. The designated account for recommend-only assignments (the recommendOnly flag itself is per-assignment). |
| `reviewer.julia` | reviewer | Default reviewer. First in the list — use this when you just need "a reviewer". |
| `reviewer.paul` | reviewer | A second reviewer (e.g. to model multiple reviews on one submission) |
| `reviewer.amara` | reviewer | A third reviewer |
| `reviewer.adam` | reviewer | A fourth reviewer |
| `copyeditor.carla` | copyeditor | Copyediting actions |
| `copyeditor.sam` | copyeditor | A second copyeditor |
| `layouteditor.leo` | layoutEditor | Layout / galley production |
| `proofreader.pia` | proofreader | Proofreading actions |
| `author.alex` | author | A non-privileged author. Use when a spec needs to exercise an author-only permission gate. |
| `author.bea` | author | A second author — co-author and foreign-submission cases (e.g. one author must not see another's submission) |
| `assistant.rita` | funding (assistant) on OJS/OMP · editorialBoardMember on OPS | An assistant **with review-stage access** — enrolled in the Funding coordinator group (stages 1,3), the one default assistant group that reaches external review. **OPS has no `funding` group**; there `assistant.rita` is an Editorial Board Member (assistant slot, no stage access) |
| `reader.rosa` | reader | A registered user with no roles beyond reader — reader-facing gates, "logged in but no editorial access" checks |

**Why `author.alex` matters.** Every other seeded publicknowledge user with workflow access has a manager/editor role that short-circuits `Repo::submission()->canEditPublication` (NOT_CHANGE_METADATA_EDIT_PERMISSION_ROLES). `author.alex` (and `author.bea`) are author-only, so author-side permission tests are meaningful. Password derives normally to `author.alexauthor.alex`.

**No pre-seeded subscriber.** The seed data does not include a subscription-manager user (OJS-only role); `reader.rosa` covers the plain-reader case.

## Password rule

Defined in `getPassword()` at `lib/pkp/playwright/data/users.js`:

```js
getPassword(username) {
    return username === 'admin' ? 'admin' : username + username;
}
```

- `admin` → `admin`
- everyone else → **username repeated twice** (e.g. `editor.diana` → `editor.dianaeditor.diana`, `reviewer.julia` → `reviewer.juliareviewer.julia`)

No seeded user is flagged `mustChangePassword` — every account logs straight in to the dashboard.

**The maxlength trap**: the login form's password input carries `maxlength="32"` while the three `sectioneditor.*` passwords run 34–36 chars. `LoginPage.fillPassword()` lifts the attribute before filling, so specs never see it; the underlying UI limitation is a product finding for the Login & sessions spec, not something a test should assert around.

## Login flow internals

### `ensureAuthStateFor(browser, username, {baseURL})`

Defined at `lib/pkp/playwright/support/auth.js`.

Flow:
1. Check for `<appRoot>/playwright/.auth/<username>.json`. If present, **probe** it: replay cookies into a throwaway APIRequestContext, GET the profile URL **following redirects**, and judge by where the request ENDS: `ok() && !url.includes('/login')` → live, return the cached path; otherwise fall through to a fresh login. (The pre-reset design — status-200 with redirects disabled — always fails on this app: even a signed-in profile request redirects twice, first to the locale-prefixed form, then into the single context, so it silently re-logged everyone in on every run.)
2. Open a fresh browser context, drive `LoginPage` to submit `username` + `getPassword(username)`, wait for redirect away from `/login` (15 s timeout, `waitUntil:'commit'` so it fires on URL change rather than waiting for the full dashboard fan-out), snapshot `context.storageState()` to the JSON file, close the context, return the path.
3. Under parallel workers, two workers may race on the missing/stale file. Both perform a successful login (OJS allows concurrent sessions per user), last write wins, tests keep working.

**Why the probe.** Tests that drive the impersonation flow (`signInAsUser`/`signOutAsUser`) call `PKPSessionGuard::signInAs/signOutAs`, both of which migrate the session ID and destroy the previous one. After such a test, cookies persisted in `<username>.json` point at a session row that no longer exists, and the next loader lands on `/login`. The probe catches that without special-casing the impersonation specs.

Login URL: `/index.php/index/en/login` (see `LoginPage.js:24`). Form selectors are stable IDs:
- `input#username`
- `input#password`
- `form#login button` (submit)

### How specs consume it

Two paths, depending on the shape of the test:

**Single-actor (default):**
```js
test.use({user: 'editor.diana'});
test('...', async ({page}) => {
    // page is already logged in as editor.diana via storageState
});
```
The `storageState` fixture at `lib/pkp/playwright/support/base-test.js` reads the `user` option, calls `ensureAuthStateFor`, and loads the cached file before the page is created.

**Multi-actor:**
```js
test('...', async ({page, asUser}) => {
    const reviewerCtx = await asUser('reviewer.julia');
    const reviewerPage = await reviewerCtx.newPage();
});
```
See `asUser` in `lib/pkp/playwright/support/base-test.js`.

### Bootstrap prerequisite

Auth only works after the setup project has run (`bootstrap.setup.js`). The setup runs before every test project; it probes `GET /api/v1/_test/bootstrap?context=publicknowledge` and no-ops warm (<1 s), or cold it:
1. Installs the test schema (`tools/installTest.php` — self-healing from an empty or partially installed DB; refuses a populated one, which is what `--recreate-db` is for)
2. Seeds the `publicknowledge` journal (from `playwright/fixtures/bootstrap.js`)
3. Seeds all 17 non-admin users (admin is created by the installer), including the section-editor→section assignments via `users[].sections`

If `.auth/` is stale (deleted DB, seed-data change), `ensureAuthStateFor` re-creates files on demand. Use `npm run test:e2e:reset` to force a full cold bootstrap.

## The `publicknowledge` journal context

Every test user (except admin) is enrolled in this journal. Seed data at `playwright/fixtures/bootstrap.js:33-108`.

- **Path:** `publicknowledge`
- **URL base:** `/index.php/publicknowledge/`
- **Primary locale:** `en` (supported: `en`, `fr_CA`)
- **Acronym:** `JPK`

### Sections (workflow-relevant)

| Abbrev | Title | Section editors | Notes |
|---|---|---|---|
| `ART` | Articles | editor.diana, sectioneditor.ana, sectioneditor.omar | Word count limit 500 |
| `REV` | Reviews | editor.diana, sectioneditor.ravi | Abstracts not required; identifyType "Review Article" |

### Categories

Two top-level: `applied-science` (with nested `comp-sci/computer-vision` and `eng`) and `social-sciences` (with `sociology` and `anthropology`).

### Issues

- Volume 1, Number 2, 2014 — **published**
- Volume 2, Number 1, 2015 — unpublished (upcoming)

When a test needs to publish to an issue, use the unpublished one unless you specifically want to edit a back issue.
