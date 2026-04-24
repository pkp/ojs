// @ts-check
const {test, expect} = require('../support/fixtures.js');
const submissionPublished = require('../fixtures/scenarios/submission-published.js');

/**
 * Pubmed metadata — row #37 in docs/e2e-playwright-migration.md.
 *
 * Ports cypress/tests/integration/Pubmed.cy.js. The Cypress source
 * exercises the PubMed importexport plugin's submission export endpoint:
 * POST to
 * `/management/importexport/plugin/PubMedExportPlugin/exportSubmissions`
 * with `selectedSubmissions[]=<id>` returns a PubMed/MEDLINE XML document
 * whose `<Article>` carries the journal's `<Issn>` and the seeded
 * submission's `<ArticleTitle>`. PubMed is an `importexport`-category
 * plugin and ships auto-loaded on every OJS install (see
 * `plugins/importexport/pubmed/version.xml`), so no plugin-gallery
 * enable step is needed — the endpoint is live on any authenticated
 * manager session against any OJS journal.
 *
 * Scope — two tests:
 *   1. Authenticated manager exports a seeded submission; XML round-trips
 *      the seeded title + the journal's ISSN.
 *   2. Anonymous access to the same endpoint is rejected (the endpoint
 *      sits under `/management/` and is guarded by the manager role);
 *      this pins the access-control contract without a second seed.
 *
 * Scope deviations vs. roadmap cell + prompt:
 *   - Roadmap cell reads "plugin config (enable Pubmed export) · R:
 *     Pubmed meta tags on article page." The PubMed plugin is an
 *     importexport plugin; it does not emit citation_* or Pubmed meta
 *     tags on any article page (that's what the Highwire/Google Scholar
 *     plugin does, which is a different row). The Cypress source
 *     matches the actual capability — XML export — and we follow the
 *     source. The doc cell tick is updated to reflect this.
 *   - No E0 scratch journal. The plugin is pre-loaded, and the
 *     bootstrap seeds a published submission-friendly journal
 *     (publicknowledge) we can export against. `submissionPublished`
 *     seeds the submission per-test so parallel workers don't collide.
 *   - No issue-export variant. The plugin's `exportIssues` path uses
 *     the same filter + XML shape; exporting a seeded submission
 *     already proves the filter wires up. If a future regression
 *     targets the issues path specifically, add a sibling test.
 */
