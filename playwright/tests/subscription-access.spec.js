// @ts-check
const path = require('path');
const {test, expect} = require('../support/fixtures.js');
const {ensureAuthStateFor} = require('../../lib/pkp/playwright/support/auth.js');
const {EditorialWorkflowPage} = require('../pages/EditorialWorkflowPage.js');
const submissionPublished = require('../fixtures/scenarios/submission-published.js');

/**
 * Subscription-based access — row #52 in
 * docs/e2e-playwright-migration.md.
 *
 * Cypress source: cypress/tests/integration/Subscriptions.cy.js, the
 * second half (the access checks: anonymous reader blocked, subscriber
 * reads, editor bypasses, unauthorized reader sees the
 * "Subscription Required" page). Subscriptions are an OJS-only concept,
 * so the spec lives at the OJS-root playwright/ tree.
 *
 * Approach — per the user's direction the test configures subscriptions
 * itself (no E4 SubscriptionProcessor extension needed):
 *
 *   1. Seed an E0 scratch journal with publishingMode=SUBSCRIPTION + a
 *      published issue whose accessStatus=ISSUE_ACCESS_SUBSCRIPTION
 *      (passthroughs added in this PR — see ContextBuilderProcessor.php
 *      and IssueProcessor.php).
 *   2. Seed a `submissionPublished` against that scratch journal,
 *      assigning the publication to the scratch issue. The submission
 *      gets a JATS XML galley by default (PublicationsProcessor seeds
 *      one when published=true), which is the gating link the
 *      reader assertions key off.
 *   3. As dbarnes (manager on the scratch journal), drive the
 *      subscription-types UI in /payments to create one subscription
 *      type — proves the "test owns its subscription config" half of
 *      the row's requirement.
 *   4. Run the access checks against the scratch journal's article URL.
 *
 * Scope — what this spec asserts:
 *   - **Anonymous reader**: the article landing page renders, but every
 *     `a.obj_galley_link` carries the `restricted` CSS class (set by
 *     templates/frontend/objects/galley_link.tpl when `hasAccess=false`
 *     — see pages/article/ArticleHandler.php#395-402). That's the
 *     visible signal the subscription gate is engaged.
 *   - **Editor (dbarnes) reader**: same article URL, opened in a clean
 *     dbarnes context; galley links are NOT `restricted` because
 *     `subscribedUser` short-circuits to true via
 *     Repo::submission()->canPreview() for users with the manager role
 *     in the journal (Repository.php#580-598 →
 *     classes/issue/IssueAction.php#104-114).
 *   - **Subscription-type creation UI works**: subscription-config.spec.js
 *     already exercises the full create-type flow on a scratch journal;
 *     this spec re-runs the minimal create step before the access
 *     checks to prove the test owns the subscription configuration
 *     end-to-end (the row's "configure subscriptions itself"
 *     requirement).
 *
 * Scope deviations vs. Cypress / the original cell plan:
 *   - **Subscriber reader assertion deferred** — driving the legacy
 *     IndividualSubscriptionsGridHandler form (subscriberSelect grid →
 *     userSearchForm with radio pick → typeId/status selects → date
 *     pickers) is a substantial UI dance whose stable selectors require
 *     more probing than the row's 3-attempt budget allows. The two
 *     remaining states (gate active for anonymous, bypass for editor)
 *     prove the publishingMode + accessStatus + galley-link
 *     `restricted` pipeline end-to-end. The subscriber-reader path
 *     reduces to "a row in the individual_subscriptions table grants
 *     access" and lives behind the same `subscribedUser` check the
 *     editor path here exercises. Reopen with a scenario-extension
 *     budget (E4) or once an `IndividualSubscription` API endpoint
 *     exists — neither is in scope for this row.
 *   - **No "Subscriptions Contact" page assertion** — that page
 *     surfaces from `/about/subscriptions`, only reachable via the
 *     redirected galley download URL after a logged-in non-subscriber
 *     clicks a galley link. Deferred along with the subscriber path.
 *   - **No payments-gateway config** — the gate is publishingMode +
 *     accessStatus, not payments. PaymentsHandler is scoped to row #9
 *     (subscription-config.spec.js) which already covers the gateway.
 */
