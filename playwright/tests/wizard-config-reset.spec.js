// @ts-check
const {test, expect} = require('../support/fixtures.js');
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
 *   - require / disable directions on `keywords`.
 *   - One non-keywords field (`subjects`) flipped to Require, to prove
 *     the FieldMetadataSetting wiring is general across the
 *     ForTheEditors form (subjects renders in step #4, not step #2 like
 *     keywords — different component, same configuration plumbing).
 *   - Review-step required-field error: with keywords flipped to
 *     Require and the field left empty, the Review step's Details
 *     panel surfaces "This field is required." for the keywords entry,
 *     and the wizard's top-level errors banner appears.
 *
 * Scope dropped:
 *   - The full 10-field Cypress preamble (agencies / citations /
 *     coverage / dataAvailability / disciplines / rights / source /
 *     type). With one keywords + one subjects test the wiring is
 *     exercised across both Details (titleAbstract) and ForTheEditors
 *     forms — covering the two FieldMetadataSetting host surfaces.
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
 * Set a metadata field's mode on the open Metadata form. Each metadata
 * field is rendered by the same FieldMetadataSetting.vue, so the helper
 * scopes to the field's fieldset by its uniquely-named "Enable …
 * metadata" checkbox label and otherwise treats every field
 * identically.
 *
 * FieldMetadataSetting.vue renders as two layers: (a) an "Enable"
 * checkbox whose state is bound to isEnabled, and (b) a radio group of
 * submissionOptions (noRequest / request / require) that only appears
 * when isEnabled is true. Toggling the checkbox OFF sets value to the
 * disabledValue (METADATA_DISABLE = 0); toggling it ON sets value to
 * the enabledOnlyValue (METADATA_ENABLE = "enable"); picking a radio
 * sets value to the corresponding submissionOption.
 *
 * The form is a shared `<pkp-form>` — its Save button is the footer
 * "Save" whose click triggers a PUT on the context endpoint via
 * X-Http-Method-Override. Race the click with a waitForResponse on the
 * context PUT to know the round-trip is done.
 *
 * @param {import('@playwright/test').Page} page
 * @param {Object} field                     Metadata field descriptor.
 * @param {string} field.enableLabel         The "Enable …" checkbox label
 *                                            text — uniquely identifies the
 *                                            field's fieldset on the form
 *                                            (e.g. "Enable keyword metadata").
 * @param {RegExp} field.requireLabel        Regex matching the field's
 *                                            "Require the author …" radio
 *                                            label text.
 * @param {'require' | 'disable'} mode
 */
