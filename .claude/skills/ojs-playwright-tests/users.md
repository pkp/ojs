# Users & Roles Reference

Everything auth-related for writing OJS Playwright tests. If you need to decide which user to log in as, or you need to know the password, start here.

## Role constants (PHP side)

Defined in `lib/pkp/classes/security/Role.php:24-31`. These are the integer IDs the backend uses. They map to the string role keys in the test user data (next section).

| Constant | ID | String key | Description |
|---|---|---|---|
| `ROLE_ID_SITE_ADMIN` | 1 | — (siteAdmin flag) | Site-wide administrator. Outside any journal. |
| `ROLE_ID_MANAGER` | 16 | `manager` | Journal manager — journal settings, users, plugins |
| `ROLE_ID_SUB_EDITOR` | 17 | `editor`, `sectionEditor` | Editor / section editor — both map to sub-editor |
| `ROLE_ID_ASSISTANT` | 4097 | `copyeditor`, `layoutEditor`, `proofreader` | Production assistants |
| `ROLE_ID_REVIEWER` | 4096 | `reviewer` | Peer reviewer |
| `ROLE_ID_AUTHOR` | 65536 | — (implicit on submit) | Author — anyone can become one by submitting |
| `ROLE_ID_READER` | 1048576 | — (default) | Reader — any registered user |
| `ROLE_ID_SUBSCRIPTION_MANAGER` | 2097152 | — (OJS-only) | Manages subscriptions. **Not seeded in baseline users.** OJS-only role. |

**Note on string keys:** `editor` and `sectionEditor` both correspond to `ROLE_ID_SUB_EDITOR` at the DB level. The UserProcessor on the backend maps user-group names to role IDs; the test-user data treats them as distinct groups for role assignment clarity.

## Seeded test users

Source of truth: `lib/pkp/playwright/data/users.js:53-214`. All 16 users are seeded into the `publicknowledge` journal (admin is a site-level user, created by the installer; others are created by `bootstrap.setup.js` via `/api/v1/_test/bootstrap`).

When a test needs a user with a given role, use the first one listed for that role (the `users` helper map at `users.js:220-230` does exactly this).

| Username | Role | Use this when you need... |
|---|---|---|
| `admin` | site admin | Admin console, multi-journal operations, plugin management |
| `rvaca` | manager | Journal settings, managing users. **Flagged `mustChangePassword` — first login forces a reset. For plain tests prefer an editor.** |
| `dbarnes` | editor | A senior editor of `publicknowledge` (also a sectionEditor in both sections) |
| `dbuskins` | sectionEditor | Section editor for **Articles** (`ART`). Default pick when you need "a section editor". |
| `sberardo` | sectionEditor | Another Articles section editor |
| `minoue` | sectionEditor | Section editor for **Reviews** (`REV`) |
| `jjanssen` | reviewer | Default reviewer. First in the list — use this when you just need "a reviewer". |
| `phudson` | reviewer | A second reviewer (e.g. to model multiple reviews on one submission) |
| `amccrae` | reviewer | A third reviewer |
| `agallego` | reviewer | A fourth reviewer |
| `mfritz` | copyeditor | Copyediting actions |
| `svogt` | copyeditor | A second copyeditor |
| `gcox` | layoutEditor | Layout / galley production |
| `shellier` | layoutEditor | A second layout editor |
| `cturner` | proofreader | Proofreading actions |
| `skumar` | proofreader | A second proofreader |

**No pre-seeded author.** Authors are created implicitly at submission time. If a test needs "an author", either create one via the API bootstrap extensions, or have an existing user (e.g. `jjanssen`) submit — they pick up the `author` role on the new submission.

**No pre-seeded reader / subscriber.** The seed data does not include a plain reader or a subscription-manager user.

## Password rule

Defined at `lib/pkp/playwright/data/users.js:26-31`:

```js
getPassword(username) {
    return username === 'admin' ? 'admin' : username + username;
}
```

- `admin` → `admin`
- everyone else → **username repeated twice** (e.g. `dbarnes` → `dbarnesdbarnes`, `jjanssen` → `jjanssenjjanssen`)

This matches the Cypress convention (`lib/pkp/cypress/support/commands.js:20`), so credentials port across suites.

### Special case: `rvaca`

`rvaca` is flagged `mustChangePassword: true`. On first login, OJS forces a password reset. For tests that just need "a journal manager", prefer `dbarnes` (who has editor privileges broad enough for most manager-style actions) unless the test is specifically about manager-only settings.

## Login flow internals

### `ensureAuthStateFor(browser, username, {baseURL})`

Defined at `lib/pkp/playwright/support/auth.js:34-63`.

Flow:
1. Check for `<appRoot>/playwright/.auth/<username>.json`. If present, return its path — no login happens.
2. Otherwise: open a fresh browser context, drive `LoginPage` to submit `username` + `getPassword(username)`, wait for redirect away from `/login` (10 s timeout), snapshot `context.storageState()` to the JSON file, close the context, return the path.
3. Under parallel workers, two workers may race on the missing file. Both perform a successful login (OJS allows concurrent sessions per user), last write wins, tests keep working.

Login URL: `/index.php/index/en/login` (see `LoginPage.js:24`). Form selectors are stable IDs:
- `input#username`
- `input#password`
- `form#login button` (submit)

### How specs consume it

Two paths, depending on the shape of the test:

**Single-actor (default):**
```js
test.use({user: 'dbarnes'});
test('...', async ({page}) => {
    // page is already logged in as dbarnes via storageState
});
```
The `storageState` fixture at `base-test.js:38-44` reads the `user` option, calls `ensureAuthStateFor`, and loads the cached file before the page is created.

**Multi-actor:**
```js
test('...', async ({page, asUser}) => {
    const reviewerCtx = await asUser('jjanssen');
    const reviewerPage = await reviewerCtx.newPage();
});
```
See `asUser` in `base-test.js:46-62`.

### Bootstrap prerequisite

Auth only works after the setup project has run (`bootstrap.setup.js`, governed by `config-factory.js:66-71`). The setup runs serially before every test project and seeds:
1. The test database (schema via `tools/installTest.php`)
2. The `publicknowledge` journal (from `playwright/fixtures/bootstrap.js`)
3. All 15 non-admin users (admin is created by the installer)

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
| `ART` | Articles | dbarnes, dbuskins, sberardo | Word count limit 500 |
| `REV` | Reviews | dbarnes, minoue | Abstracts not required; identifyType "Review Article" |

### Categories

Two top-level: `applied-science` (with nested `comp-sci/computer-vision` and `eng`) and `social-sciences` (with `sociology` and `anthropology`).

### Issues

- Volume 1, Number 2, 2014 — **published**
- Volume 2, Number 1, 2015 — unpublished (upcoming)

When a test needs to publish to an issue, use the unpublished one unless you specifically want to edit a back issue.