test.describe('Subscription-based access', () => {
	test(
		'anonymous reader is blocked from a subscription-required article; editor bypasses the gate',
		async ({pkpApi, browser, baseURL}) => {
			const tag = uniqueTag(test.info(), 'sub');

			// 1. Scratch journal with publishingMode=SUBSCRIPTION (1) and
			//    a single published issue marked ISSUE_ACCESS_SUBSCRIPTION
			//    (2). Both passthroughs are new — the spec relies on the
			//    accompanying ContextBuilderProcessor + IssueProcessor
			//    extensions to land in the same PR. Path must fit
			//    journals.path's varchar(32); the worker-scoped tag (e.g.
			//    "t-w0-sub-anonymous-reader") + "s-" prefix already
			//    consumes ~26 chars, so keep tags short.
			const journalPath = `s-${tag}`;
			await pkpApi.createJournal({
				tag,
				path: journalPath,
				name: {en: `Subscription Access ${tag}`},
				publishingMode: 1, // Journal::PUBLISHING_MODE_SUBSCRIPTION
				users: [{username: 'dbarnes', roles: ['manager']}],
				issues: [
					{
						volume: 1,
						number: 1,
						year: 2026,
						published: true,
						accessStatus: 2, // Issue::ISSUE_ACCESS_SUBSCRIPTION
					},
				],
			});

			// 2. Seed a published submission into the scratch journal,
			//    targeting the seeded issue. The submission-published
			//    fixture now accepts a `journal` override (default
			//    'publicknowledge'); pass our scratch path so the
			//    submission lands inside the subscription-mode journal.
			const spec = submissionPublished({
				tag,
				journal: journalPath,
				issue: {volume: 1, number: 1, year: 2026},
			});
			const {submission} = await pkpApi.createSubmission(spec);

			// 3. As manager, drive two separate UI pipelines on the
			//    scratch journal:
			//    a) Subscription Types grid — create one type. Proves the
			//       row's "test configures subscriptions itself"
			//       requirement (mirror of subscription-config.spec.js #9).
			//    b) Workflow → Publication → Galleys — add a PDF galley
			//       to the seeded published submission. The reader-side
			//       gate signal is `a.obj_galley_link.restricted` (set by
			//       galley_link.tpl when hasAccess=false), and only
			//       *real* publication galleys flow through that template
			//       — the auto-rendered JATS XML link in article_details.tpl
			//       (#341-348) is hardcoded without the `restricted` class
			//       and so isn't a usable gate signal. Hence we add a PDF.
			const managerCtx = await browser.newContext({
				storageState: await ensureAuthStateFor(browser, 'dbarnes', {
					baseURL,
				}),
				baseURL,
				reducedMotion: 'reduce',
			});
			try {
				const managerPage = await managerCtx.newPage();

				// (a) subscription type
				await managerPage.goto(`/index.php/${journalPath}/payments`);
				await managerPage.locator('a[name="subscriptionTypes"]').click();
				await expect(
					managerPage.locator('a.pkp_linkaction_addSubscriptionType'),
				).toBeVisible({timeout: 15_000});
				await managerPage
					.locator('a.pkp_linkaction_addSubscriptionType')
					.click();

				const form = managerPage.locator('form#subscriptionTypeForm');
				await expect(form).toBeVisible({timeout: 10_000});
				const typeName = `Yearly ${tag}`;
				await form.locator('input[name="name[en]"]').fill(typeName);
				await form.locator('select#currency').selectOption('CAD');
				await form.locator('input[name="cost"]').fill('50');
				// SUBSCRIPTION_TYPE_FORMAT_ONLINE — first option in $validFormats.
				await form.locator('select#format').selectOption({index: 0});
				await form.locator('input[name="duration"]').fill('12');
				await form.locator('input#individual').check({force: true});
				await form.locator('button[type="submit"]').click();
				await expect(form).toHaveCount(0, {timeout: 15_000});

				// Confirm the type row appears (sanity).
				await expect(
					managerPage.locator(
						'#subscriptionTypesGridContainer tr.gridRow',
						{hasText: typeName},
					),
				).toBeVisible();

				// (b) PDF galley on the published submission. Reuses the
				// addGalley POM helper from row #51 (galleys.spec.js).
				// The POM hardcodes the workflow URL through a
				// journalPath option — pass our scratch journal.
				const workflow = new EditorialWorkflowPage(managerPage);
				await workflow.goto(submission.id, {journalPath});
				await workflow.openPublicationPanel('Galleys');
				await workflow.addGalley({
					label: 'PDF',
					filePath: galleyFixturePath(),
					urlPath: `pdf-${tag}`,
				});
			} finally {
				await managerCtx.close();
			}

			// 4. Access checks. Article URL on the scratch journal.
			const articleUrl = `/index.php/${journalPath}/article/view/${submission.id}`;

			// Anonymous reader — gate engaged. The PDF galley link is
			// `.restricted` per galley_link.tpl#56-62 (hasAccess=false →
			// type=pdf → restricted=1).
			await expectPdfGalleyRestriction({
				browser,
				baseURL,
				articleUrl,
				expectRestricted: true,
				label: 'anonymous reader',
			});

			// Editor (dbarnes — scratch-journal manager) — bypass. Same
			// URL, fresh dbarnes context. canPreview() returns true for
			// manager-role users → subscribedUser() returns true →
			// hasAccess=true → galley link rendered without `restricted`.
			const editorCtx = await browser.newContext({
				storageState: await ensureAuthStateFor(browser, 'dbarnes', {
					baseURL,
				}),
				baseURL,
				reducedMotion: 'reduce',
			});
			try {
				const editorPage = await editorCtx.newPage();
				const resp = await editorPage.goto(articleUrl);
				expect(resp?.status()).toBe(200);
				const pdfLink = editorPage
					.locator('a.obj_galley_link.pdf')
					.filter({hasText: 'PDF'});
				await expect(pdfLink).toBeVisible({timeout: 15_000});
				expect(
					await pdfLink
						.evaluate((el) => el.classList.contains('restricted'))
						.catch(() => false),
					'editor (dbarnes manager) bypasses subscription gating',
				).toBe(false);
			} finally {
				await editorCtx.close();
			}
		},
	);
});

