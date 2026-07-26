# Users & Roles Reference

> **Design record** — the harness files named below (`users.js`, `auth.js`,
> `base-test.js`, bootstrap) were deleted in the 2026-07-26 reset; this file
> preserves the roster + auth design the rebuild recreates. The role constants
> and journal facts are live app truth.

Everything auth-related for writing OJS Playwright tests. If you need to decide which user to log in as, or you need to know the password, start here.

## Role constants (PHP side)

Defined in `lib/pkp/classes/security/Role.php:24-31`. These are the integer IDs the backend uses. They map to the string role keys in the test user data (next section).

| Constant | ID | String key | Description |
|---|---|---|---|
| `ROLE_ID_SITE_ADMIN` | 1 | — (siteAdmin flag) | Site-wide administrator. Outside any journal. |
| `ROLE_ID_MANAGER` | 16 | `manager` | Journal manager — journal settings, users, plugins |
| `ROLE_ID_SUB_EDITOR` | 17 | `editor`, `sectionEditor` | Editor / section editor — both map to sub-editor |
| `ROLE_ID_ASSISTANT` | 4097 | `copyeditor`, `layoutEditor`, `proofreader`, `funding` | Assistants — `funding` (Funding coordinator) is the one default assistant group with review-stage access (stages 1,3) |
| `ROLE_ID_REVIEWER` | 4096 | `reviewer` | Peer reviewer |
| `ROLE_ID_AUTHOR` | 65536 | `author` (also implicit on submit) | Author — anyone can become one by submitting |
| `ROLE_ID_READER` | 1048576 | `reader` | Reader — any registered user |
| `ROLE_ID_SUBSCRIPTION_MANAGER` | 2097152 | — (OJS-only) | Manages subscriptions. **Not seeded in baseline users.** OJS-only role. |

**Note on string keys:** `sectionEditor` corresponds to `ROLE_ID_SUB_EDITOR`. **CAUTION (verified wave 11):** on default scratch-journal user groups, the scenario role string `editor` resolves to the "Journal editor" group, which is **`ROLE_ID_MANAGER`** per `registry/userGroups.xml:18` — NOT sub-editor. A `users: [{roles: ['editor']}]` throwaway therefore passes manager-level gates (canPublish, settings access). Use `sectionEditor` when you need a non-manager editorial role.

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
| `assistant.rita` | funding (assistant) | An assistant **with review-stage access** — enrolled in the Funding coordinator group (stages 1,3), the one default assistant group that reaches external review |
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

## Login flow internals

### `ensureAuthStateFor(browser, username, {baseURL})`

Defined at `lib/pkp/playwright/support/auth.js`.

Flow:
1. Check for `<appRoot>/playwright/.auth/<username>.json`. If present, **probe** it: replay cookies into a throwaway APIRequestContext and GET `/index/user/profile` with redirects disabled. 200 → return the cached path. Anything else → fall through to a fresh login.
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

Auth only works after the setup project has run (`bootstrap.setup.js`, governed by `config-factory.js:66-71`). The setup runs serially before every test project and seeds:
1. The test database (schema via `tools/installTest.php`)
2. The `publicknowledge` journal (from `playwright/fixtures/bootstrap.js`)
3. All 17 non-admin users (admin is created by the installer)

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
