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
 *   - **Subscriber reader arm graduated** — a third test now drives the
 *     legacy IndividualSubscriptionsGridHandler "Create New
 *     Subscription" AjaxModal end-to-end on the scratch journal: pick
 *     the seeded reader user from the embedded SubscriberSelect grid,
 *     fill `typeId` / `status` / `dateStart` / `dateEnd`, save, then
 *     re-open the article URL anonymously with the reader's
 *     storageState and assert the PDF galley link is no longer
 *     `restricted`. Uses `phudson` as the reader — seeded as a reviewer
 *     on publicknowledge, but enrolled into NO role on the scratch
 *     journal, so on the scratch journal phudson behaves as a plain
 *     reader (no manager/editor bypass via canPreview).
 *   - **No "Subscriptions Contact" page assertion** — that page
 *     surfaces from `/about/subscriptions`, only reachable via the
 *     redirected galley download URL after a logged-in non-subscriber
 *     clicks a galley link. Lives in the unauthorised-reader arm of
 *     the Cypress source which we don't port (covered indirectly by
 *     the `restricted` class assertion on the anonymous arm).
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

	test(
		'subscriber reader sees the full article body',
		async ({pkpApi, browser, baseURL}) => {
			// Mirrors the setup of the gate-engaged test: scratch journal
			// in subscription mode, published issue marked
			// ISSUE_ACCESS_SUBSCRIPTION, published submission, PDF galley.
			// Then drives the legacy IndividualSubscriptionsGridHandler
			// AjaxModal to grant phudson a subscription, and asserts that
			// phudson's article view shows the PDF galley link without
			// `restricted`.
			//
			// phudson is the reader user. He's seeded as a reviewer on
			// publicknowledge; here we enroll him into the scratch
			// journal's "Reader" role so he surfaces in
			// SubscriberSelectGridHandler's user list (which filters
			// `Repo::user()->getCollector()->filterByContextIds(...)` —
			// users without any user_user_groups row in the target
			// journal don't appear in the grid). The Reader role has no
			// editorial privileges, so `canPreview()` still returns false
			// for phudson on this journal — only the
			// individual_subscriptions row this test creates can grant
			// him access via `subscribedUser()`.
			const tag = uniqueTag(test.info(), 'sub');
			const journalPath = `s-${tag}`;
			await pkpApi.createJournal({
				tag,
				path: journalPath,
				name: {en: `Subscription Subscriber ${tag}`},
				publishingMode: 1, // PUBLISHING_MODE_SUBSCRIPTION
				users: [
					{username: 'dbarnes', roles: ['manager']},
					{username: 'phudson', roles: ['reader']},
				],
				issues: [
					{
						volume: 1,
						number: 1,
						year: 2026,
						published: true,
						accessStatus: 2, // ISSUE_ACCESS_SUBSCRIPTION
					},
				],
			});

			const spec = submissionPublished({
				tag,
				journal: journalPath,
				issue: {volume: 1, number: 1, year: 2026},
			});
			const {submission} = await pkpApi.createSubmission(spec);

			const subscriptionTypeName = `Yearly ${tag}`;

			const managerCtx = await browser.newContext({
				storageState: await ensureAuthStateFor(browser, 'dbarnes', {
					baseURL,
				}),
				baseURL,
				reducedMotion: 'reduce',
			});
			try {
				const managerPage = await managerCtx.newPage();

				// (a) Create a subscription type so the
				//     IndividualSubscriptionForm has a non-empty `typeId`
				//     select. Same flow as the gate-engaged test.
				await managerPage.goto(`/index.php/${journalPath}/payments`);
				await managerPage.locator('a[name="subscriptionTypes"]').click();
				await expect(
					managerPage.locator('a.pkp_linkaction_addSubscriptionType'),
				).toBeVisible({timeout: 15_000});
				await managerPage
					.locator('a.pkp_linkaction_addSubscriptionType')
					.click();
				const typeForm = managerPage.locator('form#subscriptionTypeForm');
				await expect(typeForm).toBeVisible({timeout: 10_000});
				await typeForm
					.locator('input[name="name[en]"]')
					.fill(subscriptionTypeName);
				await typeForm.locator('select#currency').selectOption('CAD');
				await typeForm.locator('input[name="cost"]').fill('50');
				await typeForm
					.locator('select#format')
					.selectOption({index: 0});
				await typeForm.locator('input[name="duration"]').fill('12');
				await typeForm.locator('input#individual').check({force: true});
				await typeForm.locator('button[type="submit"]').click();
				await expect(typeForm).toHaveCount(0, {timeout: 15_000});
				// Sanity — the new type row is in the grid; the
				// IndividualSubscriptionForm reads the same
				// SubscriptionTypeDAO::getByInstitutional() to populate
				// its `typeId` select, so this guarantees the modal will
				// render a non-empty option set.
				await expect(
					managerPage.locator(
						'#subscriptionTypesGridContainer tr.gridRow',
						{hasText: subscriptionTypeName},
					),
				).toBeVisible();

				// (b) PDF galley on the published submission so the
				//     reader-side gate signal lives on a real publication
				//     galley (the auto-rendered JATS XML link in
				//     article_details.tpl#341-348 is hardcoded outside
				//     galley_link.tpl and never carries the `restricted`
				//     class regardless of subscription state).
				const workflow = new EditorialWorkflowPage(managerPage);
				await workflow.goto(submission.id, {journalPath});
				await workflow.openPublicationPanel('Galleys');
				await workflow.addGalley({
					label: 'PDF',
					filePath: galleyFixturePath(),
					urlPath: `pdf-${tag}`,
				});

				// (c) Drive the IndividualSubscriptionsGridHandler "Create
				//     New Subscription" AjaxModal. The grid lives at
				//     /payments → "Individual Subscriptions" tab.
				await managerPage.goto(`/index.php/${journalPath}/payments`);
				await managerPage
					.locator('a[name="individualSubscription"]')
					.click();
				// "Create New Subscription" is a top-level link action
				// above the grid. PKPHandler#setId chops the trailing
				// "Handler" off the component id and lowercases the rest
				// (PKPHandler.php#427), so the grid's stable DOM id is
				// `grid-subscriptions-individualsubscriptionsgrid` and
				// the button id starts with
				// `component-grid-subscriptions-individualsubscriptionsgrid-addSubscription-button-`.
				const addAction = managerPage.locator(
					'a[id^="component-grid-subscriptions-individualsubscriptionsgrid-addSubscription-button-"]',
				);
				await expect(addAction).toBeVisible({timeout: 15_000});
				await addAction.click();

				// AjaxModal opens the IndividualSubscriptionForm
				// (templates/payments/individualSubscriptionForm.tpl) into
				// a jQuery-UI dialog. Form id =
				// individualSubscriptionForm.
				const subForm = managerPage.locator(
					'form#individualSubscriptionForm',
				);
				await expect(subForm).toBeVisible({timeout: 10_000});

				// User pick — the form embeds a SubscriberSelectGrid via
				// load_url_in_div. Search for phudson by family name to
				// narrow the result list, then click the row's radio.
				// `userSearchForm` is the grid's filter form (action
				// component=grid.users.subscriberSelect.SubscriberSelectGridHandler
				// op=fetchGrid). The grid renders rows with a radio
				// `input[name=userId]` per
				// userSelectRadioButton.tpl.
				const userSearch = managerPage.locator('form#userSearchForm');
				await expect(userSearch).toBeVisible({timeout: 10_000});
				await userSearch.locator('input[name="search"]').fill('Hudson');
				await userSearch
					.getByRole('button', {name: 'Search', exact: true})
					.click();
				const phudsonRow = managerPage.locator(
					'#subscriberSelectGridContainer tr.gridRow',
					{hasText: 'Paul Hudson'},
				);
				await expect(phudsonRow).toBeVisible({timeout: 15_000});
				await phudsonRow
					.locator('input[type="radio"][name="userId"]')
					.check({force: true});

				// Subscription type, status, date range. The
				// `dateStart` / `dateEnd` fbvElement renders id="dateStart"
				// / id="dateEnd" on the form even with the
				// `class="datepicker"` decoration; the picker overlays a
				// hidden input but the underlying text field still
				// accepts a value via fill.
				// SubscriptionType::getSummaryString() renders the option
				// label as e.g. "Yearly w0-sub-subsc-r724 - 1 year - 50.00 CAD";
				// pick by index 0 — the manager just created exactly one
				// type on this scratch journal, so the first non-empty
				// option is unambiguous.
				const typeSelect = subForm.locator('select[name="typeId"]');
				const typeValue = await typeSelect
					.locator('option:not([value=""])')
					.first()
					.getAttribute('value');
				if (!typeValue) {
					throw new Error('typeId select is empty — type creation step failed');
				}
				await typeSelect.selectOption(typeValue);
				await subForm
					.locator('select[name="status"]')
					.selectOption({label: 'Active'});
				const today = new Date();
				const oneYearOut = new Date(today);
				oneYearOut.setFullYear(today.getFullYear() + 1);
				// templates/form/textInput.tpl#59-77 renders datepicker
				// fields as TWO inputs sharing the same name: a visible
				// `<input type="text">` whose user-friendly format is
				// driven by `dateFormatShort` (config has "Y-m-d" — the
				// same canonical format), and a hidden alt-field also
				// named `dateStart`. jQuery UI datepicker keeps them in
				// sync as the user types in the visible input.
				//
				// On submit, AjaxFormHandler does `$form.serialize()`
				// which emits BOTH name=value pairs to PHP; since they
				// share a key, PHP's `$_POST['dateStart']` ends up as
				// the LAST one (the alt-field). So both fields must
				// carry the right value, OR at minimum the alt-field
				// must, but the visible field's `change` is what nudges
				// the alt-field via the picker. Easiest robust path:
				// drive both via JS and fire change, so listeners and
				// serializers all see canonical values.
				// jQuery UI datepicker renames the visible input to
				// `name=dateStart-removed` on init (and exposes the
				// canonical hidden alt-field under `name=dateStart`).
				// Filling the visible input fires the picker's `onSelect`
				// only if the picker recognises the value as a real date
				// in its expected format. Simpler and more reliable:
				// drive both inputs directly via JS — set the visible
				// `dateStart-removed` text input and the hidden
				// `dateStart` alt-field to the same canonical Y-m-d
				// value, then dispatch a `change` so any client-side
				// validators clear their error state. The serializer
				// reads the hidden input (it's the only one the form
				// knows as `dateStart`) and PHP gets the right value.
				await managerPage.evaluate(
					({startVal, endVal}) => {
						const setByName = (name, value) => {
							const inputs = document.querySelectorAll(
								`form#individualSubscriptionForm input[name="${name}"]`,
							);
							inputs.forEach((el) => {
								/** @type {HTMLInputElement} */ (el).value = value;
								el.dispatchEvent(
									new Event('change', {bubbles: true}),
								);
							});
							return inputs.length;
						};
						setByName('dateStart-removed', startVal);
						setByName('dateStart', startVal);
						setByName('dateEnd-removed', endVal);
						setByName('dateEnd', endVal);
					},
					{
						startVal: formatDate(today),
						endVal: formatDate(oneYearOut),
					},
				);
				// Notify checkbox — leave unchecked (default). The form's
				// "Notify Email" check fires a SubscriptionNotify mailable
				// that the subscriptionEmail/Name policy fields gate; we
				// don't seed those on the scratch journal so leaving the
				// box unchecked keeps the save fast.

				await subForm
					.getByRole('button', {name: 'Save', exact: true})
					.click();
				// AjaxFormHandler closes the dialog on a successful save
				// (DataChangedEvent). On a validation failure it
				// re-renders the form inline; if that happens this
				// assert times out and the failure surfaces clearly.
				await expect(subForm).toHaveCount(0, {timeout: 15_000});

				// Sanity — the subscription row appears in the grid.
				// `IndividualSubscriptionsGridContainer` is the legacy
				// jQuery grid div the SubscriptionsGridCellProvider
				// hydrates with the subscriber's name in the `name`
				// column.
				await expect(
					managerPage.locator(
						'#individualSubscriptionsGridContainer tr.gridRow',
						{hasText: 'Paul Hudson'},
					),
				).toBeVisible({timeout: 15_000});
			} finally {
				await managerCtx.close();
			}

			// Now, as phudson, verify the article reads as fully
			// accessible — PDF galley link is NOT `.restricted`, and
			// the article body paragraphs render. ensureAuthStateFor
			// logs phudson in via publicknowledge's login form (his
			// `journal` field) and persists the cookies; the resulting
			// session is journal-agnostic for read access on any
			// journal in the installation.
			const articleUrl = `/index.php/${journalPath}/article/view/${submission.id}`;
			const readerCtx = await browser.newContext({
				storageState: await ensureAuthStateFor(browser, 'phudson', {
					baseURL,
				}),
				baseURL,
				reducedMotion: 'reduce',
			});
			try {
				const readerPage = await readerCtx.newPage();
				const resp = await readerPage.goto(articleUrl);
				expect(resp?.status()).toBe(200);
				const pdfLink = readerPage
					.locator('a.obj_galley_link.pdf')
					.filter({hasText: 'PDF'});
				await expect(pdfLink).toBeVisible({timeout: 15_000});
				expect(
					await pdfLink
						.evaluate((el) => el.classList.contains('restricted'))
						.catch(() => false),
					'subscriber (phudson) bypasses subscription gating',
				).toBe(false);
				// Article body — the published submission's abstract
				// from submissionPublished is "<p>A fully-processed,
				// published article in scenario form.</p>". The article
				// landing page renders that abstract inside
				// `.item.abstract` on the bootstrap default theme.
				// Anchor on the abstract's stable text fragment to
				// confirm the reader sees the non-galley body content
				// too.
				await expect(
					readerPage.getByText(
						'A fully-processed, published article in scenario form.',
					),
				).toBeVisible({timeout: 10_000});
			} finally {
				await readerCtx.close();
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

/**
 * Escape a string for safe inclusion in a RegExp literal.
 *
 * @param {string} s
 */
function escapeRegex(s) {
	return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Format a Date as `YYYY-MM-DD` for the IndividualSubscriptionForm
 * datepicker fields. SubscriptionForm::readInputData feeds these
 * straight into `strtotime()`, so a YYYY-MM-DD string is unambiguous
 * across locales.
 *
 * @param {Date} d
 */
function formatDate(d) {
	const y = d.getFullYear();
	const m = String(d.getMonth() + 1).padStart(2, '0');
	const day = String(d.getDate()).padStart(2, '0');
	return `${y}-${m}-${day}`;
}
