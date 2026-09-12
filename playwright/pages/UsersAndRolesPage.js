// @ts-check
/**
 * @file playwright/pages/UsersAndRolesPage.js
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Settings → Users & Roles, "Users" tab: the Invitations table, the users table
 * below it ("Current Users"), and the "Invite to a role" button that opens the
 * send-invitation wizard (`InviteUserWizardPage`).
 *
 * Both tables are Vue `PkpTable`s whose accessible name carries a live count
 * ("Invitations (2)"), so the name is matched with a prefix regex and the count
 * is left to the spec to assert. The two share column names ("Roles"), so every
 * locator below is scoped to one table, never to the page.
 *
 * Three locator notes, all learned live:
 *
 * - The users table's row-options button is located STRUCTURALLY (the row's only
 *   button), not by its accessible name: that name is a raw locale key today
 *   (*Users management*'s register, A3), and a POM that spelled the key out
 *   would have to change when the defect is fixed.
 * - The two tables' row menus behave differently: the Invitations one portals to
 *   the document root (so its items are scoped to the page), while the users
 *   one renders inside the row's own cell and stays open when another row's
 *   menu is opened (so its items are scoped to the row).
 * - The search field only searches on Enter — typing alone changes nothing —
 *   and the row it narrows to arrives with the API's answer, so `searchUsers()`
 *   waits for that response rather than for a row. The "Current Users" heading
 *   reads "(0)" until that answer lands, which is why `goto()` and
 *   `searchUsers()` both end by settling the count against it.
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

		this.usersHeading = page.getByRole('heading', {name: /^Current Users \(\d+\)$/});
		this.usersTable = page.getByRole('table', {name: /^Current Users/});
		this.userRows = this.usersTable.locator('tbody tr');
		this.userSearchField = page.getByRole('searchbox', {name: /^Enter a user's name/});
	}

	static url(contextPath) {
		return BasePage.contextUrl(contextPath, '/management/settings/access');
	}

	/** Open the screen and wait for both tables to have answered. */
	async goto() {
		const answered = this.usersApiResponse();

		await this.page.goto(UsersAndRolesPage.url(this.contextPath));
		await this.invitationsHeading.waitFor({timeout: 30_000});
		await this.settle(await answered);
	}

	/**
	 * The users API call the table is about to make. Created BEFORE the action
	 * that triggers it.
	 *
	 * @param {string} [searchPhrase]
	 */
	usersApiResponse(searchPhrase) {
		return this.page.waitForResponse(
			(response) =>
				response.url().includes('/api/v1/users?') &&
				response.status() === 200 &&
				(searchPhrase === undefined ||
					response
						.url()
						.includes(`searchPhrase=${encodeURIComponent(searchPhrase)}`)),
			{timeout: 30_000},
		);
	}

	/**
	 * Wait for the table to have caught up with the answer it was given. The
	 * heading counts "(0)" until then, so anything reading the count straight
	 * after a navigation or a search would read the placeholder.
	 *
	 * @param {import('@playwright/test').Response} response
	 */
	async settle(response) {
		const {itemsMax} = await response.json();

		await this.page
			.getByRole('heading', {name: `Current Users (${itemsMax})`, exact: true})
			.waitFor({timeout: 30_000});
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
	 * The red mark the Name cell carries while an account is disabled. The icon
	 * is a bare `<svg>` with no accessible name, so the only thing that tells it
	 * apart from the ORCID icon beside it is the negative colour class.
	 *
	 * @param {string} text something that identifies the row
	 */
	disabledMark(text) {
		return this.userRow(text).locator('.text-negative');
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

	/** How many users the "Current Users" heading currently counts. */
	async userCount() {
		const heading = await this.usersHeading.textContent();

		return Number(heading.replace(/\D/g, ''));
	}

	/**
	 * Narrow the users table. The field submits on Enter only, and the narrowed
	 * list is whatever the users API answers with — so the response bounds the
	 * wait, which is what an absence assertion afterwards needs.
	 *
	 * @param {string} term
	 */
	async searchUsers(term) {
		const answered = this.usersApiResponse(term);

		await this.userSearchField.fill(term);
		await this.userSearchField.press('Enter');
		await this.settle(await answered);
	}

	/**
	 * A user row's options menu, opened. The button carries no usable accessible
	 * name (A3), so it is found as the row's only button (the menu's own entries
	 * are `menuitem`s, not buttons).
	 *
	 * The menu is NOT portalled — its items render inside the row's own cell —
	 * and an open menu stays open when another row's is opened, so both the
	 * trigger and the items are scoped to the row rather than to the page.
	 *
	 * @param {string} text something that identifies the row — an email address
	 * @returns {Promise<import('@playwright/test').Locator>} the open menu's items
	 */
	async openUserMenu(text) {
		const row = this.userRow(text);

		await row.getByRole('button').first().click();

		const items = row.getByRole('menuitem');

		await items.first().waitFor({timeout: 15_000});

		return items;
	}

	/**
	 * Choose an entry of a user row's options menu.
	 *
	 * @param {string} text something that identifies the row
	 * @param {string} action e.g. "Edit", "Email", "Remove User", "Disable User"
	 */
	async userAction(text, action) {
		await this.openUserMenu(text);

		// By accessible name, not by `hasText`: the entries carry an icon and the
		// surrounding whitespace an exact text match would trip over.
		await this.userRow(text)
			.getByRole('menuitem', {name: action, exact: true})
			.click();
	}

	/**
	 * The labels a user row's options menu offers, in order.
	 *
	 * @param {string} text something that identifies the row
	 * @returns {Promise<string[]>}
	 */
	async userActionLabels(text) {
		const items = await this.openUserMenu(text);
		const labels = (await items.allInnerTexts()).map((label) => label.trim());

		// Close it again: an open menu overlays the rows below it.
		await this.userRow(text).getByRole('button').first().click();
		await items.first().waitFor({state: 'hidden', timeout: 15_000});

		return labels;
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
