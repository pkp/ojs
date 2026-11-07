// @ts-check
/**
 * @file playwright/tests/users-management.spec.js
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * OJS suite for the feature spec `docs/product/specs/users-management.md`
 * (U53 — Users management): one test per canonical scenario common to the three
 * apps (1–8), run in OJS's own vocabulary — a journal, a Journal Manager, a Site
 * Administrator, the journal's own roles.
 *
 * ## Everything this suite touches, it made
 *
 * These scenarios disable accounts, end roles and MERGE — which deletes an
 * account permanently and cannot be undone. So every account a test acts on is
 * one the test itself seeded, in a scratch journal of its own; the shared
 * `publicknowledge` journal, its 18-user roster and the install's `admin` are
 * never read from, let alone written to. The two scenarios that need an
 * administrator seed a throwaway one (`users[].roles: ['siteAdmin']`) rather
 * than borrowing `admin`, whose account the whole suite depends on.
 *
 * ## What this suite deliberately does NOT cover
 *
 * - **The register's 🐞 findings are never asserted as contract.** A3 (the row
 *   menu's accessible name being a raw locale key) sits on every test here: the
 *   POM finds that button structurally, so no test spells the key out and none
 *   will fail when it gains a name. A4 (the search hint naming "Journal editor"
 *   in every app), A5 and A9 (the OMP/OPS masthead error and the confirmation's
 *   wording — OJS is the app where that flow works) get no assertion at all.
 * - **The open ❓ questions get no assertion either way** — not a coverage gap.
 *   A2's question is whether "Disable User" should be withheld on a row its
 *   dialog can only refuse; scenario 7 therefore asserts what the Actors table
 *   makes contract (Merge user and Login As withheld; Remove User still works
 *   on a partly-administered row) and leaves both the dangling offer and its
 *   in-dialog refusal to the register. A1's question is the email asymmetry
 *   between ending one role and removing them all: scenario 8 asserts the
 *   role-ended email that Rule 8a and Side effects state positively, and no
 *   test asserts that Remove is silent. A6 (the users spreadsheet at a typed
 *   address), A7 (the password re-confirmation, off on a stock install) and A8
 *   (the two lists reading a Site Administrator differently) are untouched.
 * - **Rules with no canonical scenario**: the Hosted Journals list's own filter
 *   and columns (Rule 1b), its Edit User form (Rule 3), the 25-per-page
 *   pagination and the ORCID mark (Rule 1a), the own-row stripping (Rule 1c),
 *   the disable reason surviving an enable-disable-enable cycle (Rule 5 goes as
 *   far as scenario 3 walks it), and masthead display (Rule 8b — its email is
 *   OJS-only and its confirmation is A9's).
 * - **Neighbouring features**: the user edit page itself and the invitations
 *   above the users table (*User invitations*), the roles the forms offer
 *   (*Roles configuration*), "Login As" (*Login & sessions*), the sign-in
 *   refusal's own wording (*Login & sessions* — scenario 3 asserts only that
 *   the refusal quotes the reason typed into the dialog), and the templates of
 *   the welcome and role-ended emails (*Emails management*).
 *
 * ## Locator notes
 *
 * The merge grid's row actions are anchors with no `href`, so they are matched
 * by text, not by the link role. The legacy grids used by Site Administration
 * hide their row controls until the row's expander is clicked. The Users &
 * Roles screen carries more than one table with a "Roles" column, so every
 * locator goes through `UsersAndRolesPage`, which is scoped to one table.
 */

const {test, expect} = require('../support/fixtures.js');
const {LoginPage} = require('../../lib/pkp/playwright/pages/LoginPage.js');
const {BasePage} = require('../../lib/pkp/playwright/pages/BasePage.js');
const {UsersAndRolesPage} = require('../pages/UsersAndRolesPage.js');

/**
 * Per-app, per-worker, per-run tag: one hyphenless alphanumeric token, short
 * enough that `{tag}xxx` still fits a 32-character username.
 */
function tagFor(name, testInfo) {
	return `u53tojs${name}w${testInfo.parallelIndex}${Math.random().toString(36).slice(2, 6)}`;
}

