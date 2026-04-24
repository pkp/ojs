// @ts-check
const {test, expect} = require('../support/fixtures.js');
const submissionPublished = require('../fixtures/scenarios/submission-published.js');

/**
 * Article DC metadata — row #34 in docs/e2e-playwright-migration.md.
 *
 * Ports cypress/tests/integration/Z_ArticleViewDCMetadata.cy.js, which
 * creates a fully-published submission (with keywords, license,
 * multilingual titles, a PDF galley, an issue assignment, a DOI config)
 * and then asserts that a long list of DC.* `<meta>` tags appears in the
 * article page's HTML.
 *
 * The Cypress source depends on editor-driven metadata mutation
 * (enabling coverage + type + keyword fields, adding French locale
 * metadata through the workflow UI, attaching a PDF galley). Most of
 * that drives DC elements that are **not** in the reader-only
 * capability contract of this row — they're the responsibility of
 * rows #27 (metadata editing) and #51 (galleys). The DublinCoreMeta
 * plugin is the unit under test here, and it's enabled by default on
 * every OJS journal (see plugins/generic/dublinCoreMeta/settings.xml),
 * so the `publicknowledge` bootstrap journal already has it active.
 *
 * Scope — two reader-only tests on seeded published submissions:
 *   1. Core DC meta tags (Title, Creator.PersonalName, Identifier,
 *      Type, Language, Date.issued, Source).
 *   2. DC.Subject per keyword + DC.Rights from copyrightHolder and
 *      licenseUrl.
 *
 * Scope deviations vs. Cypress:
 *   - Drop coverage / type / disciplines / supportingAgencies — those
 *     metadata fields require the Metadata settings tab to enable them,
 *     which is row #16's territory; they would need an E0 scratch
 *     journal.
 *   - Drop the multilingual DC.Title.Alternative / DC.Description
 *     assertions — the submissionPublished fixture is single-locale
 *     (English) out of the box, and extending it to seed per-locale
 *     abstract / title pairs is duplicate coverage for the multilingual
 *     feature (row #5). If a future multilingual-publication spec lands
 *     we can add a `DC.Title.Alternative` assertion there.
 *   - Drop DC.Format (requires a galley file, row #51, blocked on E1).
 *   - Drop DC.Identifier.DOI (requires DOI plugin enablement, row #31).
 *   - Drop missing-translation fallback — the Cypress source doesn't
 *     actually exercise that path either; the locale-fallback logic
 *     lives in Publication::getLocalizedFullTitle and is covered by
 *     unit tests in lib/pkp.
 *
 * No POM needed — this is a pure reader-side HTML-meta assertion spec.
 */
