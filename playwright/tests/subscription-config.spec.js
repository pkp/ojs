// @ts-check
const {test, expect} = require('../support/fixtures.js');
const {ensureAuthStateFor} = require('../../lib/pkp/playwright/support/auth.js');
const {waitForJQueryIdle} = require('../../lib/pkp/playwright/support/jquery.js');

/**
 * Subscription types & policies — row #9 in
 * docs/e2e-playwright-migration.md.
 *
 * Ports the CONFIG half of cypress/tests/integration/Subscriptions.cy.js:
 * creating a subscription type, editing the subscription policy form,
 * deleting a subscription type. Subscriptions are an OJS-only concept,
 * so the spec lives at the OJS-root playwright/ tree.
 *
 * Scope drops:
 *   - Reader-side access tests (anonymous blocked / subscriber reads /
 *     editor reads restricted issue) belong to row #52
 *     (subscription-access.spec.js) and require extension E4
 *     (SubscriptionProcessor) to seed per-user subscriptions. Not in
 *     scope here.
 *   - The Cypress source also exercised payment-gateway configuration
 *     (PKPPaymentSettingsForm on Distribution > Payments) and
 *     configuring an individual issue for subscription — those belong
 *     with row #52 (the reader half), not here.
 *
 * Scope note on publishingMode: the payments page (/payments) is
 * reachable without flipping Distribution > Access to Subscription
 * mode — the PaymentsHandler only requires the manager role. The
 * Cypress source flipped publishingMode because the reader-side
 * assertions that followed it depended on it. Since we're dropping
 * the reader assertions, we also drop the Access-form toggle; it's a
 * separate capability.
 *
 * Each test seeds its own E0 scratch journal via pkpApi.createJournal
 * so the bootstrapped publicknowledge journal's subscription state
 * stays untouched.
 *
 * UI notes:
 *   - The subscription types grid is the legacy
 *     `pkp_controllers_linkAction` jQuery grid. Row-level Edit/Delete
 *     actions are hidden until the grid-level "Settings" toggle
 *     (`a.show_extras`) is clicked.
 *   - The SubscriptionType form's localized typeName field is
 *     `name="name[en]"` with id `typeName-<hash>` (no `-en-` infix
 *     despite what the Cypress selector claimed — the Cypress selector
 *     happened to still resolve because the form uses a different
 *     multilingual emission).
 *   - The delete confirmation renders as a reka-ui dialog with OK /
 *     Cancel buttons — not the classic jQuery UI dialog.
 */

function uniqueTag() {
	const workerIndex = test.info().parallelIndex;
	const suffix = Math.random().toString(36).slice(2, 8);
	return `sub-w${workerIndex}-${suffix}`;
}

/**
 * Open the Subscription Types tab on the payments page and wait for
 * the grid to render. The tabs are the legacy jQuery
 * `$.pkp.controllers.TabHandler` — each tab anchor has a stable
 * `name` attribute matching its PaymentsHandler op.
 */
async function openSubscriptionTypesTab(page, journalPath) {
	await page.goto(`/index.php/${journalPath}/payments`);
	await page.locator('a[name="subscriptionTypes"]').click();
	await waitForJQueryIdle(page);
	await expect(
		page.locator('a.pkp_linkaction_addSubscriptionType'),
	).toBeVisible();
}

/**
 * Open the Subscription Policies tab and wait for the form.
 */
async function openSubscriptionPoliciesTab(page, journalPath) {
	await page.goto(`/index.php/${journalPath}/payments`);
	await page.locator('a[name="subscriptionPolicies"]').click();
	await waitForJQueryIdle(page);
	await expect(page.locator('form#subscriptionPolicies')).toBeVisible();
}

/**
 * Reveal the per-row Edit / Delete controls on the Subscription
 * Types grid. The grid renders each row as `tr.gridRow` +
 * `tr.row_controls` siblings, with the controls row hidden until the
 * grid-level "Settings" glyph (`a.show_extras`) is clicked — same
 * pattern sections.spec.js handles.
 */
async function revealRowControls(page) {
	await page
		.locator('#subscriptionTypesGridContainer a.show_extras')
		.first()
		.click();
}

/**
 * Fill the SubscriptionType form with the given values and submit.
 * Waits for the side-modal dialog to close.
 */
async function createSubscriptionType(page, {name, cost, duration}) {
	const form = page.locator('form#subscriptionTypeForm');
	await expect(form).toBeVisible();
	await form.locator('input[name="name[en]"]').fill(name);
	await form.locator('select#currency').selectOption('CAD');
	await form.locator('input[name="cost"]').fill(cost);
	// SUBSCRIPTION_TYPE_FORMAT_ONLINE is the first option in
	// $validFormats. Online / Print / PrintOnline — index 0 is Online.
	await form.locator('select#format').selectOption({index: 0});
	await form.locator('input[name="duration"]').fill(duration);
	await form.locator('input#individual').check({force: true});
	await form.locator('button[type="submit"]').click();
	// AjaxFormHandler chains close + grid refresh; wait for jQuery to
	// settle before asserting the modal closed so a slow save under
	// parallel load doesn't race the assertion.
	await waitForJQueryIdle(page);
	await expect(form).toHaveCount(0, {timeout: 15_000});
}

