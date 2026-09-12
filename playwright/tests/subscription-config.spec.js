// @ts-check
const {test, expect} = require('../support/fixtures.js');
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
		async ({pkpApi, asUser}) => {
			const tag = uniqueTag();
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});
			const ctx = await asUser('dbarnes');
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
		
		},
	);

	test(
		'manager edits the subscription policy and changes persist on reload',
		{tag: '@regression'},
		async ({pkpApi, asUser}) => {
			const tag = uniqueTag();
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});
			const ctx = await asUser('dbarnes');
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
		
		},
	);

	test(
		'manager deletes a subscription type',
		{tag: '@regression'},
		async ({pkpApi, asUser}) => {
			const tag = uniqueTag();
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});
			const ctx = await asUser('dbarnes');
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

		},
	);

	test(
		'manager configures Payments via Distribution > Payments tab; settings persist on reload',
		{tag: '@regression'},
		async ({pkpApi, asUser}) => {
			const tag = uniqueTag();
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});
			const ctx = await asUser('dbarnes');
			const page = await ctx.newPage();

			// Distribution → Payments tab. PkpTabs id-anchored:
			// outer `#distribution-button`, inner `#payments-button`.
			await page.goto(
				`/index.php/${context.path}/management/settings/distribution#payments`,
			);
			await page.locator('#payments-button').click();

			// PKPPaymentSettingsForm starts with just an Enable
			// checkbox visible — the plugin select, currency select,
			// and plugin-specific fields cascade in only after Enable
			// is ticked (FieldOptions showWhen pattern). Anchor the
			// form by the always-present Enable label.
			const form = page
				.locator('form', {
					has: page.locator('label', {
						hasText: 'Payments will be enabled',
					}),
				})
				.first();
			await expect(form).toBeVisible({timeout: 15_000});

			// Tick Enable.
			await form
				.locator('label', {hasText: 'Payments will be enabled'})
				.first()
				.click();

			// Wait for the plugin select to mount.
			const pluginSelect = form.locator(
				'select#paymentSettings-paymentPluginName-control',
			);
			await expect(pluginSelect).toBeVisible({timeout: 15_000});
			await pluginSelect.selectOption({label: 'Manual Fee Payment'});

			// Currency.
			await form
				.locator('select#paymentSettings-currency-control')
				.selectOption({label: 'Canadian Dollar'});

			const instructions = `Test manual instructions ${tag}.`;
			const instrTextarea = form.locator(
				'textarea#paymentSettings-manualInstructions-control',
			);
			await expect(instrTextarea).toBeVisible({timeout: 15_000});
			await instrTextarea.fill(instructions);

			// Save the main paymentSettings form. The Payments tab
			// renders multiple forms (paymentSettings + per-plugin
			// settings), each with its own Save. Scope to the form
			// containing the manualInstructions textarea (since we
			// just selected manualpayment, that field is part of the
			// paymentSettings form). Race with the context PUT.
			const settingsForm = page.locator('form', {
				has: page.locator(
					'textarea#paymentSettings-manualInstructions-control',
				),
			});
			const settingsSaveBtn = settingsForm.getByRole('button', {
				name: 'Save',
				exact: true,
			});
			await expect(settingsSaveBtn).toHaveCount(1);
			await settingsSaveBtn.scrollIntoViewIfNeeded();
			await expect(settingsSaveBtn).toBeEnabled();
			await Promise.all([
				page.waitForResponse(
					(res) =>
						// PKPPaymentSettingsForm posts to the
						// `_payments` API route (see
						// ManagementHandler.php#377), not the generic
						// `/api/v1/contexts/{id}` URL — match both for
						// robustness.
						/\/api\/v1\/(_payments|contexts\/\d+)/.test(res.url()) &&
						res.ok() &&
						['POST', 'PUT'].includes(res.request().method()),
					{timeout: 15_000},
				),
				settingsSaveBtn.click(),
			]);

			// Reload + reactivate the tab; the persisted instructions
			// + currency + plugin name are the stable signals. After
			// enable persists, the cascade fields render on first
			// paint (no need to re-toggle).
			await page.reload();
			await page.locator('#payments-button').click();
			const reloaded = page
				.locator('form', {
					has: page.locator('label', {
						hasText: 'Payments will be enabled',
					}),
				})
				.first();
			await expect(reloaded).toBeVisible({timeout: 15_000});
			await expect(
				reloaded.locator(
					'textarea#paymentSettings-manualInstructions-control',
				),
			).toHaveValue(instructions);
			// Currency + plugin selects don't expose their selected
			// option's TEXT through Playwright directly — assert on
			// the underlying option values via the `<select>`'s
			// `value` property.
			await expect(
				reloaded.locator('select#paymentSettings-currency-control'),
			).toHaveValue('CAD');
			// The paymentPluginName option value uses the plugin's
			// class basename (CamelCase), per
			// PKPPaymentSettingsForm's plugin enumeration —
			// `ManualPayment` for the manualpayment plugin.
			await expect(
				reloaded.locator('select#paymentSettings-paymentPluginName-control'),
			).toHaveValue('ManualPayment');
		},
	);

	test(
		'manager flips Distribution > Access publishingMode to Subscription; setting persists',
		{tag: '@regression'},
		async ({pkpApi, asUser}) => {
			const tag = uniqueTag();
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});
			const ctx = await asUser('dbarnes');
			const page = await ctx.newPage();

			// Distribution → Access tab. publishingMode is a
			// FieldOptions radio with three values: 0 (Open Access),
			// 1 (Subscription), 2 (None of the above).
			await page.goto(
				`/index.php/${context.path}/management/settings/distribution#access`,
			);
			await page.locator('#access-button').click();

			const form = page.locator('#access form').first();
			await expect(form).toBeVisible({timeout: 15_000});

			// Click the "subscription" radio label — the i18n string
			// "The journal will require subscriptions" maps to
			// publishingMode=1 (PUBLISHING_MODE_SUBSCRIPTION). Use a
			// regex-anchored label match because the surrounding text
			// drifts across versions.
			await form
				.locator('label', {
					hasText: /journal will require subscriptions/i,
				})
				.first()
				.click();

			await Promise.all([
				page.waitForResponse(
					(res) =>
						/\/api\/v1\/contexts\/\d+/.test(res.url()) &&
						res.ok() &&
						['POST', 'PUT'].includes(res.request().method()),
					{timeout: 15_000},
				),
				form.getByRole('button', {name: 'Save', exact: true}).click(),
			]);

			// Reload, reactivate the tab, assert the radio still
			// reads value=1.
			await page.reload();
			await page.locator('#access-button').click();
			const reloaded = page.locator('#access form').first();
			await expect(reloaded).toBeVisible({timeout: 15_000});
			await expect(
				reloaded
					.locator('input[name="publishingMode"]:checked')
					.first(),
			).toHaveValue('1');

			// REST sanity-check: the context's publishingMode is now 1.
			const ctxResp = await page.request.get(
				`/index.php/${context.path}/api/v1/contexts/${context.id}`,
			);
			expect(ctxResp.ok()).toBeTruthy();
			expect((await ctxResp.json()).publishingMode).toBe(1);
		},
	);
});
