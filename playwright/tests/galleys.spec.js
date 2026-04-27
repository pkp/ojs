// @ts-check
const path = require('path');
const {test, expect} = require('../support/fixtures.js');
const {EditorialWorkflowPage} = require('../pages/EditorialWorkflowPage.js');
const submissionPublished = require('../fixtures/scenarios/submission-published.js');

/**
 * Galleys — row #51 in docs/e2e-playwright-migration.md.
 *
 * Ports the galley-creation slice of cypress/tests/data/60-content/
 * AmwandengaSubmission.cy.js (lines 363–380, plus the reader-side
 * `cy.checkViewableGalley('PDF')` at line 429). Galleys live on top
 * of a published publication, so each test seeds the canonical
 * `submissionPublished({tag})` scenario and drives the Publication →
 * Galleys panel as dbarnes.
 *
 * Wave 7 marker — this is the first row to exercise the bundled
 * default Article Text file that every seeded submission now ships
 * with (Step 2 of the scenario-extensions plan; see
 * lib/pkp/playwright/tests/scenario-default-file.spec.js). The Add
 * Galley UI accepts any file via `<input type=file>`; we re-use the
 * same `default-article.pdf` fixture for the galley source so we
 * keep one PDF blob across the suite.
 *
 * Scope vs. Cypress:
 *   - One PDF galley + reader download link (the row's primary
 *     capability assertion).
 *   - One delete cycle (the row's CRUD coverage; subsumes the
 *     Cypress edit + URL-path round-trips, which exercise the same
 *     fbv form a second time).
 *   - Drop the "PDF + HTML" two-format variant — the upload wizard
 *     is genre-agnostic, so a single PDF run is sufficient evidence
 *     the file pipeline works. Re-add an HTML variant if a future
 *     row exercises a galley-format-specific surface (e.g. inline
 *     HTML rendering on the article page).
 *   - Drop the legacy `articleGalleyForm`-error / `wait(1000)` /
 *     "wait for jQuery" idioms — the POM's per-step `expect(...
 *     visible)` waits replace them deterministically.
 */
test.describe('Galleys', () => {
	test(
		'editor adds a PDF galley to a published article; reader sees the download link',
		async ({pkpApi, asUser, browser, baseURL}) => {
			const tag = uniqueTag(test.info(), 'add');
			const spec = submissionPublished({tag});
			const {submission} = await pkpApi.createSubmission(spec);

			const ctx = await asUser('dbarnes');
			const page = await ctx.newPage();
			const workflow = new EditorialWorkflowPage(page);
			await workflow.goto(submission.id);

			await workflow.openPublicationPanel('Galleys');
			await workflow.addGalley({
				label: 'PDF',
				filePath: galleyFixturePath(),
				urlPath: `pdf-${tag}`,
			});

			// Reader side — anonymous context, no storageState. The galley
			// renders as `a.obj_galley_link` whose href points at the
			// publicly resolvable URL (urlPath when set, galley id otherwise).
			await expectReaderShowsGalley({
				browser,
				baseURL,
				submissionId: submission.id,
				label: 'PDF',
				urlPathFragment: `pdf-${tag}`,
			});
		},
	);

	test(
		'editor deletes a galley; reader no longer sees the download link',
		async ({pkpApi, asUser, browser, baseURL}) => {
			const tag = uniqueTag(test.info(), 'delete');
			const spec = submissionPublished({tag});
			const {submission} = await pkpApi.createSubmission(spec);

			const ctx = await asUser('dbarnes');
			const page = await ctx.newPage();
			const workflow = new EditorialWorkflowPage(page);
			await workflow.goto(submission.id);

			// Create the galley first so we have something to delete.
			await workflow.openPublicationPanel('Galleys');
			await workflow.addGalley({
				label: 'PDF',
				filePath: galleyFixturePath(),
				urlPath: `pdf-${tag}`,
			});

			// Reader sees the link before delete (sanity baseline).
			await expectReaderShowsGalley({
				browser,
				baseURL,
				submissionId: submission.id,
				label: 'PDF',
				urlPathFragment: `pdf-${tag}`,
			});

			await workflow.deleteGalley('PDF');

			// Reader: the PDF galley link is gone. Note: the seeded
			// `submissionPublished` fixture also publishes a JATS XML
			// representation (`a.obj_galley_link.xml`) so we filter on
			// the PDF label rather than asserting zero galley links.
			await expectReaderHasNoGalley({
				browser,
				baseURL,
				submissionId: submission.id,
				label: 'PDF',
			});
		},
	);
});

/**
 * Reader-side assertion — anonymous browser context navigates to the
 * canonical numeric article URL, expects 200, and asserts a single
 * `a.obj_galley_link` whose text is the galley label and whose href
 * contains the urlPath fragment (galley download/view route).
 *
 * @param {{
 *   browser: import('@playwright/test').Browser,
 *   baseURL?: string,
 *   submissionId: number,
 *   label: string,
 *   urlPathFragment: string,
 * }} opts
 */
async function expectReaderShowsGalley({
	browser,
	baseURL,
	submissionId,
	label,
	urlPathFragment,
}) {
	const ctx = await browser.newContext({baseURL, reducedMotion: 'reduce'});
	try {
		const page = await ctx.newPage();
		const resp = await page.goto(
			`/index.php/publicknowledge/article/view/${submissionId}`,
		);
		expect(resp?.status()).toBe(200);
		const link = page
			.locator('a.obj_galley_link')
			.filter({hasText: label});
		await expect(link).toHaveCount(1);
		await expect(link).toHaveAttribute(
			'href',
			new RegExp(`/article/view/${submissionId}/${escapeRegex(urlPathFragment)}`),
		);
	} finally {
		await ctx.close();
	}
}

/**
 * Reader-side assertion — after delete, the article page renders but
 * the named galley link is gone. We match by visible label rather than
 * counting all `a.obj_galley_link` elements because the seeded fixture
 * also publishes a JATS XML representation (its own
 * `a.obj_galley_link.xml`) regardless of whether the deleted PDF is
 * present.
 *
 * @param {{
 *   browser: import('@playwright/test').Browser,
 *   baseURL?: string,
 *   submissionId: number,
 *   label: string,
 * }} opts
 */
async function expectReaderHasNoGalley({browser, baseURL, submissionId, label}) {
	const ctx = await browser.newContext({baseURL, reducedMotion: 'reduce'});
	try {
		const page = await ctx.newPage();
		const resp = await page.goto(
			`/index.php/publicknowledge/article/view/${submissionId}`,
		);
		expect(resp?.status()).toBe(200);
		await expect(
			page.locator('a.obj_galley_link').filter({hasText: label}),
		).toHaveCount(0);
	} finally {
		await ctx.close();
	}
}

/**
 * Resolve the bundled lib/pkp default-article.pdf fixture. We re-use
 * the same PDF that the scenario-default-file flow uploads so a galley
 * source doesn't grow into its own fixture.
 */
function galleyFixturePath() {
	return path.resolve(
		__dirname,
		'../../lib/pkp/playwright/fixtures/files/default-article.pdf',
	);
}

/** Escape a string for use inside a RegExp literal. */
function escapeRegex(s) {
	return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Build a worker-scoped tag so parallel runs don't collide.
 * @param {import('@playwright/test').TestInfo} info
 * @param {string} suffix
 */
function uniqueTag(info, suffix) {
	const slug = info.title
		.toLowerCase()
		.replace(/[^a-z0-9]+/g, '-')
		.slice(0, 16);
	return `g-w${info.parallelIndex}-${suffix}-${slug}`;
}