test.describe('Subscription types & policies', () => {
	test(
		'manager creates a subscription type',
		{tag: '@regression'},
		async ({pkpApi, browser, baseURL}) => {
			const tag = uniqueTag();
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});
			const ctx = await browser.newContext({
				storageState: await ensureAuthStateFor(browser, 'dbarnes', {
					baseURL,
				}),
				baseURL,
			});
			try {
				const page = await ctx.newPage();
				await openSubscriptionTypesTab(page, context.path);

				const name = `Yearly ${tag}`;
				await page
					.locator('a.pkp_linkaction_addSubscriptionType')
					.click();
				await createSubscriptionType(page, {
					name,
					cost: '50',
					duration: '12',
				});

				// The grid refreshes in place; the new row appears with the
				// entered name + currency + duration string.
				await expect(
					page.locator('#subscriptionTypesGridContainer tr.gridRow', {
						hasText: name,
					}),
				).toBeVisible();
				await expect(
					page.locator('#subscriptionTypesGridContainer tr.gridRow', {
						hasText: name,
					}),
				).toContainText('50.00 (CAD)');
			} finally {
				await ctx.close();
			}
		},
	);

	test(
		'manager edits the subscription policy and changes persist on reload',
		{tag: '@regression'},
		async ({pkpApi, browser, baseURL}) => {
			const tag = uniqueTag();
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});
			const ctx = await browser.newContext({
				storageState: await ensureAuthStateFor(browser, 'dbarnes', {
					baseURL,
				}),
				baseURL,
			});
			try {
				const page = await ctx.newPage();
				await openSubscriptionPoliciesTab(page, context.path);

				const contactName = `Contact ${tag}`;
				const contactEmail = `contact-${tag}@example.test`;
				const mailingAddress = `123 Any St. ${tag}`;

				const form = page.locator('form#subscriptionPolicies');
				await form
					.locator('input[id^="subscriptionName"]')
					.fill(contactName);
				await form
					.locator('input[id^="subscriptionEmail"]')
					.fill(contactEmail);
				await form
					.locator('textarea[id^="subscriptionMailingAddress"]')
					.fill(mailingAddress);

				await form.locator('button[type="submit"]').click();
				// AjaxFormHandler emits a trivial notification but keeps
				// the form mounted on success. Wait for jQuery to settle
				// (the PUT + chained handlers) before reloading.
				await waitForJQueryIdle(page);

				// Reload and re-open the tab; values should be re-populated
				// from the database.
				await openSubscriptionPoliciesTab(page, context.path);
				await expect(
					page.locator(
						'form#subscriptionPolicies input[id^="subscriptionName"]',
					),
				).toHaveValue(contactName);
				await expect(
					page.locator(
						'form#subscriptionPolicies input[id^="subscriptionEmail"]',
					),
				).toHaveValue(contactEmail);
				await expect(
					page.locator(
						'form#subscriptionPolicies textarea[id^="subscriptionMailingAddress"]',
					),
				).toHaveValue(mailingAddress);
			} finally {
				await ctx.close();
			}
		},
	);

	test(
		'manager deletes a subscription type',
		{tag: '@regression'},
		async ({pkpApi, browser, baseURL}) => {
			const tag = uniqueTag();
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});
			const ctx = await browser.newContext({
				storageState: await ensureAuthStateFor(browser, 'dbarnes', {
					baseURL,
				}),
				baseURL,
			});
			try {
				const page = await ctx.newPage();
				await openSubscriptionTypesTab(page, context.path);

				const name = `Disposable ${tag}`;
				await page
					.locator('a.pkp_linkaction_addSubscriptionType')
					.click();
				await createSubscriptionType(page, {
					name,
					cost: '10',
					duration: '1',
				});

				const row = page.locator(
					'#subscriptionTypesGridContainer tr.gridRow',
					{hasText: name},
				);
				await expect(row).toBeVisible();

				await revealRowControls(page);
				// The Delete link action lives in the sibling `tr.row_controls`
				// — click the one whose id encodes `-delete-button-`.
				await page
					.locator('a.pkp_linkaction_delete', {hasText: 'Delete'})
					.first()
					.click();

				// Reka-ui dialog with OK/Cancel; click OK to confirm.
				const dialog = page.locator('[role="dialog"]', {
					hasText: 'delete this subscription type',
				});
				await expect(dialog).toBeVisible();
				await dialog.getByRole('button', {name: 'OK'}).click();

				// Grid re-fetches; let jQuery settle before asserting
				// the row is gone.
				await waitForJQueryIdle(page);
				await expect(row).toHaveCount(0, {timeout: 15_000});
			} finally {
				await ctx.close();
			}
		},
	);
});