/** The app's short-date rendering, which the suite has already met as ISO. */
function isoDate(offsetDays = 0) {
	const date = new Date();

	date.setDate(date.getDate() + offsetDays);

	return date.toLocaleDateString('en-CA');
}

/** The password rule the seeder applies to every throwaway account. */
function passwordFor(username) {
	return username + username;
}

/**
 * A scratch journal, with a Journal Manager of its own unless the caller says
 * otherwise. Scenario-seeded journals also auto-enrol the install's `admin` as a
 * Journal manager, so every user count here is one more than `users[]`.
 */
async function scratchJournal(ojsApi, tag, {users = [], manager = true, ...rest} = {}) {
	const managerName = `${tag}mgr`;
	const context = await ojsApi.createContext({
		tag,
		name: `Journal ${tag}`,
		users: [
			...(manager
				? [
						{
							username: managerName,
							givenName: 'Mona',
							familyName: 'Manager',
							email: `${managerName}@mail.test`,
							roles: ['manager'],
						},
					]
				: []),
			...users,
		],
		...rest,
	});

	return {
		journal: context.urlPath,
		contextId: context.contextId,
		manager: managerName,
		name: `Journal ${tag}`,
		users: context.users,
	};
}

/**
 * Type credentials into the sign-in screen and stay to read the answer — the
 * shared `LoginPage.login()` waits for a redirect away from `/login`, which a
 * refused sign-in never makes.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} username
 * @param {string} password
 */
async function attemptSignIn(page, username, password) {
	const login = new LoginPage(page);

	await login.goto();
	await login.username.fill(username);
	await login.fillPassword(password);
	await Promise.all([
		page.waitForLoadState('domcontentloaded'),
		login.submitButton.click(),
	]);
}

/**
 * Choose a row action in a legacy PKP grid. The actions live in a control row
 * that follows the data row and stays hidden until the row's expander is
 * clicked, and their anchors carry no `href` — so neither the link role nor a
 * plain row lookup finds them.
 *
 * @param {import('@playwright/test').Locator} row the `tr.gridRow`
 * @param {string} label the action's visible text
 */
async function legacyRowAction(row, label) {
	await row.locator('a.show_extras').click();
	await row
		.locator('xpath=following-sibling::tr[1]')
		.getByText(label, {exact: true})
		.click();
}

/**
 * Fill a legacy rich-text field. TinyMCE renders into an iframe over the
 * textarea, so the text goes to the editor's own body.
 *
 * @param {import('@playwright/test').Locator} scope the form or modal holding it
 * @param {string} text
 */
async function fillRichText(scope, text) {
	const body = scope
		.frameLocator('iframe.tox-edit-area__iframe')
		.locator('body');

	await body.waitFor({timeout: 30_000});
	await body.fill(text);
}