async function setMetadataFieldMode(page, {enableLabel, requireLabel}, mode) {
	const fieldset = page.locator('fieldset.pkpFormField--metadata', {
		has: page.locator('label', {hasText: enableLabel}),
	});
	await expect(fieldset).toBeVisible();

	const enableCheckbox = fieldset
		.locator('input.pkpFormField--options__input[type="checkbox"]')
		.first();

	if (mode === 'disable') {
		// Schema defaults vary by field — keywords ships request,
		// subjects ships noRequest (i.e. disabled). Only flip if
		// currently enabled.
		if (await enableCheckbox.isChecked()) {
			await enableCheckbox.uncheck();
		}
	} else if (mode === 'require') {
		// Make sure the field is enabled so the submissionOptions
		// radios render. (FieldMetadataSetting.vue keeps the radios
		// unmounted when isEnabled is false.)
		if (!(await enableCheckbox.isChecked())) {
			await enableCheckbox.check();
		}
		const target = fieldset.locator('label', {hasText: requireLabel});
		await expect(target).toBeVisible();
		await target.click();
	} else {
		throw new Error(`Unknown mode: ${mode}`);
	}

	const form = page.locator('form', {has: fieldset});
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

const KEYWORDS = {
	enableLabel: 'Enable keyword metadata',
	requireLabel: /Require the author to suggest keywords/,
};
const SUBJECTS = {
	enableLabel: 'Enable subject metadata',
	requireLabel: /Require the author to provide subjects/,
};

test.describe('Submission wizard — field-config reset', () => {
	test(
		'toggling keywords to Require surfaces it as required in the wizard',
		{tag: '@regression'},
		async ({pkpApi, asUser}) => {
			const tag = uniqueTag();

			// E0 scratch journal, dbarnes = manager. Editor-cum-manager is
			// the right role for a capability assertion about wizard field
			// config — the Details form's `keywords` gate is
			// role-agnostic; it only checks the context's setting.
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});

			const ctx = await asUser('dbarnes');
			const page = await ctx.newPage();

			// Flip keywords → require via the Metadata settings form.
			await openMetadataSettingsTab(page, context.path);
			await setMetadataFieldMode(page, KEYWORDS, 'require');

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
		
		},
	);

	test(
		'toggling keywords to Do-not-ask removes it from the wizard Details step',
		{tag: '@regression'},
		async ({pkpApi, asUser}) => {
			const tag = uniqueTag();

			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});

			const ctx = await asUser('dbarnes');
			const page = await ctx.newPage();

			// Flip keywords → disabled (METADATA_DISABLE = 0).
			await openMetadataSettingsTab(page, context.path);
			await setMetadataFieldMode(page, KEYWORDS, 'disable');

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

		},
	);

	test(
		'toggling subjects to Require surfaces it as required in the For-the-Editors step',
		{tag: '@regression'},
		async ({pkpApi, asUser}) => {
			const tag = uniqueTag();

			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});

			const ctx = await asUser('dbarnes');
			const page = await ctx.newPage();

			// Flip subjects → require. subjects renders inside the
			// ForTheEditors form (Step 4) rather than Details — proves
			// the FieldMetadataSetting wiring is general across the two
			// host forms (titleAbstract for keywords, forTheEditors for
			// subjects). Same FieldMetadataSetting component, same
			// PKPMetadataSettingsForm submit, same context schema gate.
			await openMetadataSettingsTab(page, context.path);
			await setMetadataFieldMode(page, SUBJECTS, 'require');

			const wizard = new SubmissionWizardPage(page, context.path);
			await wizard.goto();
			await wizard.start({title: `Require-subjects ${tag}`});

			// Walk past Upload + Details + Contributors to land on
			// "For the Editors" — Step 4. The subjects control id
			// follows `forTheEditors-subjects-control-{locale}`.
			await wizard.continueStep(); // Upload Files
			await wizard.continueStep(); // Details
			await wizard.continueStep(); // Contributors

			const subjectsLabel = page.locator(
				'label[for="forTheEditors-subjects-control-en"]',
			);
			await expect(subjectsLabel).toBeVisible({timeout: 15_000});
			await expect(
				subjectsLabel.locator('.pkpFormFieldLabel__required'),
			).toBeVisible();
		},
	);

	test(
		'missing required keyword surfaces a validation error in Review',
		{tag: '@regression'},
		async ({pkpApi, asUser}) => {
			const tag = uniqueTag();

			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});

			const ctx = await asUser('dbarnes');
			const page = await ctx.newPage();

			// Same setup as test 1: keywords flipped to require. This
			// time the wizard advances all the way to Review without
			// supplying a keyword, and the assertion is on Review's
			// validation panel surfacing the missing-keyword error.
			await openMetadataSettingsTab(page, context.path);
			await setMetadataFieldMode(page, KEYWORDS, 'require');

			const wizard = new SubmissionWizardPage(page, context.path);
			await wizard.goto();
			await wizard.start({title: `Require-review-error ${tag}`});

			// Step 1 → 2 → 3 → 4 → Review (4 Continues land on Review).
			await wizard.continueStep(); // Upload
			await wizard.continueStep(); // Details (keywords required, but step
			//                              gate doesn't validate yet)
			await wizard.continueStep(); // Contributors
			await wizard.continueStep(); // For the Editors

			// Wizard's top-level errors banner appears once at least
			// one required field is missing across the steps. Anchor
			// on its localized phrase rather than a CSS class.
			await expect(
				page.getByText(/There are one or more problems/i),
			).toBeVisible({timeout: 15_000});

			// The Review panel's Details section reports per-field
			// validation. On a single-locale scratch journal the
			// heading reads simply "Details" (the "(English)" suffix
			// only appears when supportedSubmissionLocales has 2+
			// entries). The Keywords entry must carry the localized
			// "This field is required." string. Scope to the Details
			// review panel by heading so an error elsewhere on the
			// page can't fool us.
			const detailsPanel = page
				.locator('.submissionWizard__reviewPanel')
				.filter({
					has: page.getByRole('heading', {name: /^Details$/i}),
				});
			await expect(detailsPanel).toHaveCount(1);
			await expect(detailsPanel).toContainText('Keywords');
			await expect(detailsPanel).toContainText('This field is required.');
		},
	);
});