test.describe('Article DC metadata', () => {
	test(
		'article page emits core DC meta tags (title, creator, identifier, type, language, issued, source)',
		async ({pkpApi, browser, baseURL}) => {
			const tag = uniqueTag(test.info(), 'dc-core');
			const spec = submissionPublished({
				tag,
				// submissionPublished attaches `tag` to every title locale
				// (see PublicationsProcessor), so the title that lands on
				// disk is `Published article ${tag}`. Assertions below match
				// on the stable `Published article` prefix.
			});
			const {submission, publications} = await pkpApi.createSubmission(spec);
			expect(publications).toHaveLength(1);

			const ctx = await browser.newContext({baseURL, reducedMotion: 'reduce'});
			try {
				const page = await ctx.newPage();
				const resp = await page.goto(
					`/index.php/publicknowledge/article/view/${submission.id}`,
				);
				expect(resp?.status()).toBe(200);

				// DC.Title — prefix + title + subtitle; here just the title.
				// substring match so the appended tag doesn't break the
				// assertion.
				const dcTitle = await page
					.locator('meta[name="DC.Title"]')
					.getAttribute('content');
				expect(dcTitle).not.toBeNull();
				expect(dcTitle).toContain('Published article');

				// DC.Creator.PersonalName — at least one per author. The
				// submitter (rvaca) is the sole author on the seeded
				// publication.
				const creators = await page
					.locator('meta[name="DC.Creator.PersonalName"]')
					.count();
				expect(creators).toBeGreaterThanOrEqual(1);
				const firstCreator = await page
					.locator('meta[name="DC.Creator.PersonalName"]')
					.first()
					.getAttribute('content');
				expect(firstCreator).toBeTruthy();

				// DC.Identifier — urlPath when set, otherwise submissionId;
				// scenario doesn't set urlPath, so this is the numeric id.
				const dcIdentifier = await page
					.locator('meta[name="DC.Identifier"]')
					.getAttribute('content');
				expect(dcIdentifier).toBe(String(submission.id));

				// DC.Identifier.URI — full canonical article URL.
				const dcUri = await page
					.locator('meta[name="DC.Identifier.URI"]')
					.getAttribute('content');
				expect(dcUri).toContain(`/article/view/${submission.id}`);

				// DC.Type — the plugin always emits the constant
				// "Text.Serial.Journal" first, then optionally per-locale
				// type values. Confirm the constant is present.
				const typeContents = await page
					.locator('meta[name="DC.Type"]')
					.evaluateAll((nodes) => nodes.map((n) => n.getAttribute('content')));
				expect(typeContents).toContain('Text.Serial.Journal');

				// DC.Type.articleType — section title ("Articles" from the
				// seeded ART section).
				const articleType = await page
					.locator('meta[name="DC.Type.articleType"]')
					.getAttribute('content');
				expect(articleType).toBe('Articles');

				// DC.Language — BCP47 form of the publication's primary
				// locale ("en").
				const dcLanguage = await page
					.locator('meta[name="DC.Language"]')
					.getAttribute('content');
				expect(dcLanguage).toBe('en');

				// DC.Date.issued — issue's datePublished; the seeded issue
				// (Vol. 1 No. 2, 2014) is published so this must exist.
				const dcDateIssued = await page
					.locator('meta[name="DC.Date.issued"]')
					.getAttribute('content');
				expect(dcDateIssued).toMatch(/^\d{4}-\d{2}-\d{2}$/);

				// DC.Source — journal name in its primary locale.
				const dcSource = await page
					.locator('meta[name="DC.Source"]')
					.getAttribute('content');
				expect(dcSource).toContain('Journal of Public Knowledge');

				// DC.Source.ISSN — bootstrap seeds `onlineIssn: '0378-5955'`.
				const dcIssn = await page
					.locator('meta[name="DC.Source.ISSN"]')
					.getAttribute('content');
				expect(dcIssn).toBe('0378-5955');
			} finally {
				await ctx.close();
			}
		},
	);

	test(
		'article page emits DC.Subject for each keyword and DC.Rights for copyright + license',
		async ({pkpApi, browser, baseURL}) => {
			const tag = uniqueTag(test.info(), 'dc-subject');
			// submissionPublished defaults already seed keywords
			// (['testing','published']), copyrightHolder, copyrightYear, and
			// licenseUrl — see playwright/fixtures/scenarios/
			// submission-published.js. Override keywords here to make the
			// test explicit; the rest of the publication metadata is left on
			// its defaults.
			const keywords = ['testing', 'dc-metadata', `kw-${tag}`];
			const spec = submissionPublished({tag});
			spec.publications[0].metadata.keywords = {en: keywords};
			const {submission} = await pkpApi.createSubmission(spec);

			const ctx = await browser.newContext({baseURL, reducedMotion: 'reduce'});
			try {
				const page = await ctx.newPage();
				const resp = await page.goto(
					`/index.php/publicknowledge/article/view/${submission.id}`,
				);
				expect(resp?.status()).toBe(200);

				// DC.Subject — plugin emits one per keyword (and one per
				// subject, if any). Collect every content attribute and
				// assert each seeded keyword appears.
				const subjectContents = await page
					.locator('meta[name="DC.Subject"]')
					.evaluateAll((nodes) => nodes.map((n) => n.getAttribute('content')));
				for (const kw of keywords) {
					expect(subjectContents).toContain(kw);
				}

				// DC.Rights — emitted twice: once for copyrightHolder +
				// copyrightYear, once for licenseUrl. Assert both signals
				// appear across the collected content values.
				const rightsContents = await page
					.locator('meta[name="DC.Rights"]')
					.evaluateAll((nodes) => nodes.map((n) => n.getAttribute('content')));
				expect(rightsContents.length).toBeGreaterThanOrEqual(2);
				expect(
					rightsContents.some((c) =>
						c && c.includes('https://creativecommons.org/licenses/by/4.0/'),
					),
				).toBe(true);
				// submission.copyrightStatement template substitutes the
				// holder + year into a localized string. The holder 'The
				// Author' always appears, regardless of which English locale
				// wording the template uses.
				expect(
					rightsContents.some((c) => c && c.includes('The Author')),
				).toBe(true);
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
