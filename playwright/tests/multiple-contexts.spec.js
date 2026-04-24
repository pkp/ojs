// @ts-check
const {test, expect} = require('../../lib/pkp/playwright/support/base-test.js');
const {ensureAuthStateFor} = require('../../lib/pkp/playwright/support/auth.js');

/**
 * Multiple contexts — row #46 in docs/e2e-playwright-migration.md.
 *
 * Cypress source: cypress/tests/integration/MultipleContexts.cy.js. The
 * Cypress test there has a single behaviour: admin disables a journal,
 * asserts the public journal page redirects to login, then re-enables.
 * That's not actually "multi-context" coverage — it's single-journal
 * enable/disable UI. The roadmap cell in contrast scopes this row to
 * "second journal; cross-journal navigation; user roles scoped per
 * journal" — i.e. the capability OJS promises but no Cypress spec
 * exercises: a user with different roles on different journals can
 * reach both.
 *
 * Test seeds a scratch journal via E0 (pkpApi.createJournal) with
 * dbarnes as manager. dbarnes is already an editor on the bootstrap's
 * publicknowledge journal (see lib/pkp/playwright/data/users.js), so
 * after seeding she holds two distinct role sets in two different
 * contexts. The spec then verifies:
 *   - the site index page lists BOTH journals (the anonymous
 *     templates/frontend/pages/indexSite.tpl `.journals ul` listing);
 *   - as dbarnes, both journals' dashboards load: publicknowledge's
 *     editorial dashboard (editor role → /dashboard/editorial) and
 *     the scratch journal's editorial dashboard (manager role maps to
 *     the same editorial dashboard);
 *   - the running session cleanly hops between journal URLs without
 *     forcing reauth.
 *
 * We don't drive the `app-user-nav` dropdown's journal switcher
 * affordance because none exists in the current OJS header — context
 * switching happens via direct URL (`/index.php/<journalPath>/…`). The
 * anonymous journal listing + dbarnes's ability to reach both
 * journals' dashboards is the functional equivalent of "user with
 * different roles across two journals sees them" per the roadmap.
 *
 * Scope deviations vs. Cypress source:
 *   - Dropped the journal enable/disable UI round-trip. The Cypress
 *     test's workflow (admin/contexts grid → show_extras → Edit →
 *     uncheck Enable → Save → assert public URL redirects to Login)
 *     exercises the legacy `contextGrid` jQuery UI, which is covered
 *     implicitly by the site-wide admin/contexts surface used by row
 *     #44 / #45 predecessors. The capability under test here is
 *     multi-context role scoping, not the enable toggle.
 */