test.describe('Pubmed metadata export', () => {
	test('manager exports a published submission as PubMed XML containing ISSN and ArticleTitle', async ({
		pkpApi,
		asUser,
	}) => {
		const tag = uniqueTag(test.info(), 'export');
		// submissionPublished appends `[${tag}]` to every title locale via
		// PublicationsProcessor (see
		// playwright/fixtures/scenarios/submission-published.js); the full
		// title written to disk is `Published article [${tag}]`. We
		// search the exported XML for that suffix to confirm the seeded
		// submission — not some sibling — landed in the response.
		const spec = submissionPublished({tag});
		const {submission} = await pkpApi.createSubmission(spec);

		const ctx = await asUser('dbarnes');
		const page = await ctx.newPage();

		// Hit the plugin landing page first to obtain a CSRF token —
		// matches the Cypress source's `cy.window().then((win) =>
		// win.pkp.currentUser.csrfToken)` idiom. The submissions
		// list panel renders here, confirming the plugin is live for
		// this manager session.
		const landingResp = await page.goto(
			'/index.php/publicknowledge/en/management/importexport/plugin/PubMedExportPlugin',
		);
		expect(landingResp?.status()).toBe(200);

		const csrfToken = await page.evaluate(
			() => window.pkp?.currentUser?.csrfToken,
		);
		expect(csrfToken, 'manager landing page must expose csrfToken').toBeTruthy();

		// Export the seeded submission. The plugin's exportSubmissions
		// handler reads `selectedSubmissions[]` and returns the
		// serialized XML as a download. We don't need the download
		// mechanics — `request.post` captures the response body
		// directly. `FileManager::downloadByPath` streams the XML
		// inline and the plugin's `display()` method exits after — on
		// a slow dev server this can take 10+ s, so override the
		// default 10 000 ms actionTimeout.
		const exportResp = await page.request.post(
			'/index.php/publicknowledge/en/management/importexport/plugin/PubMedExportPlugin/exportSubmissions',
			{
				headers: {'X-Csrf-Token': csrfToken},
				form: {
					'selectedSubmissions[]': String(submission.id),
				},
				timeout: 30_000,
			},
		);
		expect(exportResp.status()).toBe(200);

		const xml = await exportResp.text();

		// Top-level PubMed XML wrapper + at least one Article.
		expect(xml).toMatch(/<ArticleSet[\s\S]*<\/ArticleSet>/);
		expect(xml).toMatch(/<Article[\s\S]*<\/Article>/);

		// The bootstrap publicknowledge journal seeds onlineIssn
		// "0378-5955" (see Cypress source's expectation and the
		// bootstrap journal spec). createJournalNode prefers printIssn,
		// then issn, then onlineIssn — the bootstrap only sets
		// onlineIssn, so that's what lands in <Issn>.
		expect(xml).toMatch(/<Issn>0378-5955<\/Issn>/);

		// The seeded submission's title carries the unique tag suffix;
		// round-tripping it through <ArticleTitle> proves the export
		// targeted *our* submission rather than a sibling seeded by a
		// parallel worker. `getLocalizedTitle($publicationLocale,
		// 'html')` feeds ArticleTitle, so whitespace matches on the
		// seeded "Published article [tag]".
		expect(xml).toMatch(
			new RegExp(`<ArticleTitle>[^<]*Published article[^<]*${escapeRegex(tag)}[^<]*</ArticleTitle>`),
		);
	});

	test('anonymous request to the Pubmed export endpoint is rejected', async ({
		browser,
		baseURL,
	}) => {
		const anonCtx = await browser.newContext({baseURL, reducedMotion: 'reduce'});
		try {
			// No CSRF header, no session cookie. The /management/
			// route is guarded by the authorize() callback on
			// ManagementHandler which requires the SITE_ADMIN or
			// ROLE_ID_MANAGER role. A logged-out request lands on the
			// login redirect path (302) or gets a direct 403 —
			// implementation dependent. Accept any non-2xx.
			const resp = await anonCtx.request.post(
				'/index.php/publicknowledge/en/management/importexport/plugin/PubMedExportPlugin/exportSubmissions',
				{
					form: {'selectedSubmissions[]': '1'},
					maxRedirects: 0,
				},
			);
			// 2xx would mean the endpoint served the XML to a
			// passer-by — a security regression. The actual observed
			// response is a 302 to /login. Don't pin to a specific
			// non-2xx code; just assert it's not a success.
			expect(resp.status(), `unexpected success status ${resp.status()}`)
				.toBeGreaterThanOrEqual(300);

			// And if the server responded 200 with a login page, the body
			// wouldn't contain PubMed XML. Belt-and-braces check so we
			// fail loudly if the route ever starts serving a friendly
			// redirect body.
			const body = await resp.text().catch(() => '');
			expect(body).not.toMatch(/<ArticleTitle>/);
			expect(body).not.toMatch(/<Issn>/);
		} finally {
			await anonCtx.close();
		}
	});
});

/**
 * Build a tag scoped to this worker + test title so parallel workers
 * don't collide on the shared publications list. Mirrors the helper
 * used in article-dc-metadata.spec.js / article-statistics.spec.js.
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

/**
 * Escape a string for inclusion in a RegExp. Tags include hyphens only
 * today, but stay safe in case the slug generator grows.
 *
 * @param {string} s
 */
function escapeRegex(s) {
	return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}
