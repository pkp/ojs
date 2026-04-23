// @ts-check
const {test, expect} = require('../support/fixtures.js');
const {ensureAuthStateFor} = require('../../lib/pkp/playwright/support/auth.js');

/**
 * Issues — row #7 in docs/e2e-playwright-migration.md.
 *
 * Ports cypress/tests/data/10-ApplicationSetup/50-CreateIssues.cy.js.
 *
 * Issues are an OJS-only concept, so the spec lives at the OJS-root
 * playwright/ tree. Each test seeds its own E0 scratch journal (via
 * pkpApi.createJournal) so the bootstrapped publicknowledge journal's
 * issue grid stays untouched.
 *
 * The issue management UI on /{path}/manageIssues is a Vue tabs shell
 * wrapping two legacy pkp_controllers jQuery grids — FutureIssueGrid +
 * BackIssueGrid. Create / Edit / Publish open in the same
 * DialogContent side-modal that subscription-config.spec.js uses;
 * Unpublish and "Set current issue" open reka-ui confirmation
 * dialogs with OK / Cancel.
 *
 * UI notes:
 *   - Form input ids are suffixed with a per-render hash (e.g.
 *     `volume-69ea...`), so selectors use `input[name=...]` instead
 *     of `input#...`. The show{Volume,Number,Year,Title} checkboxes
 *     use plain ids (no hash).
 *   - IssueForm validates that `showTitle`→"title must be set". A
 *     new issue has no title, so every create flow must uncheck
 *     `#showTitle` before submitting (matching the Cypress source).
 *   - The grid renders each row as `tr.gridRow` + sibling
 *     `tr.row_controls`, with the row_controls tr hidden until the
 *     per-row "Settings" glyph (`a.show_extras`) is clicked. Each
 *     row has its own toggle; after one click the class flips to
 *     `hide_extras`, so expanding every row requires looping while
 *     any `a.show_extras` remains.
 *   - `setCurrentIssue` is only rendered as a row action on a
 *     published issue that is NOT already the current issue. On
 *     first publish Repo::issue::updateCurrent auto-promotes the
 *     newly-published issue, so to test set-current we publish a
 *     second issue and then set the first back as current.
 *
 * Scope deviations:
 *   - The Cypress source is a pure data-seeding spec (creates two
 *     future issues, publishes one). Row #7 asks for edit
 *     volume/number/year, publish, unpublish, set-current, reader
 *     archive. We combine those into the three tests below.
 *   - Reader-side "TOC renders with section grouping" assertion is
 *     reduced to "issue public view page loads at 200 and shows the
 *     volume / number / year string". With no articles assigned
 *     (E5 Galleys + row #30 Issue assignment), a TOC assertion
 *     would require seeding published content — out of scope here.
 */

function uniqueTag() {
	const workerIndex = test.info().parallelIndex;
	const suffix = Math.random().toString(36).slice(2, 8);
	return `iss-w${workerIndex}-${suffix}`;
}

/**
 * Visit the Issue Management page. The page is the Future / Back
 * tab shell; default tab is Future.
 */
async function openManageIssues(page, journalPath) {
	await page.goto(`/index.php/${journalPath}/manageIssues`);
	await expect(
		page.locator(
			'a[id^="component-grid-issues-futureissuegrid-addIssue-button-"]',
		),
	).toBeVisible();
}

/**
 * Open Future tab (no-op if already open).
 */
async function openFutureTab(page) {
	await page.locator('#future-button').click();
	await expect(
		page.locator(
			'a[id^="component-grid-issues-futureissuegrid-addIssue-button-"]',
		),
	).toBeVisible();
}

/**
 * Open Back tab.
 */
async function openBackTab(page) {
	await page.locator('#back-button').click();
	await expect(page.locator('#backIssuesGridContainer')).toBeVisible();
}

/**
 * Fill and submit the IssueForm in the side-modal. Uncheck
 * `showTitle` because the form validates that a shown title is
 * non-empty (the new issue has no localized title).
 */
async function fillIssueForm(page, {volume, number, year}) {
	const form = page.locator('form#issueForm');
	await expect(form.locator('input[name="volume"]')).toBeAttached();
	await form.locator('input[name="volume"]').fill(String(volume));
	await form.locator('input[name="number"]').fill(String(number));
	await form.locator('input[name="year"]').fill(String(year));
	await form.locator('input#showTitle').uncheck({force: true});
	await form.locator('button[id^="submitFormButton"]').click();
	await expect(form).toHaveCount(0, {timeout: 15_000});
}

/**
 * Click Create Issue on the Future tab and fill the form.
 */
async function createFutureIssue(page, data) {
	await page
		.locator(
			'a[id^="component-grid-issues-futureissuegrid-addIssue-button-"]',
		)
		.click();
	await fillIssueForm(page, data);
}