test.describe('Multiple contexts', () => {
	test(
		'user with different roles across two journals reaches both dashboards',
		{tag: '@regression'},
		async ({pkpApi, browser, baseURL}) => {
			const tag = uniqueTag(test.info(), 'mc');

			// E0 scratch journal. dbarnes is already a publicknowledge
			// editor via the bootstrap; assigning her as scratch-journal
			// manager gives two distinct context roles for one user.
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});
			expect(context.path).toMatch(/^j-/);

			// Anonymous site index lists every enabled journal. The
			// default OJS site skin emits `.journals ul > li` with one
			// item per journal. We assert both journals are present —
			// the bootstrapped "Journal of Public Knowledge" and the
			// scratch journal's URL path.
			const anonCtx = await browser.newContext({
				baseURL,
				reducedMotion: 'reduce',
			});
			try {
				const page = await anonCtx.newPage();
				const resp = await page.goto('/index.php/index');
				expect(resp?.status()).toBe(200);
				// publicknowledge always renders under its "Journal of
				// Public Knowledge" localized name. Anchor on the
				// `<a rel="bookmark">` anchor inside `.journals` — the
				// `.description` text also matches "Journal of Public
				// Knowledge" in the bootstrap fixture, so a plain
				// getByText is ambiguous.
				await expect(
					page.locator(
						'.journals a[rel="bookmark"]',
						{hasText: 'Journal of Public Knowledge'},
					),
				).toBeVisible({timeout: 10_000});
				// The scratch journal's localized name defaults to the
				// tag when the spec doesn't override `name`. Look for the
				// anchor whose href contains the scratch URL path —
				// robust against whatever localized title the
				// ContextBuilderProcessor assigns by default.
				await expect(
					page.locator(
						`.journals a[href*="/${context.path}"]`,
					).first(),
				).toBeVisible({timeout: 10_000});
			} finally {
				await anonCtx.close();
			}

			// As dbarnes, reach publicknowledge's editorial dashboard
			// (her editor role there). A live 200 without /login
			// redirect proves the session is valid for that journal.
			const dbarnesCtx = await browser.newContext({
				storageState: await ensureAuthStateFor(browser, 'dbarnes', {
					baseURL,
				}),
				baseURL,
				reducedMotion: 'reduce',
			});
			try {
				const page = await dbarnesCtx.newPage();

				// Journal 1: publicknowledge. Editor role → editorial
				// dashboard. The backend layout (lib/pkp/templates/
				// layouts/backend.tpl) renders:
				//   - `.app__contextTitle` — the current journal's name
				//   - `.app__contexts` dropdown — list of OTHER journals
				//     the user holds a role on (populated from
				//     `$availableContexts`, filtered to exclude the
				//     current one)
				// The existence of that dropdown is the canonical "user
				// has roles on more than one journal" UI signal.
				const resp1 = await page.goto(
					'/index.php/publicknowledge/dashboard/editorial',
				);
				expect(resp1?.status()).toBe(200);
				await expect(page).not.toHaveURL(/\/login/);
				// `.app__contextTitle` renders "Journal of Public
				// Knowledge" — the current journal.
				await expect(page.locator('.app__contextTitle')).toContainText(
					'Journal of Public Knowledge',
					{timeout: 15_000},
				);
				// The `.app__contexts` dropdown surfaces only the OTHER
				// journal(s). Open it and assert an <a> pointing to the
				// scratch journal's URL path exists in its dropdown
				// list.
				await page.locator('.app__contexts button').first().click();
				await expect(
					page.locator(
						`.app__contexts a[href*="/${context.path}"]`,
					),
				).toBeVisible({timeout: 10_000});

				// Cross-journal navigation. Walk directly to the scratch
				// journal's dashboard — dbarnes holds the manager role
				// there, which also resolves into the editorial
				// dashboard role-assignment set (ROLE_ID_MANAGER is in
				// the addRoleAssignment list for `editorial`).
				const resp2 = await page.goto(
					`/index.php/${context.path}/dashboard/editorial`,
				);
				expect(resp2?.status()).toBe(200);
				await expect(page).not.toHaveURL(/\/login/);
				// URL must have landed inside the scratch journal path,
				// not redirected back to publicknowledge.
				expect(page.url()).toContain(`/${context.path}/`);
				// Current context reflects the scratch journal now. The
				// scratch journal's default localized name is set by
				// ContextBuilderProcessor and includes the tag.
				await expect(
					page.locator('.app__contextTitle'),
				).not.toContainText('Journal of Public Knowledge', {
					timeout: 15_000,
				});
				// And the app__contexts dropdown lists the OTHER
				// journal — publicknowledge — because its link is
				// filtered out of the current context's slot.
				await page.locator('.app__contexts button').first().click();
				await expect(
					page
						.locator('.app__contexts a')
						.filter({hasText: 'Journal of Public Knowledge'}),
				).toBeVisible({timeout: 10_000});

				// Hop back to publicknowledge. The session retains
				// access — no login re-prompt.
				const resp3 = await page.goto(
					'/index.php/publicknowledge/dashboard/editorial',
				);
				expect(resp3?.status()).toBe(200);
				await expect(page).not.toHaveURL(/\/login/);
				expect(page.url()).toContain('/publicknowledge/');
			} finally {
				await dbarnesCtx.close();
			}
		},
	);
});

/**
 * Build a tag scoped to this worker + test title so parallel workers
 * don't collide on the shared journals list.
 *
 * @param {import('@playwright/test').TestInfo} info
 * @param {string} suffix
 */
function uniqueTag(info, suffix) {
	const slug = info.title
		.toLowerCase()
		.replace(/[^a-z0-9]+/g, '-')
		.slice(0, 12);
	const rand = Math.random().toString(36).slice(2, 6);
	return `mc-w${info.parallelIndex}-${suffix}-${slug}-${rand}`;
}
