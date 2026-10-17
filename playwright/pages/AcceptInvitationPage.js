// @ts-check
/**
 * @file playwright/pages/AcceptInvitationPage.js
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * The recipient's side of an invitation: the acceptance flow, the decline page,
 * and the landing pages a dead link reaches. All of them are opened by URL —
 * the emailed link is the credential and no sign-in is involved — so this POM
 * takes the link rather than building one.
 */

const {BasePage} = require('../../lib/pkp/playwright/pages/BasePage.js');

class AcceptInvitationPage extends BasePage {
	/** @param {import('@playwright/test').Page} page */
	constructor(page) {
		super(page);

		// Acceptance steps. A newcomer walks all three; an existing user sees only
		// the review step.
		this.accountStepHeading = page.getByRole('heading', {name: /Create OJS account/});
		this.detailsStepHeading = page.getByRole('heading', {name: /- Enter details$/});
		this.reviewStepHeading = page.getByRole('heading', {name: /Review & create account/});

		this.usernameField = page.getByRole('textbox', {name: /^Username/});
		this.passwordField = page.getByRole('textbox', {name: /^Password/});
		this.privacyConsent = page.getByRole('checkbox', {
			name: /I agree to have my data collected/,
		});
		this.givenNameField = page.getByRole('textbox', {name: /^Given Name/});
		this.familyNameField = page.getByRole('textbox', {name: /^Family Name/});
		this.countryField = page.getByRole('combobox', {name: /^Country of affiliation/});

		this.continueButton = page.getByRole('button', {name: 'Save and continue'});
		this.acceptButton = page.getByRole('button', {name: 'Accept And Continue to OJS'});
		this.rolesTable = page.getByRole('table', {name: 'Roles'});
		this.successDialog = page.getByRole('dialog', {
			name: "You've been assigned a new role in OJS",
		});
		this.wrongUserDialog = page.getByRole('dialog', {
			name: "Invitation not accepted. You're logged in as a different user.",
		});
		this.logoutButton = this.wrongUserDialog.getByRole('button', {name: 'Logout'});

		// Decline.
		this.declineHeading = page.getByRole('heading', {name: 'Decline Invitation'});
		this.declineDescription = page.getByText(
			'Are you sure you want to decline this invitation?',
		);
		this.confirmDeclineButton = page.getByRole('button', {
			name: 'Confirm Decline Invitation',
		});

		// A link whose invitation is spent.
		this.unavailableHeading = page.getByRole('heading', {name: 'Invitation Unavailable'});
		this.unavailableDescription = page.getByText('This invitation is no longer available.');
		this.loginLink = page.getByRole('link', {name: 'Login', exact: true});
		this.registerLink = page.getByRole('link', {name: 'Register', exact: true});
	}

	/**
	 * Open an emailed link.
	 *
	 * @param {string} url accept or decline URL
	 */
	async open(url) {
		return this.page.goto(url);
	}

	/**
	 * The newcomer's account step.
	 *
	 * @param {{username: string, password: string}} credentials
	 */
	async createAccount({username, password}) {
		await this.usernameField.fill(username);
		await this.passwordField.fill(password);
		await this.privacyConsent.check();
		await this.continueButton.click();
	}

	/**
	 * The newcomer's details step. Name fields arrive pre-filled from whatever the
	 * inviter typed; only what the caller names is overwritten.
	 *
	 * @param {{country: string, givenName?: string, familyName?: string}} details
	 */
	async enterDetails({country, givenName, familyName}) {
		if (givenName !== undefined) {
			await this.givenNameField.fill(givenName);
		}

		if (familyName !== undefined) {
			await this.familyNameField.fill(familyName);
		}

		await this.countryField.selectOption({label: country});
		await this.continueButton.click();
	}
}

module.exports = {AcceptInvitationPage};