test.describe('Users management', () => {
	test('scenario 1 — a manager finds a user and opens their record', async ({
		ojsApi,
		asUser,
	}, testInfo) => {
		const tag = tagFor('s1', testInfo);
		const target = `${tag}tgt`;
		const targetEmail = `${target}@mail.test`;
		const other = `${tag}oth`;
		const {journal, manager} = await scratchJournal(ojsApi, tag, {
			users: [
				{
					username: target,
					givenName: 'Tara',
					familyName: 'Targetsson',
					email: targetEmail,
					affiliation: 'Redwood University',
					roles: ['sectionEditor'],
				},
				{
					username: other,
					givenName: 'Oona',
					familyName: 'Otherwise',
					email: `${other}@mail.test`,
					roles: ['reader'],
				},
			],
		});

		const page = await (await asUser(manager)).newPage();
		const usersAndRoles = new UsersAndRolesPage(page, journal);
		await usersAndRoles.goto();

		// admin (auto-enrolled), the manager, and the two seeded users.
		await expect(usersAndRoles.usersHeading).toHaveText('Current Users (4)');

		// The search runs on Enter and narrows the list to the matching rows.
		await usersAndRoles.searchUsers('Targetsson');
		await expect(usersAndRoles.userRows).toHaveCount(1);
		await expect(usersAndRoles.usersHeading).toHaveText('Current Users (1)');

		const row = usersAndRoles.userRow(targetEmail);
		await expect(row).toContainText('Tara Targetsson');
		await expect(row).toContainText(targetEmail);
		await expect(row).toContainText('Section editor');
		await expect(row).toContainText(isoDate());
		await expect(row).toContainText('Redwood University');

		// The row's "Edit" leaves the list for the user's own edit page. What that
		// page is headed with belongs to *User invitations* (its Rule 4b); what is
		// this scenario's is that it opened on THIS user.
		await usersAndRoles.userAction(targetEmail, 'Edit');
		await page.waitForURL(/\/management\/settings\/user\/\d+/, {
			timeout: 30_000,
			waitUntil: 'commit',
		});
		await expect(page.getByText(targetEmail)).toBeVisible({timeout: 30_000});
		await expect(page.getByRole('cell', {name: 'Section editor'})).toBeVisible();
	});

	test('scenario 2 — a manager emails a user from the list', async ({
		ojsApi,
		asUser,
		pkpMail,
	}, testInfo) => {
		test.slow();

		const tag = tagFor('s2', testInfo);
		const target = `${tag}tgt`;
		const targetEmail = `${target}@mail.test`;
		const {journal, manager} = await scratchJournal(ojsApi, tag, {
			users: [
				{
					username: target,
					givenName: 'Tara',
					familyName: 'Targetsson',
					email: targetEmail,
					roles: ['reader'],
				},
			],
		});

		const page = await (await asUser(manager)).newPage();
		const usersAndRoles = new UsersAndRolesPage(page, journal);
		await usersAndRoles.goto();

		await usersAndRoles.userAction(targetEmail, 'Email');

		const dialog = page.getByRole('dialog', {name: 'Email'});
		const form = dialog.locator('form#sendEmailForm');
		await expect(form).toBeVisible({timeout: 30_000});

		// "To" is fixed: it names the recipient and cannot be edited.
		const to = form.locator('input#user, input[id^="user-"]').first();
		await expect(to).toHaveValue(`Tara Targetsson <${targetEmail}>`);
		await expect(to).toBeDisabled();

		await form.locator('input[name="subject"]').fill(`Subject ${tag}`);
		await fillRichText(form, `Body ${tag}`);
		await form.getByRole('button', {name: 'Send Email'}).click();
		await expect(form).toHaveCount(0, {timeout: 30_000});

		const [message] = await pkpMail.find({to: targetEmail, contains: tag});
		expect(message.Subject).toBe(`Subject ${tag}`);
		expect(message.From.Address).toBe(`${manager}@mail.test`);

		const full = await pkpMail.fullMessage(message.ID);
		expect(full.HTML + full.Text).toContain(`Body ${tag}`);
	});

	test('scenario 3 — a manager disables an account, then re-enables it', async ({
		browser,
		ojsApi,
		asUser,
	}, testInfo) => {
		test.slow();

		const tag = tagFor('s3', testInfo);
		const target = `${tag}tgt`;
		const targetEmail = `${target}@mail.test`;
		const reason = `Suspicious activity ${tag}`;
		const {journal, manager} = await scratchJournal(ojsApi, tag, {
			users: [
				{
					username: target,
					givenName: 'Tara',
					familyName: 'Targetsson',
					email: targetEmail,
					roles: ['sectionEditor'],
				},
			],
		});

		const page = await (await asUser(manager)).newPage();
		const usersAndRoles = new UsersAndRolesPage(page, journal);
		await usersAndRoles.goto();
		await expect(usersAndRoles.disabledMark(targetEmail)).toHaveCount(0);

		await usersAndRoles.userAction(targetEmail, 'Disable User');

		const disableDialog = page.getByRole('dialog', {name: 'Disable Tara Targetsson'});
		await expect(disableDialog).toContainText('Current Roles : Section editor', {
			timeout: 30_000,
		});

		const disableForm = disableDialog.locator('form#userDisableForm');
		await expect(disableForm.getByText('Reason for disabling user')).toBeVisible();
		await disableForm.locator('textarea[name="disableReason"]').fill(reason);
		await disableForm.getByRole('button', {name: 'OK', exact: true}).click();
		await expect(disableForm).toHaveCount(0, {timeout: 30_000});

		// The row is marked, and the action has flipped.
		await expect(usersAndRoles.disabledMark(targetEmail)).toHaveCount(1);
		expect(await usersAndRoles.userActionLabels(targetEmail)).toContain('Enable User');

		// The disabled person cannot sign in, and is told why in the words the
		// manager typed.
		const anonymous = await browser.newContext({
			storageState: {cookies: [], origins: []},
		});
		const targetPage = await anonymous.newPage();
		await attemptSignIn(targetPage, target, passwordFor(target));
		await expect(targetPage).toHaveURL(/\/login/);
		await expect(targetPage.getByText(reason)).toBeVisible();

		// Enabling offers the reason back, pre-filled, and restores sign-in.
		await usersAndRoles.userAction(targetEmail, 'Enable User');

		const enableDialog = page.getByRole('dialog', {name: 'Enable Tara Targetsson'});
		const enableForm = enableDialog.locator('form#userDisableForm');
		await expect(enableForm.locator('textarea[name="disableReason"]')).toHaveValue(
			reason,
			{timeout: 30_000},
		);
		await expect(enableForm.getByText('Reason for enabling user')).toBeVisible();
		await enableForm.getByRole('button', {name: 'OK', exact: true}).click();
		await expect(enableForm).toHaveCount(0, {timeout: 30_000});

		await expect(usersAndRoles.disabledMark(targetEmail)).toHaveCount(0);

		await new LoginPage(targetPage).login(target, passwordFor(target));
		await expect(targetPage).not.toHaveURL(/\/login/);
		await anonymous.close();
	});

	test('scenario 4 — a manager removes a user from the journal', async ({
		browser,
		ojsApi,
		asUser,
	}, testInfo) => {
		test.slow();

		const tag = tagFor('s4', testInfo);
		const target = `${tag}tgt`;
		const targetEmail = `${target}@mail.test`;
		const {journal, manager} = await scratchJournal(ojsApi, tag, {
			users: [
				{
					username: target,
					givenName: 'Tara',
					familyName: 'Targetsson',
					email: targetEmail,
					roles: ['sectionEditor'],
				},
			],
		});

		const page = await (await asUser(manager)).newPage();
		const usersAndRoles = new UsersAndRolesPage(page, journal);
		await usersAndRoles.goto();

		const countBefore = await usersAndRoles.userCount();
		const row = usersAndRoles.userRow(targetEmail);
		await expect(row).toContainText('Section editor');

		await usersAndRoles.userAction(targetEmail, 'Remove User');

		const confirmation = page.getByRole('dialog', {name: 'Remove'});
		await expect(confirmation).toContainText(
			'Remove this user from this journal? This action will unenroll the user ' +
				'from all roles within this journal.',
			{timeout: 30_000},
		);
		await confirmation.getByRole('button', {name: 'OK', exact: true}).click();

		// The row stays; its roles and start dates do not, and the count is
		// unchanged — the account survived, only its enrolments ended.
		await expect(row.getByRole('cell').nth(2)).toHaveText('', {timeout: 30_000});
		await expect(row.getByRole('cell').nth(3)).toHaveText('');
		expect(await usersAndRoles.userCount()).toBe(countBefore);

		// With no active role left here, the action is no longer offered.
		expect(await usersAndRoles.userActionLabels(targetEmail)).not.toContain(
			'Remove User',
		);

		// The account still signs in.
		const anonymous = await browser.newContext({
			storageState: {cookies: [], origins: []},
		});
		const targetPage = await anonymous.newPage();
		await new LoginPage(targetPage).login(target, passwordFor(target));
		await expect(targetPage).not.toHaveURL(/\/login/);
		await anonymous.close();
	});

	test('scenario 5 — an administrator merges a duplicate account away', async ({
		browser,
		ojsApi,
		asUser,
	}, testInfo) => {
		test.slow();

		const tag = tagFor('s5', testInfo);
		const admin = `${tag}adm`;
		const duplicate = `${tag}dup`;
		const duplicateEmail = `${duplicate}@mail.test`;
		const survivor = `${tag}srv`;
		const survivorEmail = `${survivor}@mail.test`;
		const {journal} = await scratchJournal(ojsApi, tag, {
			manager: false,
			users: [
				// A throwaway administrator: the install's own `admin` is the only
				// other one, and this suite must leave it exactly as it found it.
				{username: admin, givenName: 'Ada', familyName: 'Adminson', roles: ['siteAdmin']},
				{
					username: duplicate,
					givenName: 'Dana',
					familyName: 'Duplicate',
					email: duplicateEmail,
					roles: ['author'],
				},
				{
					username: survivor,
					givenName: 'Sara',
					familyName: 'Survivor',
					email: survivorEmail,
					roles: ['reader'],
				},
			],
		});

		const page = await (await asUser(admin)).newPage();
		const usersAndRoles = new UsersAndRolesPage(page, journal);
		await usersAndRoles.goto();

		const countBefore = await usersAndRoles.userCount();
		await expect(usersAndRoles.userRow(survivorEmail)).toContainText('Reader');

		await usersAndRoles.userAction(duplicateEmail, 'Merge user');

		// A second user list opens, titled "Merge into this User"; the surviving
		// account is picked from it.
		const mergeDialog = page.getByRole('dialog', {name: 'Merge user'});
		await expect(
			mergeDialog.getByRole('heading', {name: 'Merge into this User'}),
		).toBeVisible({timeout: 30_000});

		// The duplicate is listed there too, with only its own action withheld —
		// which is why its row has no expander at all.
		const duplicateRow = mergeDialog.locator('tr.gridRow').filter({hasText: duplicate});
		await expect(duplicateRow).toHaveCount(1);
		await expect(duplicateRow.locator('a.show_extras')).toHaveCount(0);

		await legacyRowAction(
			mergeDialog.locator('tr.gridRow').filter({hasText: survivor}),
			'Merge into this User',
		);

		const confirmation = page.getByRole('dialog').filter({
			hasText: 'will not exist afterwards',
		});
		await expect(confirmation).toContainText(
			`Are you sure you wish to merge the account with the username "${duplicate}" ` +
				`into the account with the username "${survivor}"? The account with the ` +
				`username "${duplicate}" will not exist afterwards. This action is not ` +
				'reversible.',
			{timeout: 30_000},
		);
		await confirmation.getByRole('button', {name: 'OK', exact: true}).click();

		// The duplicate is gone from the list at once and the count drops.
		await usersAndRoles.goto();
		await expect(usersAndRoles.userRow(duplicateEmail)).toHaveCount(0);
		expect(await usersAndRoles.userCount()).toBe(countBefore - 1);

		// Its role now belongs to the survivor.
		const survivorRowAfter = usersAndRoles.userRow(survivorEmail);
		await expect(survivorRowAfter).toContainText('Reader');
		await expect(survivorRowAfter).toContainText('Author');

		// And its username is refused at sign-in.
		const anonymous = await browser.newContext({
			storageState: {cookies: [], origins: []},
		});
		const duplicatePage = await anonymous.newPage();
		await attemptSignIn(duplicatePage, duplicate, passwordFor(duplicate));
		await expect(duplicatePage).toHaveURL(/\/login/);
		await expect(duplicatePage.locator('.pkp_form_error')).toBeVisible();
		await anonymous.close();
	});

	test('scenario 6 — an administrator adds a user from Site Administration', async ({
		ojsApi,
		asUser,
		pkpMail,
	}, testInfo) => {
		test.slow();

		const tag = tagFor('s6', testInfo);
		const admin = `${tag}adm`;
		const newcomer = `${tag}new`;
		const newcomerEmail = `${newcomer}@mail.test`;
		const password = `${tag}Pw1!`;
		const {journal, name} = await scratchJournal(ojsApi, tag, {
			manager: false,
			users: [
				{username: admin, givenName: 'Ada', familyName: 'Adminson', roles: ['siteAdmin']},
			],
		});

		const page = await (await asUser(admin)).newPage();

		// Administration → Hosted Journals. A legacy grid: the row's controls are
		// hidden until its expander is clicked.
		await page.goto(BasePage.siteUrl('/admin/contexts'));

		const journalRow = page.locator('tr.gridRow').filter({hasText: name});
		await expect(journalRow).toHaveCount(1, {timeout: 30_000});
		await legacyRowAction(journalRow, 'Settings wizard');

		await page.waitForURL(/\/admin\/wizard\//, {timeout: 30_000, waitUntil: 'commit'});

		// The journal's own settings pages, "Users" tab.
		await page.locator('#users-button').click();

		const grid = page.locator('#userGridContainer');
		await expect(grid.getByText('Current Users')).toBeVisible({timeout: 30_000});
		await grid.getByText('Add User', {exact: true}).click();

		const addDialog = page.getByRole('dialog', {name: 'Add User'});
		const detailsForm = addDialog.locator('form#userDetailsForm');
		await expect(detailsForm).toBeVisible({timeout: 30_000});
		await expect(detailsForm.getByText('Step #1: Fill in User Details')).toBeVisible();

		await detailsForm.locator('input[name^="givenName"]').first().fill('Ned');
		await detailsForm.locator('input[name="username"]').fill(newcomer);
		await detailsForm.locator('input[name="email"]').fill(newcomerEmail);
		await detailsForm.locator('input[name="password"]').fill(password);
		await detailsForm.locator('input[name="password2"]').fill(password);
		await detailsForm.locator('input[name="sendNotify"]').check();
		await detailsForm.getByRole('button', {name: 'OK', exact: true}).click();

		// Step 2 replaces the form in place, headed with the new user's name.
		const roleForm = addDialog.locator('form#userRoleForm');
		await expect(
			roleForm.getByRole('heading', {name: 'Step #2: Add User Roles to Ned'}),
		).toBeVisible({timeout: 30_000});

		// Both lists on this form carry the same role labels; the roles list comes
		// first, the masthead list second.
		await roleForm.getByRole('checkbox', {name: 'Author', exact: true}).first().check();
		await roleForm.getByRole('button', {name: 'Save', exact: true}).click();
		await expect(roleForm).toHaveCount(0, {timeout: 30_000});

		// The welcome email left as soon as the details were saved.
		const [message] = await pkpMail.find({
			to: newcomerEmail,
			subject: 'Journal Registration',
			timeoutMs: 30_000,
		});
		const full = await pkpMail.fullMessage(message.ID);
		expect(full.HTML + full.Text).toContain(newcomer);

		// And the journal's own users list now holds the account, with the role.
		const usersAndRoles = new UsersAndRolesPage(page, journal);
		await usersAndRoles.goto();
		await usersAndRoles.searchUsers(newcomer);

		const row = usersAndRoles.userRow(newcomerEmail);
		await expect(row).toContainText('Ned');
		await expect(row).toContainText('Author');
	});

	test('scenario 7 — a manager meets a user who belongs somewhere else', async ({
		ojsApi,
		asUser,
	}, testInfo) => {
		test.slow();

		const tag = tagFor('s7', testInfo);
		const shared = `${tag}shr`;
		const sharedEmail = `${shared}@mail.test`;
		const local = `${tag}loc`;
		const localEmail = `${local}@mail.test`;

		// The journal the manager runs: a user of its own (the control) and a user
		// who also belongs to a second journal.
		const home = await scratchJournal(ojsApi, `${tag}a`, {
			users: [
				{
					username: shared,
					givenName: 'Shea',
					familyName: 'Shared',
					email: sharedEmail,
					roles: ['sectionEditor'],
				},
				{
					username: local,
					givenName: 'Lena',
					familyName: 'Local',
					email: localEmail,
					roles: ['sectionEditor'],
				},
			],
		});
		// The second journal, with a manager of its own to read it back with.
		const elsewhere = await scratchJournal(ojsApi, `${tag}b`, {
			users: [{username: shared, roles: ['reader']}],
		});

		const page = await (await asUser(home.manager)).newPage();
		const usersAndRoles = new UsersAndRolesPage(page, home.journal);
		await usersAndRoles.goto();

		// The control: a user this manager fully administers is offered everything.
		const localActions = await usersAndRoles.userActionLabels(localEmail);
		expect(localActions).toContain('Merge user');
		expect(localActions).toContain('Login As');

		// The shared user's row withholds exactly those two.
		const sharedActions = await usersAndRoles.userActionLabels(sharedEmail);
		expect(sharedActions).not.toContain('Merge user');
		expect(sharedActions).not.toContain('Login As');
		expect(sharedActions).toContain('Edit');
		expect(sharedActions).toContain('Email');
		expect(sharedActions).toContain('Remove User');

		// "Remove User" only needs this journal's say-so, so it still works here.
		await usersAndRoles.userAction(sharedEmail, 'Remove User');

		const confirmation = page.getByRole('dialog', {name: 'Remove'});
		await expect(confirmation).toContainText('Remove this user from this journal?', {
			timeout: 30_000,
		});
		await confirmation.getByRole('button', {name: 'OK', exact: true}).click();
		await expect(
			usersAndRoles.userRow(sharedEmail).getByRole('cell').nth(2),
		).toHaveText('', {timeout: 30_000});

		// The other journal's role is untouched.
		const elsewherePage = await (await asUser(elsewhere.manager)).newPage();
		const elsewhereUsers = new UsersAndRolesPage(elsewherePage, elsewhere.journal);
		await elsewhereUsers.goto();
		await expect(elsewhereUsers.userRow(sharedEmail)).toContainText('Reader');
	});

	test('scenario 8 — one role ended, then all of them', async ({
		ojsApi,
		asUser,
		pkpMail,
	}, testInfo) => {
		test.slow();

		const tag = tagFor('s8', testInfo);
		const target = `${tag}tgt`;
		const targetEmail = `${target}@mail.test`;
		const {journal, manager} = await scratchJournal(ojsApi, tag, {
			users: [
				{
					username: target,
					givenName: 'Tara',
					familyName: 'Targetsson',
					email: targetEmail,
					roles: ['sectionEditor', 'reader'],
				},
			],
		});

		const page = await (await asUser(manager)).newPage();
		const usersAndRoles = new UsersAndRolesPage(page, journal);
		await usersAndRoles.goto();

		const row = usersAndRoles.userRow(targetEmail);
		await expect(row).toContainText('Section editor');
		await expect(row).toContainText('Reader');

		// The row's "Edit" opens the user's own page, where one role is ended.
		await usersAndRoles.userAction(targetEmail, 'Edit');
		await page.waitForURL(/\/management\/settings\/user\/\d+/, {
			timeout: 30_000,
			waitUntil: 'commit',
		});

		const roleRow = page
			.getByRole('row')
			.filter({hasText: 'Section editor'})
			.first();
		await expect(roleRow).toBeVisible({timeout: 30_000});
		await roleRow.getByRole('button', {name: 'Remove Role'}).click();

		const confirmation = page.getByRole('dialog', {name: 'Remove Role'});
		await expect(confirmation).toContainText(
			'Are you sure you want to remove this role?',
			{timeout: 30_000},
		);
		await confirmation
			.getByRole('button', {name: 'Remove Role', exact: true})
			.click();
		await expect(roleRow).toContainText('User Removed From Role', {timeout: 30_000});

		// Ending one role always tells the person so.
		const [message] = await pkpMail.find({
			to: targetEmail,
			subject: 'You have been removed from a role',
			timeoutMs: 30_000,
		});
		expect(message.Subject).toBe('You have been removed from a role');

		// Back on the list, "Remove User" ends what is left in one stroke.
		await usersAndRoles.goto();
		await expect(usersAndRoles.userRow(targetEmail)).toContainText('Reader');
		await expect(usersAndRoles.userRow(targetEmail)).not.toContainText(
			'Section editor',
		);

		await usersAndRoles.userAction(targetEmail, 'Remove User');

		const removeConfirmation = page.getByRole('dialog', {name: 'Remove'});
		await expect(removeConfirmation).toContainText(
			'This action will unenroll the user from all roles within this journal.',
			{timeout: 30_000},
		);
		await removeConfirmation.getByRole('button', {name: 'OK', exact: true}).click();

		await expect(
			usersAndRoles.userRow(targetEmail).getByRole('cell').nth(2),
		).toHaveText('', {timeout: 30_000});
	});
});
