// @ts-check
const {test, expect} = require('../support/fixtures.js');

/**
 * Site admin "Add Journal" UI — row #59 in docs/e2e-playwright-migration.md.
 *
 * Cypress source: cypress/tests/data/10-ApplicationSetup/20-CreateContext.cy.js.
 * That file fired the live admin/contexts grid → "Create" AjaxModal flow,
 * tripped two validators (urlPath pattern + already-required fields), then
 * succeeded into the Settings Wizard. This row's distinction from row #46
 * (multiple-contexts) is fidelity: row #46 spawns a journal via E0
 * (`pkpApi.createJournal`) — fast and programmatic. THIS spec drives the
 * actual /admin/contexts UI a site admin would touch in production, so the
 * UI's add-journal flow + URL-path validator stay covered as OJS evolves.
 *
 * The form lives in lib/pkp/templates/admin/editContext.tpl as
 * `<add-context-form>` (which extends pkp-form). It mounts inside a side
 * modal triggered by the contextGrid's `createContext` link action labelled
 * "Create Journal", and post-success the AddContextForm Vue component
 * navigates the browser to `/admin/wizard/{newId}` — the contextSettings.tpl
 * Settings Wizard with an h1 of "Settings Wizard".
 *
 * Field naming follows the FieldText/FieldOptions convention (FieldBase
 * #110-112):
 *   - non-multilingual: name="urlPath", name="contactName", etc.
 *   - multilingual:     name="name-en", name="acronym-en"
 *   - locale options:   name="supportedLocales" (checkbox group),
 *                       name="primaryLocale" (radio)
 *
 * Validation surfaces inline through the FieldError component
 * (lib/ui-library/.../FieldError.vue) — `<div class="pkpFieldError">` next
 * to the offending input. The Cypress source asserted via the `id`
 * pattern `context-{field}-error[-{locale}]` (the form's controlId scheme),
 * which we mirror with a substring class match for resilience.
 */

