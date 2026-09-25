// @ts-check
/**
 * @file playwright/pages/InviteUserWizardPage.js
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * The send-invitation wizard ("Invite user to take a role"), reached from Users
 * & Roles or, preloaded, from a pending invitation's "Edit". Three steps —
 * Search User, Enter details, and the email composer — each with its own
 * Continue label.
 *
 * Two locator notes, both learned live:
 *
 * - The role table's fields are addressed by their form `name`, not by label:
 *   every added row renders the same input ids, so the second row onwards has no
 *   accessible name at all and the first row's name grows a copy of the label per
 *   row. Rows are located structurally and the fields inside them by name.
 * - Step-list pills are buttons too, so "Search User" matches both the pill and
 *   the Continue button. Footer buttons come last in DOM order, which is what
 *   `continueWith()` relies on.
 */

const {BasePage} = require('../../lib/pkp/playwright/pages/BasePage.js');

class InviteUserWizardPage extends BasePage {
	/** @param {import('@playwright/test').Page} page */
	constructor(page) {
		super(page);

		this.pageHeading = page.getByRole('heading', {
			name: 'Invite user to take a role',
			exact: true,
		});
		// Search step.
		this.searchField = page.getByRole('textbox', {
			name: /Search for a user by email address/,
		});
		this.searchOutcome = page.getByText(
			/The user (already exists in the journal|does not have a role in this journal)/,
		);

		// Details step: the newcomer form, and the role table both branches share.
		this.emailField = page.getByRole('textbox', {name: /^Email/});
		this.givenNameField = page.getByRole('textbox', {name: /^Given Name/});
		this.familyNameField = page.getByRole('textbox', {name: /^Family Name/});
		this.roleTable = page
			.locator('table')
			.filter({has: page.getByRole('columnheader', {name: 'Journal Masthead'})});
		this.roleRows = this.roleTable.locator('tbody tr');
		// Rows offering a role to add carry the role select; the invitee's current
		// roles are the rows without one (their role is plain text). `hasText` can
		// not tell them apart — a select's options are part of a row's text.
		this.newRoleRows = this.roleRows.filter({
			has: page.locator('select[name="userGroupId"]'),
		});
		this.currentRoleRows = this.roleRows.filter({
			hasNot: page.locator('select[name="userGroupId"]'),
		});
		this.addRoleButton = page.getByRole('button', {name: 'Add Another Role'});

		// Composer step.
		this.subjectField = page.getByRole('textbox', {name: 'Subject', exact: true});

		this.sentDialog = page.getByRole('dialog', {name: 'Invitation Sent'});
	}

	/** The clickable pill of a step the wizard offers. */
	stepPill(name) {
		return this.page.getByRole('button', {name}).first();
	}

	/**
	 * Run the search step.
	 *
	 * @param {string} term
	 */
	async searchFor(term) {
		await this.searchField.fill(term);
		await this.continueWith('Search User');
	}

	/** Offer one more blank role row and wait for it. */
	async addRoleRow() {
		const before = await this.newRoleRows.count();

		await this.addRoleButton.click();
		await this.newRoleRows.nth(before).waitFor({timeout: 15_000});
	}

	/**
	 * Fill the last offered role row. Reviewer rows carry a fixed masthead value
	 * instead of a select, so the masthead is only set where it is offered.
	 *
	 * @param {{role: string, startDate: string, masthead?: string}} options
	 */
	async fillLastRole({role, startDate, masthead = 'Appear on the masthead'}) {
		const row = this.newRoleRows.last();

		await row.locator('select[name="userGroupId"]').selectOption({label: role});
		await row.locator('input[name="dateStart"]').fill(startDate);

		const mastheadSelect = row.locator('select[name="masthead"]');

		if (await mastheadSelect.count()) {
			await mastheadSelect.selectOption({label: masthead});
		}
	}

	/**
	 * Press a step's own Continue button. The footer sits after the step list in
	 * the DOM, so `.last()` picks the button over a same-named step pill.
	 *
	 * @param {string} label
	 */
	async continueWith(label) {
		await this.page.getByRole('button', {name: label, exact: true}).last().click();
	}

	/** Send the invitation from the composer step. */
	async send() {
		await this.continueWith('Invite user to the role');
	}
}

module.exports = {InviteUserWizardPage};