/**
 * Expand every row's settings glyph in the given grid so the
 * `tr.row_controls` siblings become visible. Each row has its own
 * `a.show_extras` toggle; clicking flips it to `hide_extras`.
 */
async function expandAllRows(page, gridSelector) {
	// Loop until no show_extras remain; each click consumes one.
	/* eslint-disable no-await-in-loop */
	while ((await page.locator(`${gridSelector} a.show_extras`).count()) > 0) {
		await page.locator(`${gridSelector} a.show_extras`).first().click();
	}
	/* eslint-enable no-await-in-loop */
}

/**
 * Locate a row by its volume/number/year identification text in a
 * given grid container.
 */
function findRow(page, gridSelector, {volume, number, year}) {
	return page.locator(`${gridSelector} tr.gridRow`, {
		hasText: `Vol. ${volume} No. ${number} (${year})`,
	});
}

/**
 * Publish the first (and at this point only) future issue via the
 * per-row publish action. The action opens an AjaxModal form with
 * the "send issue notification" checkbox; submitting confirms.
 */
async function publishFirstFutureIssue(page) {
	await expandAllRows(page, '#futureIssuesGridContainer');
	await page
		.locator(
			'#futureIssuesGridContainer tr.row_controls a[id*="-publish-button-"]',
		)
		.first()
		.click();
	const publishForm = page.locator('form#assignPublicIdentifierForm');
	await expect(publishForm).toBeVisible();
	await publishForm.locator('button[id^="submitFormButton"]').click();
	await expect(publishForm).toHaveCount(0, {timeout: 15_000});
}