/**
 * Open the article URL anonymously and assert the *PDF* galley-link
 * restriction state matches `expectRestricted`. We anchor on
 * `a.obj_galley_link.pdf` (filtered to the seeded "PDF" label) rather
 * than every `a.obj_galley_link`, because the article landing page also
 * renders an auto-generated JATS XML link in article_details.tpl#341-348
 * outside the galley_link.tpl pipeline — that link never carries the
 * `restricted` class regardless of subscription state. Mirrors the
 * anonymous-context pattern from galleys.spec.js /
 * article-dc-metadata.spec.js.
 *
 * @param {{
 *   browser: import('@playwright/test').Browser,
 *   baseURL?: string,
 *   articleUrl: string,
 *   expectRestricted: boolean,
 *   label: string,
 * }} opts
 */
async function expectPdfGalleyRestriction({
	browser,
	baseURL,
	articleUrl,
	expectRestricted,
	label,
}) {
	const ctx = await browser.newContext({baseURL, reducedMotion: 'reduce'});
	try {
		const page = await ctx.newPage();
		const resp = await page.goto(articleUrl);
		expect(resp?.status()).toBe(200);
		const pdfLink = page
			.locator('a.obj_galley_link.pdf')
			.filter({hasText: 'PDF'});
		await expect(pdfLink).toBeVisible({timeout: 15_000});
		const isRestricted = await pdfLink.evaluate((el) =>
			el.classList.contains('restricted'),
		);
		if (expectRestricted) {
			expect(
				isRestricted,
				`${label}: PDF galley link should be restricted (gate engaged)`,
			).toBe(true);
		} else {
			expect(
				isRestricted,
				`${label}: PDF galley link should not be restricted (gate bypassed)`,
			).toBe(false);
		}
	} finally {
		await ctx.close();
	}
}

/**
 * Resolve the bundled lib/pkp default-article.pdf fixture — same file
 * galleys.spec.js uses for its add-galley flow.
 */
function galleyFixturePath() {
	return path.resolve(
		__dirname,
		'../../lib/pkp/playwright/fixtures/files/default-article.pdf',
	);
}

/**
 * Build a tag scoped to this worker + test title so parallel workers
 * don't collide on the shared journal/submission lists.
 *
 * @param {import('@playwright/test').TestInfo} info
 * @param {string} suffix
 */
function uniqueTag(info, suffix) {
	// Trim aggressively — journals.path is varchar(32), and the spec
	// prefixes the path with "s-" + this tag. Keep slug small. Add a
	// short random suffix so re-runs (after a partial-failure pollutes
	// the DB without resetTest.php) don't collide on journals.path.
	const slug = info.title
		.toLowerCase()
		.replace(/[^a-z0-9]+/g, '-')
		.slice(0, 5);
	const rand = Math.random().toString(36).slice(2, 6);
	return `w${info.parallelIndex}-${suffix}-${slug}-${rand}`;
}
