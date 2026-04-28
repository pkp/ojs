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
 * Bootstrap caveat — the publicknowledge bootstrap declares
 * `supportedLocales: ['en', 'fr_CA']` but JournalProcessor doesn't pass
 * that through to `supportedSubmissionMetadataLocales`, and
 * PKPContextService::add() then defaults it to `['en']`. The
 * `/changeLocale` validator intersects this setting with
 * `Submission::getPublicationLanguages()` — so a naive run yields 400
 * with `{title: {fr_CA: ["This language is not accepted."]}}`. The
 * original Cypress suite paid this cost in 20-CreateContext.cy.js,
 * which clicked through the Languages settings grid to enable fr_CA as
 * a submission/metadata locale. We replicate that in
 * `withSubmissionMetadataLocale()` below: a journal-config touch via
 * PUT /api/v1/contexts/{id} that adds the locale, runs the test body,
 * and reverts in a finally block. Reverting matters because the same
 * setting drives the wizard's per-locale review panel (see
 * `lib/pkp/playwright/tests/wizard-comments-become-discussion.spec.js`,
 * which fails strict-mode if it sees fr_CA in metadata locales).
 * Promoting the helper into the shared bootstrap would need a lib/pkp
 * change (out of scope per submodule discipline). This is the only
 * journal-level mutation in this spec; everything else is per-submission.
 */
// Serial mode — the change-locale flow needs publicknowledge's
// `supportedSubmissionMetadataLocales` extended (then reverted) for the
// duration of the test, so two parallel runs would clobber each other's
// revert and the second worker's `changeLocale` PUT would fail validation.
// Within-file serialization isn't enough for the cross-file race against
// `wizard-comments-become-discussion` (which assumes a single-locale
// metadata setting); the cross-file window is bounded by the
// add+revert pair, but flakes are possible. Reopen if needed.
test.describe.configure({mode: 'serial'});

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

			// Resolve the journal's numeric id by fetching the submission
			// payload (it carries `contextId`). Used by the locale-bump
			// helper below.
			const fullSubmission = await workflow.fetchSubmission(submission.id);

			// Wrap the body in a locale-add/revert pair so the rest of
			// the suite sees the journal in its original state. See
			// `withSubmissionMetadataLocale` for why the wrapper exists.
			await withSubmissionMetadataLocale({
				asUser,
				locale: 'fr_CA',
				contextId: fullSubmission.contextId,
				fn: async () => {
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
			});
		},
	);
});

// Publication status ints — see lib/pkp/classes/submission/PKPSubmission.php.
const STATUS_QUEUED = 1;
const STATUS_PUBLISHED = 3;

/**
 * Add `locale` to publicknowledge's `supportedSubmissionMetadataLocales`,
 * run `fn`, then revert the array to its prior value.
 *
 * Uses the dbarnes (manager) context so the PUT carries a valid session
 * + CSRF token. We deliberately don't reach into the bootstrap because
 * `JournalProcessor` doesn't pass `supportedSubmissionMetadataLocales`
 * through the spec, and bumping the bootstrap would require a lib/pkp
 * change (out of scope per the migration's submodule discipline).
 *
 * @param {{
 *   asUser: (user: string) => Promise<import('@playwright/test').BrowserContext>,
 *   locale: string,
 *   contextId: number,
 *   fn: () => Promise<void>,
 * }} args
 */
async function withSubmissionMetadataLocale({asUser, locale, contextId, fn}) {
	// Add `locale` to the journal's supportedSubmissionMetadataLocales,
	// run `fn`, then revert the array to its prior value. The metadata
	// locales list is what the publication validator intersects against
	// the change-locale request body (see
	// PKPSubmissionController::changeLocale → Repo::publication()->
	// validate, which calls
	// `Submission::getPublicationLanguages($context->
	// getSupportedSubmissionMetadataLocales())`); without fr_CA in there
	// the validator rejects the new title/abstract with "This language
	// is not accepted." But the same setting also drives the wizard's
	// per-locale review panel (PKPSubmissionHandler::getSteps reads it
	// into `$locales` for review-details.tpl), so leaving fr_CA enabled
	// after the test would cause `wizard-comments-become-discussion`
	// to see two "For the Editors" panels and fail strict-mode on its
	// heading locator. Do the touch inside a finally-revert wrapper.
	const ctx = await asUser('dbarnes');
	let priorMetadataLocales = null;
	try {
		const page = await ctx.newPage();
		try {
			await page.goto('/index.php/publicknowledge/dashboard/editorial');
			const csrfToken = await page.evaluate(
				() => window.pkp?.currentUser?.csrfToken,
			);
			if (!csrfToken) {
				throw new Error(
					'withSubmissionMetadataLocale: csrfToken not available on dashboard',
				);
			}

			// Fetch the full context payload (the path-scoped GET returns
			// the locale arrays we need). The site-wide /index/api/v1/contexts
			// list endpoint requires site-admin, so we use the path-scoped
			// route instead.
			const fullResp = await ctx.request.get(
				`/index.php/publicknowledge/api/v1/contexts/${contextId}`,
			);
			if (!fullResp.ok()) {
				throw new Error(
					`GET context ${contextId} failed: ${fullResp.status()} ${await fullResp.text()}`,
				);
			}
			const fullCtx = await fullResp.json();
			priorMetadataLocales =
				fullCtx.supportedSubmissionMetadataLocales || [];

			if (!priorMetadataLocales.includes(locale)) {
				const resp = await ctx.request.fetch(
					`/index.php/publicknowledge/api/v1/contexts/${contextId}`,
					{
						method: 'PUT',
						headers: {
							'X-Csrf-Token': csrfToken,
							'Content-Type': 'application/json',
						},
						data: {
							supportedSubmissionMetadataLocales: [
								...priorMetadataLocales,
								locale,
							],
						},
					},
				);
				if (!resp.ok()) {
					throw new Error(
						`PUT context (add locale) failed: ${resp.status()} ${await resp.text()}`,
					);
				}
			} else {
				// Locale already present — nothing to revert at the end.
				priorMetadataLocales = null;
			}

			try {
				await fn();
			} finally {
				if (priorMetadataLocales !== null) {
					// Best-effort revert. If this fails the suite is left in
					// a state where `wizard-comments-become-discussion`
					// would see two "For the Editors" panels — surface the
					// failure rather than silently swallowing it.
					const revertResp = await ctx.request.fetch(
						`/index.php/publicknowledge/api/v1/contexts/${contextId}`,
						{
							method: 'PUT',
							headers: {
								'X-Csrf-Token': csrfToken,
								'Content-Type': 'application/json',
							},
							data: {
								supportedSubmissionMetadataLocales:
									priorMetadataLocales,
							},
						},
					);
					if (!revertResp.ok()) {
						throw new Error(
							`PUT context (revert locale) failed: ${revertResp.status()} ${await revertResp.text()}`,
						);
					}
				}
			}
		} finally {
			await page.close();
		}
	} finally {
		await ctx.close();
	}
}

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
	const ctx = await browser.newContext({baseURL, reducedMotion: 'reduce'});
	try {
		const page = await ctx.newPage();
		const localePrefix = readerLocale ? `/${readerLocale}` : '';
		const resp = await page.goto(
			`/index.php/publicknowledge${localePrefix}/article/view/${submissionId}`,
		);
		expect(resp?.status()).toBe(200);
		await expect(page.locator('h1').first()).toContainText(
			expectedTitleFragment,
		);
	} finally {
		await ctx.close();
	}
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
