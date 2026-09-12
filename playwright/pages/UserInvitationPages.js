/**
 * @file playwright/pages/UserInvitationPages.js
 *
 * OJS-local Page Objects for the user-invitations feature (spec:
 * lib/pkp/docs/product/specs/user-invitations.md).
 *
 * Three surfaces:
 * - UsersRolesPage — Settings → Users & Roles (Users tab): the Invitations
 *   table (+ row menu) and the Current Users list (+ row menu).
 * - SendInvitationWizard — {journal}/invitation/create|edit/... and the
 *   editUser mode reached from a user row's Edit action.
 * - AcceptInvitationWizard — the emailed accept-link flow.
 *
 * Labels are the live locale strings (lib/pkp/locale/en/invitation.po,
 * locale/en/invitation.po) — required fields carry a screen-reader
 * "* Required" suffix in their accessible name, hence the ^-anchored regexes.
 */
const {expect} = require('@playwright/test');
const {BasePage} = require('../../lib/pkp/playwright/pages/BasePage.js');

exports.UsersRolesPage = class UsersRolesPage extends BasePage {
    /**
     * @param {import('@playwright/test').Page} page
     * @param {string} contextPath
     */
    constructor(page, contextPath) {
        super(page);
        this.contextPath = contextPath;
        this.pageHeading = page.getByRole('heading', {name: 'Users & Roles'});
        this.inviteButton = page.getByRole('button', {name: 'Invite to a role'});
        this.invitationsTable = page.getByRole('table', {name: /Invitations \(/});
        this.usersTable = page.getByRole('table', {name: /Current Users \(/});
    }

    async goto() {
        await this.page.goto(this.contextUrl(this.contextPath, '/management/settings/access'));
        await expect(this.pageHeading).toBeVisible();
    }

    /** The h3 label above the Invitations table, e.g. "Invitations (1)". */
    invitationsHeading(count) {
        return this.page.getByRole('heading', {name: `Invitations (${count})`});
    }

    /** Invitations-table row for a recipient email. */
    invitationRow(email) {
        return this.invitationsTable.getByRole('row').filter({hasText: email});
    }

    /** Current-Users row for a user email. */
    userRow(email) {
        return this.usersTable.getByRole('row').filter({hasText: email});
    }

    /**
     * Open a row's actions menu and click one item. Works for both tables:
     * the invitations menu button is named "Invitation management options",
     * the users one renders the raw ##userAccess.management.options## token
     * (register A7) — the regex matches both.
     *
     * @param {import('@playwright/test').Locator} row
     * @param {string|RegExp} itemLabel
     */
    async rowAction(row, itemLabel) {
        await row.getByRole('button', {name: /management.options/i}).click();
        await this.page.getByRole('menuitem', {name: itemLabel}).click();
    }
};

exports.SendInvitationWizard = class SendInvitationWizard extends BasePage {
    constructor(page) {
        super(page);
        this.searchInput = page.getByLabel(/Search for a user by email address/);
        // "Search User" also names the step-1 pill; exact match hits the button.
        this.searchContinueButton = page.getByRole('button', {name: 'Search User', exact: true});
        this.saveAndContinueButton = page.getByRole('button', {name: 'Save And Continue'});
        this.addAnotherRoleButton = page.getByRole('button', {name: 'Add Another Role'});
        this.subjectInput = page.getByLabel(/^Subject/);
        this.sendButton = page.getByRole('button', {name: 'Invite user to the role'});
        this.sentDialog = page.getByRole('dialog').filter({hasText: 'Invitation Sent'});
        this.emailInput = page.getByLabel(/^Email/);
    }

    stepHeading(name) {
        return this.page.getByRole('heading', {name});
    }

    /** Step 1 — search, then land on "Enter details". */
    async searchAndContinue(email) {
        await expect(this.stepHeading(/Search User/)).toBeVisible();
        await this.searchInput.fill(email);
        await this.searchContinueButton.click();
        await expect(this.stepHeading(/Enter details/)).toBeVisible();
    }

    async fillGivenName(givenName) {
        await this.page.getByLabel(/^Given Name/).first().fill(givenName);
    }

    /**
     * Fill the new-role row (the row that carries a "Select a new role"
     * combobox). Scoping to that row matters in editUser mode, where the
     * current-role rows also carry a Journal Masthead select whose change opens
     * a confirmation dialog (Rule 13) — a global `.last()` would hit it.
     * Within the row the two comboboxes are [role, masthead] in DOM order.
     *
     * @param {{role: string, startDate: string, masthead?: string}} options
     *   role/masthead are the visible option labels; startDate is YYYY-MM-DD.
     */
    async fillRoleRow({role, startDate, masthead = 'Appear on the masthead'}) {
        const row = this.page
            .getByRole('row')
            .filter({has: this.page.getByLabel(/^Select a new role/)})
            .last();
        await row.getByLabel(/^Select a new role/).selectOption({label: role});
        await row.getByRole('textbox').fill(startDate);
        await row.getByRole('combobox').last().selectOption({label: masthead});
    }

    /** Details step → compose step (POST add + PUT populate happen here). */
    async saveAndContinue() {
        await this.saveAndContinueButton.click();
        await expect(this.subjectInput).toBeVisible();
        // The subject prefills from the stored template (or the edited
        // invitation's payload); waiting for it prevents a late prefill from
        // overwriting the subject this test is about to set.
        await expect(this.subjectInput).not.toHaveValue('');
    }

    async setSubject(subject) {
        await this.subjectInput.fill(subject);
    }

    /** Compose step → "Invitation Sent" dialog (PUT populate + PUT invite). */
    async send() {
        await this.sendButton.click();
        await expect(this.sentDialog).toBeVisible();
    }

    /**
     * Dismiss the success dialog. Its only button, "View All Users", is not
     * asserted on beyond dismissal (Rule 15 / register A5) — callers navigate
     * to Users & Roles themselves.
     */
    async dismissSentDialog() {
        await this.sentDialog.getByRole('button', {name: 'View All Users'}).click();
    }
};

exports.AcceptInvitationWizard = class AcceptInvitationWizard extends BasePage {
    constructor(page) {
        super(page);
        this.usernameInput = page.getByLabel(/^Username/);
        this.passwordInput = page.getByLabel(/^Password/);
        this.privacyCheckbox = page.getByRole('checkbox');
        this.saveAndContinueButton = page.getByRole('button', {name: 'Save and continue'});
        this.acceptButton = page.getByRole('button', {name: 'Accept And Continue to OJS'});
        this.acceptedDialog = page
            .getByRole('dialog')
            .filter({hasText: "You've been assigned a new role in OJS"});
    }

    stepHeading(name) {
        return this.page.getByRole('heading', {name});
    }

    /** "Create OJS account" step (new invitees; ORCID is off in test contexts). */
    async createAccount({username, password}) {
        await expect(this.stepHeading(/Create OJS account/)).toBeVisible();
        await this.usernameInput.fill(username);
        await this.passwordInput.fill(password);
        await this.privacyCheckbox.check();
        await this.saveAndContinueButton.click();
    }

    /** "Enter details" step (new invitees). */
    async fillDetails({givenName, country}) {
        await expect(this.stepHeading(/Enter details/)).toBeVisible();
        await this.page.getByLabel(/^Given Name/).first().fill(givenName);
        await this.page.getByLabel(/^Country of affiliation/).selectOption(country);
        await this.saveAndContinueButton.click();
    }

    async expectOnReviewStep() {
        await expect(this.stepHeading(/Review & create account/)).toBeVisible();
    }

    /** Final button → "You've been assigned a new role" dialog. */
    async accept() {
        await this.acceptButton.click();
        await expect(this.acceptedDialog).toBeVisible();
    }
};
