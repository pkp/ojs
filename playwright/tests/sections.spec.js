// @ts-check
const {test, expect} = require('../support/fixtures.js');
const {ensureAuthStateFor} = require('../../lib/pkp/playwright/support/auth.js');

/**
 * Sections — row #8 in docs/e2e-playwright-migration.md.
 *
 * Ports cypress/tests/data/10-ApplicationSetup/50-CreateSections.cy.js.
 *
 * Sections are an OJS-only concept, so the spec lives at the OJS-root
 * playwright/ tree. Each test seeds its own E0 scratch journal (via
 * pkpApi.createJournal) so the bootstrapped publicknowledge journal's
 * sections stay untouched.
 *
 * The sections UI on /management/settings/context#sections is the
 * legacy `pkp_controllers_linkAction` jQuery grid — not Vue. The grid
 * embeds in the Sections tab of the journal-settings page and the
 * Edit/Create form renders in a jQuery-UI dialog. Selectors below
 * hook the grid's `component-grid-settings-sections-*` ids and the
 * checkbox `id`s defined in
 * templates/controllers/grid/settings/sections/form/sectionForm.tpl.
 *
 * Scope note on the editor-only test: the Cypress source didn't
 * exercise the wizard-side effect of `editorRestricted` either; it
 * only created sections. Row #12 (Wizard — section rules) is the
 * proper place for the wizard-visibility assertion. Here we verify
 * the admin-UI side — setting `editorRestricted` on a section
 * persists across reload — and leave the wizard-side round-trip to
 * row #12 to avoid doubling the spec's size and reinventing
 * wizard navigation that row #12 will share across several specs.
 */

function uniqueTag() {
	const workerIndex = test.info().parallelIndex;
	const suffix = Math.random().toString(36).slice(2, 8);
	return `sec-w${workerIndex}-${suffix}`;
}

async function openSectionsTab(page, journalPath) {
	await page.goto(`/index.php/${journalPath}/management/settings/context`);
	await page.locator('#sections-button').click();
	// Grid content loads async via load_url_in_div — wait for at least
	// the "Create Section" action to be present.
	await expect(
		page.locator(
			'a[id^="component-grid-settings-sections-sectiongrid-addSection-button-"]',
		),
	).toBeVisible();
	// The row-level Edit/Delete actions live in `tr.row_controls` which
	// the grid hides until the grid-level "Settings" toggle is
	// expanded. Click it up-front so edits can proceed.
	await page.locator('#sectionsGridContainer a.show_extras').first().click();
}

/**
 * Click "Create Section" and return a locator for the section form
 * once the dialog has opened.
 */
async function openAddSectionForm(page) {
	await page
		.locator(
			'a[id^="component-grid-settings-sections-sectiongrid-addSection-button-"]',
		)
		.click();
	await expect(page.locator('form#sectionForm')).toBeVisible();
	return page.locator('form#sectionForm');
}

/**
 * Click Edit on the section whose title matches `title`. The legacy
 * grid renders each row as two adjacent <tr> elements that share the
 * same row id: a `tr.gridRow` that carries the title/columns and a
 * sibling `tr.row_controls#...-control-row` that carries the
 * Edit/Delete link actions. We locate the data row by title, read its
 * row id off its DOM id, then click the matching `editSection` action
 * in the sibling control row.
 */
async function openEditSectionForm(page, title) {
	const row = page.locator(
		'tr.gridRow[id^="component-grid-settings-sections-sectiongrid-row-"]',
		{hasText: title},
	);
	const rowId = await row.first().getAttribute('id');
	if (!rowId) {
		throw new Error(`Section row "${title}" not found`);
	}
	await page
		.locator(`a[id^="${rowId}-editSection-button-"]`)
		.first()
		.click();
	await expect(page.locator('form#sectionForm')).toBeVisible();
	return page.locator('form#sectionForm');
}

/**
 * Submit the section form and wait for the dialog to close.
 */
async function saveSectionForm(page) {
	const form = page.locator('form#sectionForm');
	await form.getByRole('button', {name: 'Save'}).click();
	// AjaxFormHandler closes the dialog on success.
	await expect(form).toHaveCount(0, {timeout: 15_000});
}

