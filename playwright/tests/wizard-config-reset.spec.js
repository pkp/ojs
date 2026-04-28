// @ts-check
const {test, expect} = require('../support/fixtures.js');
const {ensureAuthStateFor} = require('../../lib/pkp/playwright/support/auth.js');
const {SubmissionWizardPage} = require('../../lib/pkp/playwright/pages/SubmissionWizardPage.js');

/**
 * Wizard — field-config reset — row #16 in
 * docs/e2e-playwright-migration.md.
 *
 * Ports cypress/tests/integration/SubmissionWizard.cy.js test 6
 * ("Resets the submission wizard fields to more common configuration")
 * AND the journal-level flag-flip preamble that tests 4–5 share. In the
 * Cypress suite the flag-flip preamble was baked into tests 4 and 5; row
 * #10 (wizard-validation) and row #13 (wizard-language) dropped the
 * flag-flip because row #16 owns it as its own feature.
 *
 * The feature under test: metadata-field config on the journal
 * Submission > Metadata tab (PKPMetadataSettingsForm) flows through to
 * the wizard's Details step via the Details form's
 * `in_array($context->getData('keywords'), [METADATA_REQUEST,
 * METADATA_REQUIRE])` gate and `isRequired` prop (see
 * lib/pkp/classes/components/forms/publication/Details.php#addKeywordField
 * equivalents). Toggling "Require" in settings on a fresh session turns
 * the wizard's Keywords field into a required one (asterisk label via
 * FormFieldLabel.vue's pkpFormFieldLabel__required span); toggling it
 * to "Do not ask" (METADATA_DISABLE) removes the field entirely.
 *
 * Scope kept:
 *   - One capability assertion per direction: require → Keywords label
 *     acquires the required asterisk in the Details step.
 *   - disable → the Keywords control disappears from the Details form.
 *
 * Scope dropped:
 *   - The full 10-field Cypress preamble (agencies / citations /
 *     coverage / dataAvailability / disciplines / keywords / rights /
 *     source / subjects / type). Picking a single field (keywords)
 *     proves the wiring without turning the spec into a config-knob
 *     smoke test. All fields go through the same
 *     FieldMetadataSetting component + the same `in_array(...,
 *     [METADATA_REQUEST, METADATA_REQUIRE])` gate, so exercising one
 *     covers the capability.
 *   - The Cypress source's "reset to defaults" test (test 6) just un-did
 *     what tests 4–5 configured. With E0 per-test scratch journals that
 *     teardown cost doesn't exist — each journal starts clean — so the
 *     reset assertion has no test value here.
 *
 * OJS-only: yes — spec lives at playwright/tests/ because the
 * Metadata settings tab is reached via the OJS-routed URL prefix. The
 * PKPMetadataSettingsForm is shared across apps but the URL structure
 * and tab layout (workflow.tpl's tab/tabs shell) ship with the parent
 * app.
 */

function uniqueTag() {
	const workerIndex = test.info().parallelIndex;
	const suffix = Math.random().toString(36).slice(2, 8);
	return `wcr-w${workerIndex}-${suffix}`;
}

/**
 * Navigate to the workflow settings page, activate the Submission tab +
 * Metadata sub-tab. The page uses nested `<tabs>` — outer tabs switch
 * between "Submission" / "Review" / "Library" / "Emails" / ..., and the
 * inner side-tabs under "Submission" switch between "Disable Submissions" /
 * "Instructions" / "Metadata" / "Components" / "Contributor Roles".
 *
 * Both outer + inner tab triggers carry id hooks of the form
 * `#{name}-button` (PkpTabs convention), so #submission-button opens the
 * outer tab and #metadata-button opens the inner one.
 */
async function openMetadataSettingsTab(page, journalPath) {
	await page.goto(`/index.php/${journalPath}/management/settings/workflow`);
	// Outer tab: Submission (the default, but click to make the state
	// deterministic — some runs land on a cached tab-history state).
	await page.locator('#submission-button').click();
	// Inner tab: Metadata.
	await page.locator('#metadata-button').click();
	// The Metadata form mounts a fieldset legend per field. Keywords'
	// legend is "Keywords" (common.keywords). Waiting for it guarantees
	// the form has hydrated. `hasText` with a regex does substring
	// matching; anchoring ^Keywords$ would require the legend to be a
	// single text node exactly equal to "Keywords", but the element
	// contains a nested span with padding whitespace. Prefer a positive
	// substring match + check for the visible "Enable keyword metadata"
	// label text to disambiguate from similar-named sections elsewhere.
	await expect(
		page.locator('label', {hasText: 'Enable keyword metadata'}),
	).toBeVisible({timeout: 15_000});
}

/**
 * Set the `keywords` metadata flag on the open Metadata form.
 *
 * FieldMetadataSetting.vue renders as two layers: (a) an "Enable" checkbox
 * whose state is bound to isEnabled, and (b) a radio group of
 * submissionOptions (noRequest / request / require) that only appears
 * when isEnabled is true. Toggling the checkbox OFF sets value to the
 * disabledValue (METADATA_DISABLE = 0); toggling it ON sets value to the
 * enabledOnlyValue (METADATA_ENABLE = "enable"); picking a radio sets
 * value to the corresponding submissionOption.
 *
 * The form is a shared `<pkp-form>` — its Save button is the footer
 * "Save" whose click triggers a PUT on the context endpoint. We wait for
 * the success notification to know the round-trip is done.
 *
 * @param {import('@playwright/test').Page} page
 * @param {'require' | 'disable'} mode
 */
