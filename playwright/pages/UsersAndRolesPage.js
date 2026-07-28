// @ts-check
/**
 * @file playwright/pages/UsersAndRolesPage.js
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Settings → Users & Roles, "Users" tab: the Invitations table, the users table
 * below it, and the "Invite to a role" button that opens the send-invitation
 * wizard (`InviteUserWizardPage`).
 *
 * Both tables are Vue `PkpTable`s whose accessible name carries a live count
 * ("Invitations (2)"), so the name is matched with a prefix regex and the count
 * is left to the spec to assert.
 */

const {BasePage} = require('../../lib/pkp/playwright/pages/BasePage.js');
const {InviteUserWizardPage} = require('./InviteUserWizardPage.js');

class UsersAndRolesPage extends BasePage {
	/**
	 * @param {import('@playwright/test').Page} page
	 * @param {string} contextPath the journal's url path
	 */
	constructor(page, contextPath) {
		super(page);
		this.contextPath = contextPath;

		this.invitationsHeading = page.getByRole('heading', {name: /^Invitations \(\d+\)$/});
		this.invitationsTable = page.getByRole('table', {name: /^Invitations/});
		this.invitationRows = this.invitationsTable.locator('tbody tr');
		this.inviteButton = page.getByRole('button', {name: 'Invite to a role'});

		this.usersTable = page.getByRole('table', {name: /^Current Users/});
		this.userRows = this.usersTable.locator('tbody tr');
	}

	static url(contextPath) {
		return BasePage.contextUrl(contextPath, '/management/settings/access');
	}

	/** Open the screen and wait for the Invitations table to have answered. */
	async goto() {
		await this.page.goto(UsersAndRolesPage.url(this.contextPath));
		await this.invitationsHeading.waitFor({timeout: 30_000});
	}

	/** @param {string} email */
	invitationRow(email) {
		return this.invitationRows.filter({hasText: email});
	}

	/** @param {string} text */
	userRow(text) {
		return this.userRows.filter({hasText: text});
	}

	/**
	 * Choose an entry of a pending row's "Invitation management options" menu.
	 * The menu portals to the document root, so it is scoped to the page.
	 *
	 * @param {string} email
	 * @param {string} action "Edit" or "Cancel Invite"
	 */
	async invitationAction(email, action) {
		await this.invitationRow(email)
			.getByRole('button', {name: 'Invitation management options'})
			.click();
		await this.page.getByRole('menuitem', {name: action, exact: true}).click();
	}

	/** Open the send-invitation wizard. */
	async openInviteWizard() {
		const wizard = new InviteUserWizardPage(this.page);

		await this.inviteButton.click();
		await wizard.searchField.waitFor({timeout: 30_000});

		return wizard;
	}

	/**
	 * The wizard a row's "Edit" has already navigated to — same screen, opened
	 * preloaded and without the search step, so there is nothing to click here.
	 */
	openedWizard() {
		return new InviteUserWizardPage(this.page);
	}
}

module.exports = {UsersAndRolesPage};
