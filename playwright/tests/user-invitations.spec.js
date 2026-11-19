// @ts-check
/**
 * @file playwright/tests/user-invitations.spec.js
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * OJS suite for the feature spec `docs/product/specs/user-invitations.md`
 * (U6 — User invitations): one test per canonical scenario common to the three
 * apps (1–7), run in OJS's own vocabulary — a journal, a Journal Manager, the
 * journal's own roles — in a scratch journal of its own so the shared
 * `publicknowledge` seed and its roster stay untouched.
 *
 * Every test seeds its journal, its throwaway manager and (where the recipient's
 * journey is the subject rather than the sending) its invitations through the
 * scenario endpoint, and drives the screens for the behaviour under test.
 *
 * ## What this suite deliberately does NOT cover
 *
 * - **Scenario 8 (OPS)** — an app-specific scenario about OPS's email-templates
 *   screen; it belongs to the OPS suite.
 * - **The register's 🐞 findings are never asserted as contract.** A12
 *   (acceptance ending at a sign-in page) and A14 (a superseded email's links
 *   landing on a bare not-found page) sit directly on scenarios 1, 2 and 5: each
 *   test asserts the part that is contract — the account exists and the roles are
 *   attached; the superseded link no longer opens the acceptance flow — and
 *   leaves the defective landing to the register entry, so the tests keep passing
 *   when the defects are fixed. A5 (raw locale keys in the step list's accessible
 *   name and the acceptance flow's error banner), A6 (an unexplained modal on a
 *   refused send), A7 (the "journal manager" wording on other apps), A8 (a
 *   Section Editor's typed-in wizard), A9 (the users-table wizard's missing
 *   heading and dead Cancel), A10 (inviting a disabled user), A11 (a newcomer
 *   greeted by email address) and A15 (a link naming no invitation) get no
 *   assertion at all.
 * - **The open ❓ questions** — A1 (Section Editors and Assistants admitted by
 *   the machinery), A2 (the older Users & Roles address), A3 (role removal acting
 *   before the invitation is sent), A4 (Site Administrator access per app), A13
 *   (a verified-ORCID existing user) and OPS1 — are not coverage gaps and get no
 *   assertion either way.
 * - **Rules with no canonical scenario**: the wizard opened preloaded from the
 *   users table (Rule 4b), a disabled invitee (Rule 8), the details step's
 *   immediate role and masthead changes (Rule 9 — *Users management*'s
 *   mechanics), the Invitations table's five-per-page pagination (Rule 3), the
 *   ORCID step (Rule 10 — ORCID is not configured on the test install), the
 *   nightly deletion of expired invitations (Rule 2 — scheduled tasks do not run
 *   in the test environment; scenario 7 asserts the listing scope and the link
 *   instead), and the field-level refusals of the Fields & validation table
 *   (taken username, short password, unticked consent, empty search).
 * - **Neighbouring features**: the users table and its role mechanics (*Users
 *   management*), the roles the wizard offers (*Roles configuration*), the
 *   invitation email's template (*Emails management*), self-registration
 *   (*Registration & account validation*) and ORCID verification (*ORCID
 *   integration*). This suite drives them only as far as an invitation's own
 *   behaviour needs.
 */

const {test, expect} = require('../support/fixtures.js');
const {LoginPage} = require('../../lib/pkp/playwright/pages/LoginPage.js');
const {UsersAndRolesPage} = require('../pages/UsersAndRolesPage.js');
const {AcceptInvitationPage} = require('../pages/AcceptInvitationPage.js');

/** Per-app, per-worker, per-run tag: one hyphenless alphanumeric token. */
function tagFor(name, testInfo) {
	return `u6tojs${name}w${testInfo.parallelIndex}${Math.random().toString(36).slice(2, 6)}`;
}

/** A date the wizard's date field and the app's short-date display agree on. */
function isoDate(offsetDays = 0) {
	const date = new Date();

	date.setDate(date.getDate() + offsetDays);

	return date.toLocaleDateString('en-CA');
}

/** A scratch journal with a Journal Manager of its own. */
async function scratchJournal(ojsApi, tag, spec = {}) {
	const context = await ojsApi.createContext({
		tag,
		users: [
			{
				username: `${tag}mgr`,
				givenName: 'Mona',
				familyName: 'Manager',
				roles: ['manager'],
			},
			...(spec.users ?? []),
		],
		...(spec.invitations ? {invitations: spec.invitations} : {}),
	});

	return {journal: context.urlPath, manager: `${tag}mgr`, invitations: context.invitations};
}