test.describe('Admin add journal UI', () => {
	test(
		'site admin creates a journal with multilingual name + URL path validation',
		{tag: '@regression'},
		async ({browser, baseURL}, testInfo) => {
			const tag = uniqueTag(testInfo);
			const urlPath = `aaj-w${testInfo.parallelIndex}-${tag}`;
			const journalName = `AAJ Journal ${tag}`;
			const acronym = `AAJ${tag.slice(-3).toUpperCase()}`;
			const contactName = 'Site Admin Tester';
			const contactEmail = `aaj-${tag}@mailinator.com`;

			const adminCtx = await browser.newContext({
				storageState: await ensureAdminAuthState(browser, baseURL),
				baseURL,
			});
			try {
				const page = await adminCtx.newPage();

				// 1) Open /admin/contexts and trigger the legacy
				//    contextGrid's "Create" link action (jQuery UI
				//    AjaxModal). Click() the anchor whose href targets
				//    the createContext component op — labels vary by
				//    locale.
				const resp = await page.goto('/index.php/index/admin/contexts');
				expect(resp?.status()).toBe(200);

				await expect(
					page.locator('#contextGridContainer'),
				).toBeVisible({timeout: 15_000});

				await page
					.locator('#contextGridContainer a[id*="createContext"]')
					.first()
					.click();

				// The AjaxModal injects the Vue form mount-point. Its
				// container is `#editContext` (editContext.tpl#10).
				const form = page.locator('#editContext');
				await expect(form).toBeVisible({timeout: 15_000});
				await expect(
					form.locator('input[name="name-en"]'),
				).toBeVisible({timeout: 15_000});

				// 2) Fill the required fields with an INVALID urlPath
				//    that exercises the regex validator. A space is
				//    rejected by `urlPath.regex` →
				//    admin.contexts.form.pathAlphaNumeric.
				await form.locator('input[name="name-en"]').fill(journalName);
				await form.locator('input[name="acronym-en"]').fill(acronym);
				await form.locator('input[name="contactName"]').fill(contactName);
				await form.locator('input[name="contactEmail"]').fill(contactEmail);
				// Country is non-nullable and runs the `country` Laravel
				// validator (lib/pkp/schemas/context.json#country). The
				// FieldSelect's default empty value fails that check on
				// the server, so we must pick something valid before the
				// first submit — otherwise Country accumulates an error
				// that blocks all subsequent Save clicks (the form's
				// disabled-when-errors-exist gate at FormPage.vue#65).
				await form
					.locator('select[name="country"]')
					.selectOption({label: 'Canada'});
				await form
					.locator('input[name="urlPath"]')
					.fill('invalid path with space');

				// supportedLocales / primaryLocale fields only appear
				// when the site has more than one installed locale (the
				// test installer adds fr_CA — see tools/installTest.php
				// #62). Set them defensively in case the install was
				// run with the locale list extended.
				if (await form.locator('input[name="supportedLocales"]').count()) {
					await form
						.locator('input[name="supportedLocales"][value="en"]')
						.check();
					await form
						.locator('input[name="primaryLocale"][value="en"]')
						.check();
				}

				// Submit → expect inline urlPath error. The pkp-form
				// Save button is the only `type=submit` inside the
				// form scope. Click by accessible role.
				await form.getByRole('button', {name: /Save/i}).click();

				// FieldError renders `<div class="pkpFieldError">`
				// adjacent to the offending input. Anchor on the field
				// row so we don't catch unrelated errors.
				const urlPathField = form
					.locator('.pkpFormField', {has: page.locator('input[name="urlPath"]')});
				await expect(
					urlPathField.locator('.pkpFieldError'),
				).toBeVisible({timeout: 15_000});
				await expect(urlPathField.locator('.pkpFieldError')).toContainText(
					/letters/i,
					{timeout: 10_000},
				);

				// 3) Now try a urlPath that already exists — collide
				//    with the bootstrapped publicknowledge journal —
				//    and assert the duplicate-path validator fires
				//    (admin.contexts.form.pathExists).
				await form.locator('input[name="urlPath"]').fill('publicknowledge');
				await form.getByRole('button', {name: /Save/i}).click();
				await expect(urlPathField.locator('.pkpFieldError')).toContainText(
					/already in use/i,
					{timeout: 15_000},
				);

				// 4) Fix the path and submit successfully. AddContextForm.
				//    success() redirects to /admin/wizard/{id} —
				//    page.waitForURL pins on that landing.
				await form.locator('input[name="urlPath"]').fill(urlPath);
				await form.getByRole('button', {name: /Save/i}).click();

				await page.waitForURL(/\/admin\/wizard\/\d+/, {
					timeout: 30_000,
				});

				// 5) Settings Wizard renders. The contextSettings.tpl
				//    page emits an h1 "Settings Wizard" (manager.settings.wizard).
				await expect(
					page.getByRole('heading', {name: /Settings Wizard/i, level: 1}),
				).toBeVisible({timeout: 15_000});

				// 6) REST sanity check via the admin-scoped contexts
				//    list. The site-context endpoint
				//    `/index/api/v1/contexts` is the one the admin's
				//    session can hit (PKPContextController route group
				//    is gated on ROLE_ID_SITE_ADMIN). NOTE — context
				//    `searchPhrase` only matches against the
				//    `description`, `acronym`, and `abbreviation`
				//    settings (PKPContextQueryBuilder.php#200), NOT
				//    against `urlPath` or `name`. We seeded a unique
				//    acronym, so search by that.
				const apiResp = await adminCtx.request.get(
					`/index.php/index/api/v1/contexts?searchPhrase=${acronym}`,
				);
				expect(apiResp.ok()).toBe(true);
				const body = await apiResp.json();
				expect(body.itemsMax).toBeGreaterThanOrEqual(1);
				expect(
					body.items.some((c) => c.urlPath === urlPath),
				).toBe(true);
			} finally {
				await adminCtx.close();
			}

			// 7) Anonymous reader hits the new journal's homepage. A
			//    fresh-out-of-the-box journal redirects from
			//    /index.php/{path}/ to /index.php/{path}/index but the
			//    final response is 200 with the journal name visible
			//    under the default OJS site skin.
			const anonCtx = await browser.newContext({
				baseURL,
			});
			try {
				const page = await anonCtx.newPage();
				const resp = await page.goto(`/index.php/${urlPath}/`);
				expect(resp?.status()).toBe(200);
				// Journal name appears in the page header. Default
				// OJS theme renders the localized name in the site
				// header anchor; first occurrence is enough.
				await expect(page.getByText(journalName).first()).toBeVisible({
					timeout: 15_000,
				});
			} finally {
				await anonCtx.close();
			}
		},
	);
});

/**
 * Build a unique tag scoped to this worker + test title so that parallel
 * runs don't collide on the global contexts table — each worker gets its
 * own urlPath value.
 *
 * @param {import('@playwright/test').TestInfo} info
 */
function uniqueTag(info) {
	const rand = Math.random().toString(36).slice(2, 6);
	return `${info.parallelIndex}-${rand}`;
}

/**
 * Site admin's storage-state path. The shared ensureAuthStateFor cache is
 * the canonical source — keep storage state warm across runs.
 *
 * @param {import('@playwright/test').Browser} browser
 * @param {string|undefined} baseURL
 */
async function ensureAdminAuthState(browser, baseURL) {
	const {ensureAuthStateFor} = require('../../lib/pkp/playwright/support/auth.js');
	return ensureAuthStateFor(browser, 'admin', {baseURL});
}