test.describe('Issues', () => {
	test(
		'manager creates a future issue, edits volume/number/year, and the metadata persists',
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
				reducedMotion: 'reduce',
			});
			try {
				const page = await ctx.newPage();
				await openManageIssues(page, context.path);

				// Create initial future issue with volume/number/year.
				await createFutureIssue(page, {volume: 1, number: 1, year: 2020});
				const firstRow = findRow(page, '#futureIssuesGridContainer', {
					volume: 1,
					number: 1,
					year: 2020,
				});
				await expect(firstRow).toBeVisible();

				// Edit the same issue and bump each field. The per-row
				// Edit action opens a side-modal with a jQuery-UI tabset
				// (#editIssueTabs) — Table of Contents / Issue Data /
				// Issue Galleys. The volume/number/year form lives under
				// the "Issue Data" tab and is lazy-loaded from
				// `editIssueData`.
				await expandAllRows(page, '#futureIssuesGridContainer');
				await page
					.locator(
						'#futureIssuesGridContainer tr.row_controls a[id*="futureissuegrid-row-"][id*="-edit-button-"]',
					)
					.first()
					.click();
				await expect(page.locator('#editIssueTabs')).toBeVisible();
				await page
					.locator('#editIssueTabs a', {hasText: 'Issue Data'})
					.click();
				const form = page.locator('form#issueForm');
				await expect(form.locator('input[name="volume"]')).toBeAttached();
				await form.locator('input[name="volume"]').fill('3');
				await form.locator('input[name="number"]').fill('4');
				await form.locator('input[name="year"]').fill('2023');
				await form.locator('button[id^="submitFormButton"]').click();
				// AjaxFormHandler closes the whole Edit side-modal on
				// successful save — wait for the tabset to detach before
				// re-querying the grid.
				await expect(page.locator('#editIssueTabs')).toHaveCount(0, {
					timeout: 15_000,
				});

				// The grid refreshes in place; the edited row must show
				// the new identification.
				await expect(
					findRow(page, '#futureIssuesGridContainer', {
						volume: 3,
						number: 4,
						year: 2023,
					}),
				).toBeVisible();

				// Reload the whole page and re-check — the updated
				// identification must persist across reloads.
				await openManageIssues(page, context.path);
				await expect(
					findRow(page, '#futureIssuesGridContainer', {
						volume: 3,
						number: 4,
						year: 2023,
					}),
				).toBeVisible();
			} finally {
				await ctx.close();
			}
		},
	);

	test(
		'manager publishes, swaps current, and unpublishes — state transitions show in the grid',
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
				reducedMotion: 'reduce',
			});
			try {
				const page = await ctx.newPage();
				await openManageIssues(page, context.path);

				// Seed two future issues.
				await createFutureIssue(page, {volume: 1, number: 1, year: 2021});
				await createFutureIssue(page, {volume: 2, number: 1, year: 2022});

				// Publish the first one — Repo::issue::updateCurrent
				// auto-promotes it as current.
				await publishFirstFutureIssue(page);

				// After one publish: the remaining future row is the
				// second issue. Publish it too.
				await publishFirstFutureIssue(page);

				// Both issues are now on the Back tab. Verify they
				// appear, and that the non-current row exposes the
				// setCurrentIssue action.
				await openBackTab(page);
				await expect(
					findRow(page, '#backIssuesGridContainer', {
						volume: 1,
						number: 1,
						year: 2021,
					}),
				).toBeVisible();
				await expect(
					findRow(page, '#backIssuesGridContainer', {
						volume: 2,
						number: 1,
						year: 2022,
					}),
				).toBeVisible();

				await expandAllRows(page, '#backIssuesGridContainer');
				// The second publish auto-promoted the 2022 issue to
				// current, so the 2021 row should have setCurrentIssue
				// and the 2022 row should not.
				const row2021 = findRow(page, '#backIssuesGridContainer', {
					volume: 1,
					number: 1,
					year: 2021,
				});
				const row2021Id = await row2021.first().getAttribute('id');
				const row2022 = findRow(page, '#backIssuesGridContainer', {
					volume: 2,
					number: 1,
					year: 2022,
				});
				const row2022Id = await row2022.first().getAttribute('id');

				await expect(
					page.locator(
						`a[id^="${row2021Id}-setCurrentIssue-button-"]`,
					),
				).toHaveCount(1);
				await expect(
					page.locator(
						`a[id^="${row2022Id}-setCurrentIssue-button-"]`,
					),
				).toHaveCount(0);

				// Set 2021 as current.
				await page
					.locator(`a[id^="${row2021Id}-setCurrentIssue-button-"]`)
					.click();
				const setCurrentDialog = page.locator('[role="dialog"]', {
					hasText: 'Are you sure you want to set this issue as current',
				});
				await expect(setCurrentDialog).toBeVisible();
				await setCurrentDialog.getByRole('button', {name: 'OK'}).click();
				await expect(setCurrentDialog).toHaveCount(0, {timeout: 15_000});

				// The setCurrentIssue action should flip — now on 2022,
				// not on 2021.
				await openBackTab(page);
				await expandAllRows(page, '#backIssuesGridContainer');
				await expect(
					page.locator(
						`a[id^="${row2021Id}-setCurrentIssue-button-"]`,
					),
				).toHaveCount(0);
				await expect(
					page.locator(
						`a[id^="${row2022Id}-setCurrentIssue-button-"]`,
					),
				).toHaveCount(1);

				// Unpublish the 2022 issue — it returns to the Future
				// tab.
				await page
					.locator(`a[id^="${row2022Id}-unpublish-button-"]`)
					.click();
				const unpublishDialog = page.locator('[role="dialog"]', {
					hasText: 'Are you sure you want to unpublish this published issue',
				});
				await expect(unpublishDialog).toBeVisible();
				await unpublishDialog.getByRole('button', {name: 'OK'}).click();
				await expect(unpublishDialog).toHaveCount(0, {timeout: 15_000});

				// Back tab should no longer have the 2022 row.
				await expect(
					findRow(page, '#backIssuesGridContainer', {
						volume: 2,
						number: 1,
						year: 2022,
					}),
				).toHaveCount(0, {timeout: 15_000});

				// Future tab should now contain the 2022 row.
				await openFutureTab(page);
				await expect(
					findRow(page, '#futureIssuesGridContainer', {
						volume: 2,
						number: 1,
						year: 2022,
					}),
				).toBeVisible();
			} finally {
				await ctx.close();
			}
		},
	);

	test(
		'published issue appears on the public archive and its public view page loads',
		{tag: '@regression'},
		async ({pkpApi, browser, baseURL}) => {
			const tag = uniqueTag();
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});

			// Editor context: create + publish one issue.
			const editorCtx = await browser.newContext({
				storageState: await ensureAuthStateFor(browser, 'dbarnes', {baseURL}),
				baseURL,
				reducedMotion: 'reduce',
			});
			try {
				const editorPage = await editorCtx.newPage();
				await openManageIssues(editorPage, context.path);
				await createFutureIssue(editorPage, {
					volume: 7,
					number: 2,
					year: 2024,
				});
				await publishFirstFutureIssue(editorPage);
			} finally {
				await editorCtx.close();
			}

			// Anonymous reader context: no storageState.
			const anon = await browser.newContext({
				baseURL,
				reducedMotion: 'reduce',
			});
			try {
				const anonPage = await anon.newPage();

				// Archive page lists the published issue.
				const archiveResp = await anonPage.goto(
					`/index.php/${context.path}/issue/archive`,
				);
				expect(archiveResp?.status()).toBe(200);
				await expect(
					anonPage.getByText('Vol. 7 No. 2 (2024)'),
				).toBeVisible();

				// Clicking the archive link should load the issue's
				// public view page (h1 shows the issue identification).
				await anonPage.getByRole('link', {name: /Vol\. 7/}).first().click();
				await expect(
					anonPage.locator('h1', {hasText: 'Vol. 7 No. 2 (2024)'}),
				).toBeVisible();
			} finally {
				await anon.close();
			}
		},
	);
});
