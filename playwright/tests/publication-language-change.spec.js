// @ts-check
const {test, expect} = require('../support/fixtures.js');
const {EditorialWorkflowPage} = require('../pages/EditorialWorkflowPage.js');
const {setTinyMceContent} = require('../../lib/pkp/playwright/support/tinymce.js');
const submissionPublished = require('../fixtures/scenarios/submission-published.js');

/**
 * Publication primary-language change — row #33 in
 * docs/e2e-playwright-migration.md.
 *
 * Ports cypress/tests/integration/ChangeSubmissionLanguage.cy.js: a single
 * end-to-end flow that proves
 *   1. the change-language affordance is suppressed while a publication
 *      is published,
 *   2. unpublishing surfaces the affordance, and changing the locale via
 *      the side modal round-trips through PUT /changeLocale, and
 *   3. after republishing, the public article page renders with the new
 *      title (i.e. the locale change actually took effect on the
 *      publication, not just on the metadata draft).
 *
 * UI shape — for future readers debugging this. The
 * `WorkflowChangeSubmissionLanguage` widget renders inside
 * `[data-cy="workflow-controls-left"]` on every Publication-panel item,
 * but only when `submission.status !== STATUS_PUBLISHED` and
 * `publications.length < 2` (see
 * lib/ui-library/.../workflowConfigEditorialOJS.js#getPrimaryControlsLeft).
 * Clicking its "Change" button opens a SideModal with
 * `<div id="changeSubmissionLanguage">` that wraps a PkpForm with:
 *   - a FieldRadioInput called `locale` (radio per supported locale,
 *     each `<input value="…">` keyed to the locale code),
 *   - a `metadata` group (showWhen the locale differs from the current)
 *     containing TinyMCE title + abstract controls keyed
 *     `changeSubmissionLanguageMetadata-{title|abstract}-control`.
 * Submitting fires PUT
 * /api/v1/submissions/{id}/publications/{pubId}/changeLocale and on
 * success the Vue store calls window.location.reload() — which is why
 * the original port timed out: it was waiting on the response while the
 * page was reloading itself underneath. We anchor on
 * page.waitForResponse(...changeLocale...) instead of waiting on the
 * UI text the reload would replace.
 *
 * Form.vue tunnels PUT through POST with X-Http-Method-Override (see
 * lib/pkp/playwright/tests/multilingual.spec.js for context); match on
 * URL only, not on method.
 *
 * Bootstrap note — `JournalProcessor` mirrors the spec's
 * `supportedLocales` onto `supportedSubmissionMetadataLocales` and
 * `supportedAddedSubmissionLocales` so the `/changeLocale` validator
 * accepts fr_CA out of the box. Earlier revisions of this spec paid
 * for that gap with a `withSubmissionMetadataLocale()` wrapper that
 * PUT'd the journal config at runtime; the bootstrap fix made the
 * wrapper redundant.
 */