async function setKeywordsMode(page, mode) {
	// Scope to the keywords field — the Metadata form has a stack of
	// similarly-structured fieldsets. Anchor on the uniquely-named
	// "Enable keyword metadata" checkbox label (the keywords field's
	// options[0].label from PKPMetadataSettingsForm.php). This avoids
	// the whitespace-anchoring gotcha of `<legend>^Keywords$</legend>`
	// (the legend contains a child span with padding whitespace, so
	// anchored substring matching fails).
	const keywordsField = page.locator('fieldset.pkpFormField--metadata', {
		has: page.locator('label', {hasText: 'Enable keyword metadata'}),
	});
	await expect(keywordsField).toBeVisible();

	const enableCheckbox = keywordsField
		.locator('input.pkpFormField--options__input[type="checkbox"]')
		.first();

	if (mode === 'disable') {
		// Default is "request" (schema default), so the checkbox is
		// already ticked. Untick it → metadata setting flips to 0.
		await expect(enableCheckbox).toBeChecked();
		await enableCheckbox.uncheck();
	} else if (mode === 'require') {
		// Checkbox already ticked (default is "request"). Click the
		// "Require" radio — its label text comes from
		// manager.setup.metadata.keywords.require:
		// "Require the author to suggest keywords before accepting
		//  their submission."
		await expect(enableCheckbox).toBeChecked();
		const requireLabel = keywordsField.locator('label', {
			hasText: /Require the author to suggest keywords/,
		});
		await expect(requireLabel).toBeVisible();
		await requireLabel.click();
	} else {
		throw new Error(`Unknown mode: ${mode}`);
	}

	// Save the Metadata form. The `<pkp-form>` renders exactly one
	// primary "Save" button inside its footer — scope to the form wrapper.
	// The form element doesn't advertise a stable id, but it's the one
	// containing the keywords fieldset, so scope via ancestor.
	const form = page.locator('form', {has: keywordsField});
	// Race the click with a waitForResponse on the PUT to the context
	// endpoint — the form posts as POST + X-Http-Method-Override: PUT, so
	// we intercept the server path. The response carrying the updated
	// context is the authoritative "save succeeded" signal, and once it
	// returns we can safely navigate away without losing the change.
	// pkp-form doesn't emit a user-visible toast on success inside
	// workflow.tpl (no SettingsPage-level form-success → notify wiring
	// for this particular form); relying on the network round-trip is
	// more reliable than polling for a transient UI cue.
	await Promise.all([
		page.waitForResponse(
			(res) =>
				res.request().method() === 'POST' &&
				/\/api\/v1\/contexts\/\d+/.test(res.url()) &&
				res.ok(),
			{timeout: 15_000},
		),
		form.getByRole('button', {name: 'Save', exact: true}).click(),
	]);
}

test.describe('Submission wizard — field-config reset', () => {
	test(
		'toggling keywords to Require surfaces it as required in the wizard',
		{tag: '@regression'},
		async ({pkpApi, browser, baseURL}) => {
			const tag = uniqueTag();

			// E0 scratch journal, dbarnes = manager. Editor-cum-manager is
			// the right role for a capability assertion about wizard field
			// config — the Details form's `keywords` gate is
			// role-agnostic; it only checks the context's setting.
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

				// Flip keywords → require via the Metadata settings form.
				await openMetadataSettingsTab(page, context.path);
				await setKeywordsMode(page, 'require');

				// New wizard session. The Keywords field is rendered by
				// publication/Details.php only when keywords is REQUEST
				// or REQUIRE, and its isRequired prop tracks REQUIRE
				// specifically. FormFieldLabel.vue adds a
				// pkpFormFieldLabel__required span when isRequired is
				// truthy — this span is absent by default (keywords
				// defaults to REQUEST, not REQUIRE).
				const wizard = new SubmissionWizardPage(page, context.path);
				await wizard.goto();
				await wizard.start({title: `Require-keywords ${tag}`});

				// Step 1 Upload Files → Continue (skip file upload, not
				// the feature under test here).
				await wizard.continueStep();

				// On Details. The Keywords control id is
				// `titleAbstract-keywords-control-{locale}` — the label
				// wraps it via for=controlId. Assert the label carries
				// the required-asterisk marker.
				const keywordsLabel = page.locator(
					'label[for="titleAbstract-keywords-control-en"]',
				);
				await expect(keywordsLabel).toBeVisible({timeout: 15_000});
				await expect(
					keywordsLabel.locator('.pkpFormFieldLabel__required'),
				).toBeVisible();
			} finally {
				await ctx.close();
			}
		},
	);

	test(
		'toggling keywords to Do-not-ask removes it from the wizard Details step',
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

				// Flip keywords → disabled (METADATA_DISABLE = 0).
				await openMetadataSettingsTab(page, context.path);
				await setKeywordsMode(page, 'disable');

				const wizard = new SubmissionWizardPage(page, context.path);
				await wizard.goto();
				await wizard.start({title: `No-keywords ${tag}`});

				await wizard.continueStep();

				// On Details. With keywords disabled, the Details form
				// doesn't add the `keywords` field at all
				// (Details.php#L48 gate), so the control + label are
				// absent.
				await expect(
					page.locator(
						'label[for="titleAbstract-keywords-control-en"]',
					),
				).toHaveCount(0);
				await expect(
					page.locator('#titleAbstract-keywords-control-en'),
				).toHaveCount(0);

				// Sanity: the Details form itself did render — the Title
				// control (which isn't config-gated) is present.
				await expect(
					page.locator(
						'textarea#titleAbstract-title-control-en',
					),
				).toBeAttached();
			} finally {
				await ctx.close();
			}
		},
	);
});
