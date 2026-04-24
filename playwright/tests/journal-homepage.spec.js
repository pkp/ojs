// @ts-check
const {test, expect} = require('../support/fixtures.js');
const submissionPublished = require('../fixtures/scenarios/submission-published.js');

/**
 * Journal homepage — row #35 in docs/e2e-playwright-migration.md.
 *
 * Reader-only smoke coverage for the two public landing pages a journal
 * exposes: the journal index (`/publicknowledge`) and the issue archive
 * (`/publicknowledge/issue/archive`). Cypress had no direct equivalent —
 * these assertions were implicit in the old serial suite (later specs
 * relied on the homepage having rendered correctly beforehand).
 *
 * Scope — two reader-only tests. Both assert reasonable theme-independent
 * landmarks (h1/h2, page title, published-issue identification string).
 *
 * Seeding:
 *   - The first test seeds a `submissionPublished` so the current-issue
 *     section on the homepage has at least one article to render (the
 *     bootstrap's Vol. 1 No. 2 (2014) issue is published but empty out
 *     of the box). The test asserts on page-level landmarks, not the
 *     article listing shape, but seeding gives the rendered section
 *     real content.
 *   - The second test reads the archive page directly; the bootstrap
 *     issue (Vol. 1 No. 2, 2014) is already published, so no extra
 *     seeding is needed to assert it shows up. If the whole bootstrap
 *     had no published issues the archive would fall back to the
 *     "no issues published" message instead, breaking the assertion.
 *
 * Scope deviations vs. the row's acceptance criteria:
 *   - "Section grouping on TOC" is deferred to a future galleys /
 *     issue-assignment spec (rows #30 + #51). It requires seeded
 *     published articles with section assignments AND galleys, which
 *     is blocked on E1/E5.
 */
test.describe('Journal homepage', () => {
	test(
		'journal index renders the current-issue section with the expected headings',
		async ({pkpApi, browser, baseURL}) => {
			// Seed a published submission so the current-issue section on
			// the homepage has at least one entry in its TOC include. The
			// submission's metadata isn't asserted here — landing-page
			// rendering is the capability under test.
			const tag = uniqueTag(test.info(), 'home-index');
			await pkpApi.createSubmission(submissionPublished({tag}));

			const ctx = await browser.newContext({baseURL, reducedMotion: 'reduce'});
			try {
				const page = await ctx.newPage();
				const resp = await page.goto('/index.php/publicknowledge/');
				expect(resp?.status()).toBe(200);

				// <title> is built from the journal's localized name
				// (templates/frontend/components/header.tpl passes
				// pageTitleTranslated=$currentJournal->getLocalizedName()).
				await expect(page).toHaveTitle(/Journal of Public Knowledge/);

				// The h1 on the index renders the journal name via
				// header.tpl's page-header include; asserting that the
				// page has any visible h1 at all is theme-independent.
				// (The default theme also places `.current_issue` with a
				// "Current Issue" h2 when an issue is published — that's
				// the landmark we care about.)
				const h1 = page.locator('h1').first();
				await expect(h1).toBeVisible();

				// Default-theme landmark: the "Current Issue" h2 inside
				// `section.current_issue`. indexJournal.tpl renders this
				// unconditionally when there's a current issue. Use a
				// forgiving substring on the localized string so minor
				// translation tweaks don't break us.
				const currentIssueHeading = page
					.locator('h2')
					.filter({hasText: /Current Issue/i})
					.first();
				await expect(currentIssueHeading).toBeVisible();

				// The current issue (Vol. 1 No. 2 (2014)) is seeded by
				// bootstrap; its identifying string must appear somewhere
				// on the homepage (either in the title block or in the
				// TOC).
				await expect(
					page.getByText(/Vol\. 1 No\. 2 \(2014\)/),
				).toBeVisible();
			} finally {
				await ctx.close();
			}
		},
	);

	test(
		'issue archive lists the bootstrapped published issue',
		async ({browser, baseURL}) => {
			const ctx = await browser.newContext({baseURL, reducedMotion: 'reduce'});
			try {
				const page = await ctx.newPage();
				const resp = await page.goto(
					'/index.php/publicknowledge/issue/archive',
				);
				expect(resp?.status()).toBe(200);

				// issueArchive.tpl wraps the page in
				// `.page.page_issue_archive`. Its h1 is the localized
				// `archive.archives` string ("Archives" in en).
				const archiveWrapper = page.locator('.page_issue_archive');
				await expect(archiveWrapper).toBeVisible();
				await expect(archiveWrapper.locator('h1').first()).toContainText(
					/Archives/i,
				);

				// Bootstrap publishes Vol. 1 No. 2 (2014); the issue
				// summary include renders that identification string in
				// every theme.
				const archiveList = page.locator('ul.issues_archive');
				await expect(archiveList).toBeVisible();
				await expect(archiveList).toContainText('Vol. 1 No. 2 (2014)');
			} finally {
				await ctx.close();
			}
		},
	);
});

/**
 * Build a tag scoped to this worker + test title so parallel workers
 * don't collide on the shared submissions list. Mirrors the helper
 * used in lib/pkp/playwright/tests/versioning.spec.js and
 * publish-unpublish.spec.js.
 *
 * @param {import('@playwright/test').TestInfo} info
 * @param {string} suffix
 */
function uniqueTag(info, suffix) {
	const slug = info.title
		.toLowerCase()
		.replace(/[^a-z0-9]+/g, '-')
		.slice(0, 16);
	return `t-w${info.parallelIndex}-${suffix}-${slug}`;
}