test.describe('Publication language change', () => {
	test(
		"editor cannot change language while published; can change after unpublish; republish persists the new language",
		async ({pkpApi, asUser, browser, baseURL}) => {
			const tag = uniqueTag(test.info(), 'lang');
			const originalTitle = `Original title ${tag}`;
			const frenchTitle = `Titre français ${tag}`;
			const frenchAbstract =
				"<p>Résumé en français pour vérifier le changement de langue.</p>";

			const spec = {
				...submissionPublished({tag}),
				publications: [
					{
						versionStage: 'VoR',
						jatsPublicVisibility: true,
						metadata: {
							title: {en: originalTitle},
							abstract: {
								en: '<p>An English abstract for the language-change spec.</p>',
							},
							keywords: {en: ['language-change']},
							copyrightHolder: {en: 'The Author'},
							copyrightYear: 2026,
							licenseUrl: 'https://creativecommons.org/licenses/by/4.0/',
							pages: '1-10',
						},
						issue: {volume: 1, number: 2, year: 2014},
						published: true,
					},
				],
			};
			const {submission, publications} = await pkpApi.createSubmission(spec);
			expect(publications).toHaveLength(1);
			const publicationId = publications[0].id;

			const ctx = await asUser('dbarnes');
			const page = await ctx.newPage();
			const workflow = new EditorialWorkflowPage(page);

			await workflow.goto(submission.id);

			// --- 1. Affordance is hidden while published ---
			//
			// Cypress asserts the same shape via
			//   [data-cy="workflow-controls-left"] button:contains("Change")
			//     .should('not.exist')
			// Open the Title & Abstract panel first so the side-nav is
			// settled and the Publication-panel controls have rendered.
			await workflow.openPublicationPanel('Title & Abstract');
			const modal = workflow.workflowModal();
			await expect(
				modal.getByRole('heading', {name: /Title & Abstract/}),
			).toBeVisible({timeout: 10_000});

			// The "Change" button only appears alongside the change-language
			// widget; absence of the widget = absence of the button.
			const controlsLeft = modal.locator('[data-cy="workflow-controls-left"]');
			await expect(controlsLeft).toBeVisible({timeout: 10_000});
			await expect(
				controlsLeft.getByRole('button', {name: 'Change', exact: true}),
			).toHaveCount(0);

			// --- 2. Unpublish so the affordance surfaces ---
			await workflow.unpublishCurrentPanel();
			const afterUnpublish = await workflow.fetchPublications(submission.id);
			expect(afterUnpublish[0].status).toBe(STATUS_QUEUED);

			// The Vue workflow store remounts on status flips; wait for the
			// "Change" button to appear before clicking it. The widget can
			// take a moment to swap in because the side-nav reloads the
			// publication and re-renders primaryControlsLeft.
			const changeButton = controlsLeft.getByRole('button', {
				name: 'Change',
				exact: true,
			});
			await expect(changeButton).toBeVisible({timeout: 15_000});
			await expect(changeButton).toBeEnabled();

			// --- 3. Open the change-language modal and switch to fr_CA ---
			await changeButton.click();
			const langModal = page.locator('#changeSubmissionLanguage');
			await expect(langModal).toBeVisible({timeout: 10_000});

			// FieldRadioInput renders one <input type="radio" value="…"> per
			// supported locale. publicknowledge ships ['en', 'fr_CA'] so the
			// fr_CA radio is always present.
			const frRadio = langModal.locator('input[type=radio][value="fr_CA"]');
			await expect(frRadio).toBeVisible({timeout: 10_000});
			await frRadio.check();

			// The `metadata` group is gated behind a `showWhen` on the locale
			// field — once we pick fr_CA, the title + abstract TinyMCE
			// editors mount. Wait for them to be ready, then seed required
			// values for the new locale.
			await setTinyMceContent(
				page,
				'changeSubmissionLanguageMetadata-title-control',
				frenchTitle,
			);
			await setTinyMceContent(
				page,
				'changeSubmissionLanguageMetadata-abstract-control',
				frenchAbstract,
			);

			// Form.vue tunnels PUT via POST + X-Http-Method-Override, so
			// match on URL alone. The Vue store also calls
			// window.location.reload() on success, which means we must NOT
			// wait on UI text after the response — Playwright's auto-wait
			// would race the navigation. Wait for the response and the
			// subsequent navigation in lock-step.
			const changeLocaleUrl = new RegExp(
				`/submissions/${submission.id}/publications/${publicationId}/changeLocale(?:\\?|$)`,
			);
			const [resp] = await Promise.all([
				page.waitForResponse(
					(r) => changeLocaleUrl.test(r.url()) && r.status() === 200,
					{timeout: 30_000},
				),
				langModal.getByRole('button', {name: 'Confirm', exact: true}).click(),
			]);
			expect(resp.ok()).toBe(true);

			// API confirms the new locale persisted on the submission.
			const submissionAfter = await workflow.fetchSubmission(submission.id);
			expect(submissionAfter.locale).toBe('fr_CA');

			// --- 4. Republish the publication ---
			//
			// The store's success handler called window.location.reload();
			// the page is already on the workflow URL (with whatever
			// workflowMenuKey was active before the modal opened). Don't
			// re-goto — that fights the page's localStorage-driven auto
			// redirect and Playwright reports the goto as interrupted.
			// Wait for the page to settle, then open the panel directly.
			await page
				.locator('.pkpSpinner')
				.first()
				.waitFor({state: 'detached', timeout: 15_000})
				.catch(() => {});
			await workflow.openPublicationPanel('Title & Abstract');
			await workflow.publishCurrentPanel();
			const afterRepublish = await workflow.fetchPublications(submission.id);
			expect(afterRepublish).toHaveLength(1);
			expect(afterRepublish[0].status).toBe(STATUS_PUBLISHED);
			expect(afterRepublish[0].locale).toBe('fr_CA');

			// --- 5. Reader: anonymous browser sees the French title when
			//       visiting the article page through the fr_CA URL.
			//
			// OJS multilingual journals keep the reader locale in the URL
			// prefix (`/en/article/...` vs `/fr_CA/article/...`) — the
			// article template calls `getLocalizedTitle()` against the
			// request locale. The English URL would still render the en
			// title (which is the publication's "fallback" locale,
			// preserved by changeLocale) so we route through fr_CA to
			// prove the new primary locale's content reaches the reader.
			await expectArticleRendered({
				browser,
				baseURL,
				submissionId: submission.id,
				expectedTitleFragment: frenchTitle,
				readerLocale: 'fr_CA',
			});
		},
	);
});

// Publication status ints — see lib/pkp/classes/submission/PKPSubmission.php.
const STATUS_QUEUED = 1;
const STATUS_PUBLISHED = 3;

/**
 * Anonymous GET of the public article page; asserts 200 and that the
 * expected title fragment renders.
 *
 * @param {{
 *   browser: import('@playwright/test').Browser,
 *   baseURL?: string,
 *   submissionId: number,
 *   expectedTitleFragment: string,
 *   readerLocale?: string,
 * }} opts
 */
async function expectArticleRendered({
	browser,
	baseURL,
	submissionId,
	expectedTitleFragment,
	readerLocale,
}) {
	const ctx = await browser.newContext({baseURL});	const page = await ctx.newPage();
	const localePrefix = readerLocale ? `/${readerLocale}` : '';
	const resp = await page.goto(
		`/index.php/publicknowledge${localePrefix}/article/view/${submissionId}`,
	);
	expect(resp?.status()).toBe(200);
	await expect(page.locator('h1').first()).toContainText(
		expectedTitleFragment,
	);

}

/**
 * Build a tag scoped to this worker + test title so parallel workers
 * don't collide on the shared submissions list.
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