/**
 * The "Accept Invitation" link of the invitation email — the recipient's own way
 * in, for the scenarios where the manager sent the invitation through the wizard
 * and no seeded key exists. Scoped by recipient AND this run's tag: one Mailpit
 * serves every worker and all three app fleets.
 */
async function acceptUrlFromMail(pkpMail, {to, tag}) {
	const [message] = await pkpMail.find({
		to,
		contains: tag,
		subject: 'You are invited to new roles',
	});
	const full = await pkpMail.fullMessage(message.ID);

	return pkpMail.extractLink(full.HTML, 'Accept Invitation');
}

test.describe('User invitations', () => {
	test('scenario 1 — a newcomer is invited and joins the journal', async ({
		page,
		ojsApi,
		asUser,
		pkpMail,
	}, testInfo) => {
		test.slow();

		const tag = tagFor('s1', testInfo);
		const invitee = `${tag}new@example.org`;
		const account = `${tag}acct`;
		const password = `${tag}Pw1!`;
		const startDate = isoDate(30);
		const {journal, manager} = await scratchJournal(ojsApi, tag);

		const managerPage = await (await asUser(manager)).newPage();
		const usersAndRoles = new UsersAndRolesPage(managerPage, journal);
		await usersAndRoles.goto();
		await expect(usersAndRoles.invitationsHeading).toHaveText('Invitations (0)');

		// Step 1: an address that matches nobody takes the newcomer branch and is
		// carried into the details step's email field.
		const wizard = await usersAndRoles.openInviteWizard();
		await expect(wizard.pageHeading).toBeVisible();
		await wizard.searchFor(invitee);
		await expect(wizard.searchOutcome).toHaveText(
			'The user does not have a role in this journal',
		);
		await expect(wizard.emailField).toHaveValue(invitee);

		// Step 2: a name, one role, a start date a month out.
		await wizard.givenNameField.fill('Nadia');
		await wizard.familyNameField.fill('Newcomer');
		await wizard.fillLastRole({role: 'Section editor', startDate});
		await wizard.continueWith('Save And Continue');

		// Step 3 is the email composer, arriving with subject and body filled.
		await expect(wizard.subjectField).toHaveValue('You are invited to new roles');
		await wizard.send();
		await expect(wizard.sentDialog).toBeVisible();

		await wizard.sentDialog.getByRole('button', {name: 'View All Users'}).click();
		await expect(usersAndRoles.invitationsHeading).toHaveText('Invitations (1)');

		const row = usersAndRoles.invitationRow(invitee);
		await expect(row).toContainText('Nadia Newcomer');
		await expect(row).toContainText('Section editor');
		await expect(row).toContainText(`Invited ${isoDate()}`);

		// The invitee, who has no account and is signed in nowhere.
		const acceptUrl = await acceptUrlFromMail(pkpMail, {to: invitee, tag});
		const accept = new AcceptInvitationPage(page);
		await accept.open(acceptUrl);

		await expect(accept.accountStepHeading).toBeVisible({timeout: 30_000});
		await accept.createAccount({username: account, password});

		await expect(accept.detailsStepHeading).toBeVisible({timeout: 30_000});
		await expect(accept.givenNameField).toHaveValue('Nadia');
		await accept.enterDetails({country: 'Canada'});

		await expect(accept.reviewStepHeading).toBeVisible({timeout: 30_000});
		await expect(accept.rolesTable).toContainText('Section editor');
		await expect(accept.rolesTable).toContainText(startDate);
		await accept.acceptButton.click();
		await expect(accept.successDialog).toBeVisible({timeout: 30_000});

		// Where the dialog's "View All Submissions" button lands is A12's business.
		// What is contract is the account: it exists, with the credentials just
		// entered — so the test signs in through the sign-in page itself.
		await new LoginPage(page).login(account, password);
		await expect(page).not.toHaveURL(/\/login/);

		// And the manager now finds the newcomer among the journal's users.
		await usersAndRoles.goto();

		const userRow = usersAndRoles.userRow(invitee);
		await expect(userRow).toContainText('Nadia Newcomer');
		await expect(userRow).toContainText('Section editor');
		await expect(usersAndRoles.invitationsHeading).toHaveText('Invitations (0)');
	});

	test('scenario 2 — an existing user is invited to an additional role', async ({
		page,
		ojsApi,
		asUser,
		pkpMail,
	}, testInfo) => {
		test.slow();

		const tag = tagFor('s2', testInfo);
		const holder = `${tag}usr`;
		const holderEmail = `${holder}@example.org`;
		const startDate = isoDate();
		const {journal, manager} = await scratchJournal(ojsApi, tag, {
			users: [
				{username: holder, givenName: 'Hal', familyName: 'Holder', roles: ['reader']},
			],
		});

		const managerPage = await (await asUser(manager)).newPage();
		const usersAndRoles = new UsersAndRolesPage(managerPage, journal);
		await usersAndRoles.goto();

		// The search finds a role-holder of this journal: identity read-only, the
		// role they already hold listed, and no newcomer form.
		const wizard = await usersAndRoles.openInviteWizard();
		await wizard.searchFor(holderEmail);
		await expect(wizard.searchOutcome).toHaveText('The user already exists in the journal');
		await expect(managerPage.getByText(holderEmail)).toBeVisible();
		await expect(wizard.emailField).toHaveCount(0);
		await expect(wizard.currentRoleRows).toHaveCount(1);
		await expect(wizard.currentRoleRows).toContainText('Reader');

		await wizard.fillLastRole({role: 'Section editor', startDate});
		await wizard.continueWith('Save And Continue');

		await expect(wizard.subjectField).toHaveValue('You are invited to new roles');
		await wizard.send();
		await expect(wizard.sentDialog).toBeVisible();

		await wizard.sentDialog.getByRole('button', {name: 'View All Users'}).click();
		await expect(usersAndRoles.invitationRow(holderEmail)).toContainText('Section editor');

		// The invitee, signed out: one review step, no account and no details step.
		const acceptUrl = await acceptUrlFromMail(pkpMail, {to: holderEmail, tag});
		const accept = new AcceptInvitationPage(page);
		await accept.open(acceptUrl);

		await expect(accept.reviewStepHeading).toBeVisible({timeout: 30_000});
		await expect(accept.rolesTable).toContainText('Section editor');
		await expect(accept.accountStepHeading).toHaveCount(0);
		await expect(accept.detailsStepHeading).toHaveCount(0);

		await accept.acceptButton.click();
		await expect(accept.successDialog).toBeVisible({timeout: 30_000});

		// The role is attached to the account that already existed — the flow
		// signing nobody in is A12's, not this assertion's.
		await usersAndRoles.goto();

		const userRow = usersAndRoles.userRow(holderEmail);
		await expect(userRow).toContainText('Reader');
		await expect(userRow).toContainText('Section editor');
		await expect(usersAndRoles.invitationsHeading).toHaveText('Invitations (0)');
	});

	test('scenario 3 — the invitee declines', async ({page, ojsApi, asUser}, testInfo) => {
		const tag = tagFor('s3', testInfo);
		const declining = `${tag}dec@example.org`;
		const untouched = `${tag}keep@example.org`;
		const {journal, manager, invitations} = await scratchJournal(ojsApi, tag, {
			invitations: [
				{
					email: declining,
					roles: ['sectionEditor'],
					givenName: 'Dana',
					familyName: 'Decliner',
				},
				// The control: an invitation nobody answers, so the table's own
				// listing bounds the assertion that the declined row has gone.
				{email: untouched, roles: ['reader'], givenName: 'Kim', familyName: 'Keeper'},
			],
		});
		const [declined] = invitations;

		const invitee = new AcceptInvitationPage(page);
		await invitee.open(declined.declineUrl);
		await expect(invitee.declineHeading).toBeVisible();
		await expect(invitee.declineDescription).toBeVisible();

		await invitee.confirmDeclineButton.click();
		await expect(page).toHaveURL(/\/login/);
		await expect(page.locator('form#login')).toBeVisible();

		// The invitation is spent: its accept link no longer opens the flow.
		await invitee.open(declined.acceptUrl);
		await expect(invitee.unavailableHeading).toBeVisible();
		await expect(invitee.unavailableDescription).toBeVisible();

		const managerPage = await (await asUser(manager)).newPage();
		const usersAndRoles = new UsersAndRolesPage(managerPage, journal);
		await usersAndRoles.goto();

		await expect(usersAndRoles.invitationsHeading).toHaveText('Invitations (1)');
		await expect(usersAndRoles.invitationRow(untouched)).toBeVisible();
		await expect(usersAndRoles.invitationRow(declining)).toHaveCount(0);
	});

	test('scenario 4 — the manager cancels a pending invitation', async ({
		page,
		ojsApi,
		asUser,
	}, testInfo) => {
		const tag = tagFor('s4', testInfo);
		const cancelled = `${tag}can@example.org`;
		const untouched = `${tag}keep@example.org`;
		const {journal, manager, invitations} = await scratchJournal(ojsApi, tag, {
			invitations: [
				{
					email: cancelled,
					roles: ['sectionEditor'],
					givenName: 'Cara',
					familyName: 'Cancelled',
				},
				{email: untouched, roles: ['reader'], givenName: 'Kim', familyName: 'Keeper'},
			],
		});

		const managerPage = await (await asUser(manager)).newPage();
		const usersAndRoles = new UsersAndRolesPage(managerPage, journal);
		await usersAndRoles.goto();
		await expect(usersAndRoles.invitationsHeading).toHaveText('Invitations (2)');

		await usersAndRoles.invitationAction(cancelled, 'Cancel Invite');

		const dialog = managerPage.getByRole('dialog', {name: 'Cancel Invitation'});
		await expect(dialog).toContainText(`Email: ${cancelled}`);
		await expect(dialog).toContainText('Role: Section editor');
		await expect(dialog).toContainText(`Status: Invited ${isoDate()}`);
		await dialog.getByRole('button', {name: 'Cancel Invitation', exact: true}).click();

		await expect(usersAndRoles.invitationsHeading).toHaveText('Invitations (1)');
		await expect(usersAndRoles.invitationRow(cancelled)).toHaveCount(0);
		await expect(usersAndRoles.invitationRow(untouched)).toBeVisible();

		// The email already delivered now leads nowhere: the recipient is told so.
		const invitee = new AcceptInvitationPage(page);
		await invitee.open(invitations[0].acceptUrl);
		await expect(invitee.unavailableHeading).toBeVisible();
		await expect(invitee.unavailableDescription).toBeVisible();
	});

	test('scenario 5 — the manager edits a pending invitation', async ({
		page,
		ojsApi,
		asUser,
		pkpMail,
	}, testInfo) => {
		test.slow();

		const tag = tagFor('s5', testInfo);
		const holder = `${tag}usr`;
		const holderEmail = `${holder}@example.org`;
		const {journal, manager, invitations} = await scratchJournal(ojsApi, tag, {
			users: [
				{username: holder, givenName: 'Hal', familyName: 'Holder', roles: ['reader']},
			],
			invitations: [{user: holder, roles: ['author']}],
		});
		const superseded = invitations[0];

		const managerPage = await (await asUser(manager)).newPage();
		const usersAndRoles = new UsersAndRolesPage(managerPage, journal);
		await usersAndRoles.goto();
		await usersAndRoles.invitationAction(holderEmail, 'Edit');

		const warning = managerPage.getByRole('dialog', {name: 'Edit Invitation'});
		await expect(warning).toContainText(
			'If you edit the existing invitation or add a new role, the current invitation ' +
				'will be canceled and, a new one will be sent. Are you sure you want to proceed?',
		);
		await warning.getByRole('button', {name: 'Edit Invitation', exact: true}).click();

		// The wizard reopens preloaded on "Enter details" — no search step, and the
		// invitation's own role already on the table.
		const wizard = usersAndRoles.openedWizard();
		await managerPage.waitForURL(/invitation\/edit/, {timeout: 30_000});
		await expect(wizard.stepPill(/Enter details/)).toBeVisible({timeout: 30_000});
		await expect(managerPage.getByRole('button', {name: 'Search User'})).toHaveCount(0);
		await expect(wizard.newRoleRows).toHaveCount(1);

		await wizard.addRoleRow();
		await wizard.fillLastRole({role: 'Copyeditor', startDate: isoDate()});
		await wizard.continueWith('Save And Continue');

		await expect(wizard.subjectField).toHaveValue('You are invited to new roles');
		await wizard.send();
		await expect(wizard.sentDialog).toBeVisible();

		// One row for the invitee, carrying the new invitation's roles.
		await wizard.sentDialog.getByRole('button', {name: 'View All Users'}).click();
		await expect(usersAndRoles.invitationsHeading).toHaveText('Invitations (1)');

		const row = usersAndRoles.invitationRow(holderEmail);
		await expect(row).toContainText('Author');
		await expect(row).toContainText('Copyeditor');

		// The second email's links work.
		const acceptUrl = await acceptUrlFromMail(pkpMail, {to: holderEmail, tag});
		expect(acceptUrl).not.toBe(superseded.acceptUrl);

		const invitee = new AcceptInvitationPage(page);
		await invitee.open(acceptUrl);
		await expect(invitee.reviewStepHeading).toBeVisible({timeout: 30_000});
		await expect(invitee.rolesTable).toContainText('Copyeditor');

		// The first email's link is spent. WHAT it shows is A14's business — a bare
		// not-found page today, "Invitation Unavailable" once fixed; that it no
		// longer opens the acceptance flow is the contract, and the link above is
		// the positive control for the same navigation.
		await invitee.open(superseded.acceptUrl);
		await expect(invitee.reviewStepHeading).toHaveCount(0);
		await expect(invitee.acceptButton).toHaveCount(0);
	});

	test('scenario 6 — the accept link is opened by the wrong user', async ({
		ojsApi,
		asUser,
	}, testInfo) => {
		const tag = tagFor('s6', testInfo);
		const holder = `${tag}usr`;
		const other = `${tag}oth`;
		const {invitations} = await scratchJournal(ojsApi, tag, {
			users: [
				{username: holder, givenName: 'Hal', familyName: 'Holder', roles: ['reader']},
				{username: other, givenName: 'Otto', familyName: 'Other', roles: ['reader']},
			],
			invitations: [{user: holder, roles: ['author']}],
		});
		const {acceptUrl} = invitations[0];

		const otherPage = await (await asUser(other)).newPage();
		const accept = new AcceptInvitationPage(otherPage);
		await accept.open(acceptUrl);

		await expect(accept.wrongUserDialog).toBeVisible({timeout: 30_000});
		await expect(accept.wrongUserDialog).toContainText(
			'Please log out and sign in with the correct account to accept this invitation.',
		);
		await expect(accept.logoutButton).toBeVisible();

		// Signing out and re-opening the link resumes the normal flow.
		await accept.logoutButton.click();
		await otherPage.waitForURL((url) => !url.pathname.includes('/invitation/'), {
			timeout: 30_000,
			waitUntil: 'commit',
		});

		await accept.open(acceptUrl);
		await expect(accept.reviewStepHeading).toBeVisible({timeout: 30_000});
		await expect(accept.rolesTable).toContainText('Author');
		await expect(accept.wrongUserDialog).toHaveCount(0);
	});

	test('scenario 7 — an expired invitation', async ({page, ojsApi, asUser}, testInfo) => {
		const tag = tagFor('s7', testInfo);
		const lapsed = `${tag}old@example.org`;
		const pending = `${tag}new@example.org`;
		const {journal, manager, invitations} = await scratchJournal(ojsApi, tag, {
			invitations: [
				{
					email: lapsed,
					roles: ['reader'],
					givenName: 'Lena',
					familyName: 'Lapsed',
					status: 'expired',
				},
				// The control: a live invitation in the same journal, so "not listed"
				// is bounded by the table's own answer.
				{email: pending, roles: ['reader'], givenName: 'Pia', familyName: 'Pending'},
			],
		});

		const invitee = new AcceptInvitationPage(page);
		await invitee.open(invitations[0].acceptUrl);

		await expect(invitee.unavailableHeading).toBeVisible();
		await expect(invitee.unavailableDescription).toBeVisible();
		await expect(invitee.loginLink).toHaveAttribute('href', new RegExp(`/${journal}/login$`));
		await expect(invitee.registerLink).toHaveAttribute(
			'href',
			new RegExp(`/${journal}/user/register$`),
		);

		await invitee.loginLink.click();
		await expect(page.locator('form#login')).toBeVisible();
		await page.goBack();
		await invitee.registerLink.click();
		await expect(page.locator('form#register')).toBeVisible();

		// The manager's table lists the live invitation and not the lapsed one.
		const managerPage = await (await asUser(manager)).newPage();
		const usersAndRoles = new UsersAndRolesPage(managerPage, journal);
		await usersAndRoles.goto();

		await expect(usersAndRoles.invitationsHeading).toHaveText('Invitations (1)');
		await expect(usersAndRoles.invitationRow(pending)).toBeVisible();
		await expect(usersAndRoles.invitationRow(lapsed)).toHaveCount(0);
	});
});