test.describe('Sections', () => {
	test(
		'manager creates a new section',
		{tag: '@regression'},
		async ({pkpApi, browser, baseURL}) => {
			const tag = uniqueTag();
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});
			const ctx = await browser.newContext({
				storageState: await ensureAuthStateFor(browser, 'dbarnes', {baseURL}),
				baseURL,
			});
			try {
				const page = await ctx.newPage();
				await openSectionsTab(page, context.path);

				const title = `Reviews ${tag}`;
				const abbrev = `REV-${tag}`;

				const form = await openAddSectionForm(page);
				await form.locator('input[id^="title-"]').fill(title);
				await form.locator('input[id^="abbrev-"]').fill(abbrev);
				await saveSectionForm(page);

				// After save the grid refreshes in-place; the new row
				// appears with the entered title.
				await expect(
					page.locator('tr.gridRow', {hasText: title}),
				).toBeVisible();
			} finally {
				await ctx.close();
			}
		},
	);

	test(
		'manager edits a section — sets it inactive and the flag persists on reload',
		{tag: '@regression'},
		async ({pkpApi, browser, baseURL}) => {
			const tag = uniqueTag();
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});
			const ctx = await browser.newContext({
				storageState: await ensureAuthStateFor(browser, 'dbarnes', {baseURL}),
				baseURL,
			});
			try {
				const page = await ctx.newPage();
				await openSectionsTab(page, context.path);

				// SectionForm::validate() rejects `isInactive` when the
				// target section is the only active one (cf. lib/pkp
				// commit history on manager.sections.confirmDeactivateSection.error).
				// A scratch journal starts with a single default
				// "Articles" section, so first create a second section
				// and deactivate the original.
				const extraTitle = `Extra ${tag}`;
				const extraAbbrev = `EX-${tag}`;
				let form = await openAddSectionForm(page);
				await form.locator('input[id^="title-"]').fill(extraTitle);
				await form.locator('input[id^="abbrev-"]').fill(extraAbbrev);
				await saveSectionForm(page);
				await expect(
					page.locator('tr.gridRow', {hasText: extraTitle}),
				).toBeVisible();

				const title = 'Articles';
				form = await openEditSectionForm(page, title);
				const inactive = form.locator('input#isInactive');
				await expect(inactive).not.toBeChecked();
				await inactive.check({force: true});
				await saveSectionForm(page);

				// Reload the settings page and re-open Edit — the
				// checkbox should still be checked.
				await openSectionsTab(page, context.path);
				form = await openEditSectionForm(page, title);
				await expect(form.locator('input#isInactive')).toBeChecked();
			} finally {
				await ctx.close();
			}
		},
	);

	test(
		'manager marks a section as editor-only and the flag persists on reload',
		{tag: '@regression'},
		async ({pkpApi, browser, baseURL}) => {
			// Scope deviation: the Cypress source never tested the
			// wizard-side effect of editor-only sections; row #12
			// (Wizard — section rules) covers that. Here we verify the
			// admin-UI side only — that `editorRestricted` persists —
			// which is all the sections spec should own.
			const tag = uniqueTag();
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});
			const ctx = await browser.newContext({
				storageState: await ensureAuthStateFor(browser, 'dbarnes', {baseURL}),
				baseURL,
			});
			try {
				const page = await ctx.newPage();
				await openSectionsTab(page, context.path);

				const title = 'Articles';

				let form = await openEditSectionForm(page, title);
				const editorRestricted = form.locator('input#editorRestricted');
				await expect(editorRestricted).not.toBeChecked();
				await editorRestricted.check({force: true});
				await saveSectionForm(page);

				await openSectionsTab(page, context.path);
				form = await openEditSectionForm(page, title);
				await expect(form.locator('input#editorRestricted')).toBeChecked();
			} finally {
				await ctx.close();
			}
		},
	);

	test(
		'manager configures section fields (wordCount, identifyType, abstractsNotRequired) and they persist on reload',
		{tag: '@regression'},
		async ({pkpApi, browser, baseURL}) => {
			// Ports the field-config half of Cypress's
			// 50-CreateSections.cy.js: editing a section to add a
			// `wordCount`, ticking `abstractsNotRequired`, and entering an
			// `identifyType` translation. wordCount is a plain integer
			// (not locale-keyed); identifyType is multilingual; the
			// abstractsNotRequired checkbox carries a stable
			// `id="abstractsNotRequired"` (not fbv-suffixed).
			const tag = uniqueTag();
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});
			const ctx = await browser.newContext({
				storageState: await ensureAuthStateFor(browser, 'dbarnes', {baseURL}),
				baseURL,
			});
			try {
				const page = await ctx.newPage();
				await openSectionsTab(page, context.path);

				const title = 'Articles';
				const identifyType = `Review Article ${tag}`;

				let form = await openEditSectionForm(page, title);
				// fbv suffixes ids with a runtime uniqId, so anchor on the
				// stable `name=` attribute. The multilingual identifyType
				// renders as `name="identifyType[en]"`. When the form has
				// only one supported locale (the scratch journal default),
				// the id collapses to `identifyType-{uniqId}` with no
				// locale segment, so `id^="identifyType-en-"` would miss.
				await form.locator('input[name="wordCount"]').fill('500');
				await form
					.locator('input[name="identifyType[en]"]')
					.fill(identifyType);
				const abstractsNotRequired = form.locator(
					'input#abstractsNotRequired',
				);
				await expect(abstractsNotRequired).not.toBeChecked();
				await abstractsNotRequired.check({force: true});
				await saveSectionForm(page);

				// Reload the settings page and re-open Edit — fields should
				// be persisted.
				await openSectionsTab(page, context.path);
				form = await openEditSectionForm(page, title);
				await expect(form.locator('input[name="wordCount"]')).toHaveValue(
					'500',
				);
				await expect(
					form.locator('input[name="identifyType[en]"]'),
				).toHaveValue(identifyType);
				await expect(form.locator('input#abstractsNotRequired')).toBeChecked();

				// Sanity-check via REST that the persisted values match.
				const sectionsResp = await page.request.get(
					`/index.php/${context.path}/api/v1/sections`,
				);
				expect(sectionsResp.ok(), 'list sections').toBe(true);
				const body = await sectionsResp.json();
				const articles = (body.items || []).find(
					(s) => (s.title?.en || '').trim() === 'Articles',
				);
				expect(articles, 'Articles section in REST listing').toBeTruthy();
				expect(articles.wordCount).toBe(500);
				expect(articles.abstractsNotRequired).toBe(true);
				expect(articles.identifyType?.en).toBe(identifyType);
			} finally {
				await ctx.close();
			}
		},
	);

	test(
		'manager assigns multiple section editors to a section and the assignment persists',
		{tag: '@regression'},
		async ({pkpApi, browser, baseURL}) => {
			// Ports the editor-assignment half of Cypress's
			// 50-CreateSections.cy.js. The section-edit form's
			// `assignableUserGroups` query (PKPSectionForm::fetch) filters
			// user-groups by `withStageIds([WORKFLOW_STAGE_ID_SUBMISSION])`,
			// which excludes the bare `manager` user-group (registry/
			// userGroups.xml gives it no stages). dbarnes therefore needs
			// the `editor` user-group (stages=1,3,4,5) to surface in the
			// list — matches the bootstrap publicknowledge fixture where
			// the original Cypress spec ran. Seed two extra
			// sectionEditor users so the form renders three editor
			// candidates: dbarnes (Journal editor), dbuskins (Section
			// editor), minoue (Section editor). Each label reads
			// "Assign {Name} as {Role}" — match by the user's full name.
			const tag = uniqueTag();
			const {context} = await pkpApi.createJournal({
				tag,
				users: [
					{username: 'dbarnes', roles: ['manager', 'editor']},
					{username: 'dbuskins', roles: ['sectionEditor']},
					{username: 'minoue', roles: ['sectionEditor']},
				],
			});
			const ctx = await browser.newContext({
				storageState: await ensureAuthStateFor(browser, 'dbarnes', {baseURL}),
				baseURL,
			});
			try {
				const page = await ctx.newPage();
				await openSectionsTab(page, context.path);

				const title = 'Articles';
				const editorNames = ['Daniel Barnes', 'David Buskins', 'Minoti Inoue'];

				let form = await openEditSectionForm(page, title);
				for (const name of editorNames) {
					// Each editor renders one <label> per user-group it can
					// be assigned under. dbarnes is a manager (one row);
					// dbuskins / minoue are sectionEditors (one row each).
					// Click only the first matching label per user — that's
					// what the Cypress source did (`label.contains(name)`
					// returns the first match).
					const label = form
						.locator('label', {hasText: `Assign ${name} as `})
						.first();
					await expect(label).toBeVisible();
					await label.click();
				}
				await saveSectionForm(page);

				// Reload the settings page and re-open Edit — the three
				// matching checkboxes should all be checked. fbv's
				// checkbox.tpl nests the <input> directly inside the
				// <label>, so the input is reachable as a label
				// descendant.
				await openSectionsTab(page, context.path);
				form = await openEditSectionForm(page, title);
				for (const name of editorNames) {
					const checkbox = form
						.locator(`label:has-text("Assign ${name} as ")`)
						.locator('input[type="checkbox"]')
						.first();
					await expect(checkbox).toBeChecked();
				}
			} finally {
				await ctx.close();
			}
		},
	);
});
