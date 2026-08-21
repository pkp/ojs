// @ts-check
/**
 * @file playwright/tests/user-invitations.spec.js
 *
 * User invitations — OJS suite, one test per canonical scenario the spec runs
 * on OJS (scenarios 1–8; scenario 9 is OPS-only).
 * Spec: lib/pkp/docs/e2e/specs/U06-user-invitations.md
 *
 * Deliberately NOT covered (one line per omission, citing the register ID):
 * - A3 🐞: S6 asserts a replaced invitation's old links no longer open the flow
 *   (Rule 12); the unstyled-404 rendering itself is the open bug, not contract.
 * - A4 🐞: the signed-out landing after accepting is the open bug; S2/S3/S8
 *   assert the spec behavior around it (roles granted, credentials work via a
 *   fresh sign-in) without asserting where the closing dialog's button lands.
 * - A5 🐞: no assertions on inviter decision updates or on where the
 *   "Invitation Sent" dialog's "View All Users" button leaves the browser.
 * - A7 🐞: copy defects (email greeting, grammar slips, raw locale tokens) are
 *   register findings, not asserted; the users-row menu locator tolerates the
 *   raw ##userAccess.management.options## token.
 * - A1 ❓: how widely the wizard's own address is gated is an open question —
 *   the suite drives only the offered manager path (no role-by-role probing).
 * - A2 ❓: daily-cleanup behavior on unsent drafts is open and needs the
 *   scheduled task (serial project territory) — not covered here.
 * - Rule 14 / A6 🐞: the disabled-user paths are not canonical scenarios and
 *   the users-row variant is broken per A6 — left to the register.
 * - Rules 5/7 ORCID legs: ORCID is disabled in the test contexts, so per Rule 5
 *   the "Verify ORCID iD" step never renders; wizards start past it.
 * - Rules 2/4 expiry leg: expiry needs config/time control the screens don't
 *   offer (SET-064); deferred to the scenario-endpoint queue.
 *
 * Every test seeds its own scratch journal (publicknowledge and the seeded
 * roster stay untouched); Mailpit assertions are scoped by unique throwaway
 * recipient addresses carrying app + test in the tag, plus a subject marker.
 */
const {test, expect} = require('../support/fixtures.js');
const {
    UsersRolesPage,
    SendInvitationWizard,
    AcceptInvitationWizard,
} = require('../pages/UserInvitationPages.js');
const {LoginPage} = require('../../lib/pkp/playwright/pages/LoginPage.js');

const ROLE = 'Copyeditor'; // offered role under test (assistant-level, masthead selectable)

/** Unique per-run tag: alphanumeric, carries app + scenario + worker. */
function makeTag(scenario, testInfo) {
    return `u6${scenario}ojsw${testInfo.parallelIndex}${Math.random().toString(36).slice(2, 8)}`;
}

/** Local-timezone YYYY-MM-DD (input[type=date] format). */
function today() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

/**
 * Scratch journal + throwaway manager (+ optional extra users).
 * Seeded users get email `<username>@mail.test`, password username-doubled.
 */
async function seedJournal(ojsApi, tag, extraUsers = []) {
    const manager = `mgr${tag}`;
    await ojsApi.createContext({
        tag,
        users: [{username: manager, roles: ['manager']}, ...extraUsers],
    });
    return {path: tag, manager};
}

/**
 * Drive the send wizard from Users & Roles as the (already signed-in) manager
 * page. `search` is the step-1 input; pass `expectExisting` for a roster hit.
 */
async function sendInvitation(managerPage, contextPath, {search, expectExisting = false, givenName, subject}) {
    const usersRoles = new UsersRolesPage(managerPage, contextPath);
    await usersRoles.goto();
    await usersRoles.inviteButton.click();

    const wizard = new SendInvitationWizard(managerPage);
    await wizard.searchAndContinue(search);
    if (expectExisting) {
        await expect(managerPage.getByText('The user already exists in the journal')).toBeVisible();
    } else {
        await expect(managerPage.getByText('The user does not have a role in this journal')).toBeVisible();
        if (givenName) {
            await wizard.fillGivenName(givenName);
        }
    }
    await wizard.fillRoleRow({role: ROLE, startDate: today()});
    await wizard.saveAndContinue();
    await wizard.setSubject(subject);
    await wizard.send();
    await wizard.dismissSentDialog();
    return wizard;
}

/**
 * Fetch the invitation email (scoped by recipient + subject marker) and pull
 * both links. The email template uses single-quoted hrefs, so we regex the URL
 * directly rather than via extractLink (which assumes double quotes).
 */
async function invitationLinks(pkpMail, {to, contains}) {
    const summary = await pkpMail.find({to, contains});
    const full = await pkpMail.fullMessage(summary.ID);
    const html = full.HTML;
    const grab = (op) => {
        const m = html.match(new RegExp(`href=['"]([^'"]*/invitation/${op}\\?[^'"]+)['"]`, 'i'));
        return m ? m[1].replace(/&amp;/g, '&') : null;
    };
    return {html, accept: grab('accept'), decline: grab('decline')};
}

test.describe('user invitations', () => {
    test('S1: manager invites a newcomer to a role', {tag: '@smoke'}, async ({page, asUser, ojsApi, pkpMail}, testInfo) => {
        const tag = makeTag('s1', testInfo);
        const recipient = `rcpt${tag}@mail.test`;
        const subject = `Invitation${tag}`;
        const {path, manager} = await seedJournal(ojsApi, tag);

        const managerCtx = await asUser(manager);
        const managerPage = await managerCtx.newPage();
        await sendInvitation(managerPage, path, {search: recipient, givenName: 'Newton', subject});

        // Back on Users & Roles the Invitations table shows the pending row.
        const usersRoles = new UsersRolesPage(managerPage, path);
        await usersRoles.goto();
        await expect(usersRoles.invitationsHeading(1)).toBeVisible();
        const row = usersRoles.invitationRow(recipient);
        await expect(row).toBeVisible();
        await expect(row).toContainText('Invited');
        await expect(row).toContainText(ROLE);

        // The recipient's mailbox holds the invitation: offered role + 2 links.
        const links = await invitationLinks(pkpMail, {to: recipient, contains: subject});
        expect(links.html).toContain(ROLE);
        expect(links.accept).toContain('/invitation/accept');
        expect(links.decline).toContain('/invitation/decline');
    });

    test('S2: newcomer accepts and gets an account', {tag: '@smoke'}, async ({page, asUser, browser, baseURL, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        const tag = makeTag('s2', testInfo);
        const recipient = `rcpt${tag}@mail.test`;
        const subject = `Invitation${tag}`;
        const username = `acc${tag}`;
        const password = `Password${tag}`;
        const {path, manager} = await seedJournal(ojsApi, tag);

        const managerCtx = await asUser(manager);
        const managerPage = await managerCtx.newPage();
        await sendInvitation(managerPage, path, {search: recipient, givenName: 'Nadia', subject});
        const links = await invitationLinks(pkpMail, {to: recipient, contains: subject});

        // Signed-out visitor walks the accept wizard (ORCID off → starts at
        // "Create OJS account", Rule 5).
        await page.goto(links.accept);
        const wizard = new AcceptInvitationWizard(page);
        await wizard.createAccount({username, password});
        await wizard.fillDetails({givenName: 'Nadia', country: 'CA'});
        await wizard.expectOnReviewStep();
        await expect(page.getByText(username)).toBeVisible();

        // The review step's Edit button reopens the details, then back.
        await page.getByRole('button', {name: 'Edit', exact: true}).click();
        await expect(page.getByLabel(/^Country of affiliation/)).toBeVisible();
        await wizard.saveAndContinueButton.click();
        await wizard.expectOnReviewStep();

        await wizard.accept();

        // Spec behavior around A4: the new credentials work via a fresh sign-in.
        const freshCtx = await browser.newContext({baseURL, storageState: {cookies: [], origins: []}});
        const loginPage = new LoginPage(await freshCtx.newPage());
        await loginPage.goto();
        await loginPage.signIn(username, password);
        await freshCtx.close();

        // Manager side: the account holds the offered role; the pending row is gone.
        const usersRoles = new UsersRolesPage(managerPage, path);
        await usersRoles.goto();
        await expect(usersRoles.invitationsHeading(0)).toBeVisible();
        const userRow = usersRoles.userRow(recipient);
        await expect(userRow).toBeVisible();
        await expect(userRow).toContainText(ROLE);
    });

    test('S3: existing user accepts an additional role', async ({page, asUser, browser, baseURL, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        const tag = makeTag('s3', testInfo);
        const invitee = `ex${tag}`;
        const inviteeEmail = `${invitee}@mail.test`;
        const subject = `Invitation${tag}`;
        const {path, manager} = await seedJournal(ojsApi, tag, [{username: invitee, roles: ['author']}]);

        const managerCtx = await asUser(manager);
        const managerPage = await managerCtx.newPage();
        await sendInvitation(managerPage, path, {search: inviteeEmail, expectExisting: true, subject});
        const links = await invitationLinks(pkpMail, {to: inviteeEmail, contains: subject});

        // Signed out, the review step opens directly: no password prompt, no
        // account fields (Rule 6; ORCID off per Rule 5).
        await page.goto(links.accept);
        const wizard = new AcceptInvitationWizard(page);
        await wizard.expectOnReviewStep();
        await expect(wizard.passwordInput).toHaveCount(0);
        await expect(wizard.usernameInput).toHaveCount(0);
        await wizard.accept();

        // Signing in the usual way works (spec behavior around A4)...
        const freshCtx = await browser.newContext({baseURL, storageState: {cookies: [], origins: []}});
        const loginPage = new LoginPage(await freshCtx.newPage());
        await loginPage.goto();
        await loginPage.signIn(invitee, invitee + invitee);
        await freshCtx.close();

        // ...and the manager sees the role on the account under Current Users.
        const usersRoles = new UsersRolesPage(managerPage, path);
        await usersRoles.goto();
        await expect(usersRoles.invitationsHeading(0)).toBeVisible();
        const userRow = usersRoles.userRow(inviteeEmail);
        await expect(userRow).toBeVisible();
        await expect(userRow).toContainText(ROLE);
    });

    test('S4: recipient declines', async ({page, asUser, ojsApi, pkpMail}, testInfo) => {
        const tag = makeTag('s4', testInfo);
        const recipient = `rcpt${tag}@mail.test`;
        const subject = `Invitation${tag}`;
        const {path, manager} = await seedJournal(ojsApi, tag);

        const managerCtx = await asUser(manager);
        const managerPage = await managerCtx.newPage();
        await sendInvitation(managerPage, path, {search: recipient, givenName: 'Diane', subject});
        const links = await invitationLinks(pkpMail, {to: recipient, contains: subject});

        // The decline link asks for confirmation; only the button declines (Rule 10).
        await page.goto(links.decline);
        await expect(page.getByRole('heading', {name: 'Decline Invitation'})).toBeVisible();
        await expect(page.getByText('Are you sure you want to decline this invitation?')).toBeVisible();
        await page.getByRole('button', {name: 'Confirm Decline Invitation'}).click();
        await page.waitForURL(/\/login/, {waitUntil: 'commit'});

        // Both emailed links now land on "Invitation Unavailable" (Rule 4).
        await page.goto(links.accept);
        await expect(page.getByRole('heading', {name: 'Invitation Unavailable'})).toBeVisible();
        await page.goto(links.decline);
        await expect(page.getByRole('heading', {name: 'Invitation Unavailable'})).toBeVisible();

        // No role granted, row gone: the manager row bounds the users list read.
        const usersRoles = new UsersRolesPage(managerPage, path);
        await usersRoles.goto();
        await expect(usersRoles.invitationsHeading(0)).toBeVisible();
        await expect(usersRoles.userRow(`${manager}@mail.test`)).toBeVisible();
        await expect(usersRoles.usersTable).not.toContainText(recipient);
    });

    test('S5: manager cancels a pending invitation', async ({page, asUser, ojsApi, pkpMail}, testInfo) => {
        const tag = makeTag('s5', testInfo);
        const recipient = `rcpt${tag}@mail.test`;
        const subject = `Invitation${tag}`;
        const {path, manager} = await seedJournal(ojsApi, tag);

        const managerCtx = await asUser(manager);
        const managerPage = await managerCtx.newPage();
        await sendInvitation(managerPage, path, {search: recipient, givenName: 'Casey', subject});
        const links = await invitationLinks(pkpMail, {to: recipient, contains: subject});

        const usersRoles = new UsersRolesPage(managerPage, path);
        await usersRoles.goto();
        const row = usersRoles.invitationRow(recipient);
        await expect(row).toBeVisible();
        await usersRoles.rowAction(row, 'Cancel Invite');

        // The confirmation recaps the invitee (email, role, status; Rule 16).
        const dialog = managerPage.getByRole('dialog').filter({hasText: 'Cancel Invitation'});
        await expect(dialog).toBeVisible();
        await expect(dialog).toContainText(recipient);
        await expect(dialog).toContainText(ROLE);
        await expect(dialog).toContainText('Invited');
        await dialog.getByRole('button', {name: 'Cancel Invitation', exact: true}).click();

        // The row disappears; the accept link shows "Invitation Unavailable"
        // with Login and Register (Rules 4, 16).
        await expect(usersRoles.invitationsHeading(0)).toBeVisible();
        await expect(usersRoles.invitationRow(recipient)).toHaveCount(0);
        await page.goto(links.accept);
        await expect(page.getByRole('heading', {name: 'Invitation Unavailable'})).toBeVisible();
        await expect(page.getByRole('link', {name: 'Login', exact: true})).toBeVisible();
        await expect(page.getByRole('link', {name: 'Register', exact: true})).toBeVisible();
    });

    test('S6: manager edits a pending invitation (edit = replace)', async ({page, asUser, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        const tag = makeTag('s6', testInfo);
        const recipient = `rcpt${tag}@mail.test`;
        const firstMarker = `${tag}first`;
        const secondMarker = `${tag}second`;
        const {path, manager} = await seedJournal(ojsApi, tag);

        const managerCtx = await asUser(manager);
        const managerPage = await managerCtx.newPage();
        await sendInvitation(managerPage, path, {search: recipient, givenName: 'Edith', subject: firstMarker});
        const firstLinks = await invitationLinks(pkpMail, {to: recipient, contains: firstMarker});

        const usersRoles = new UsersRolesPage(managerPage, path);
        await usersRoles.goto();
        await usersRoles.rowAction(usersRoles.invitationRow(recipient), 'Edit');

        // The warning dialog announces cancel-and-resend (Rule 12).
        const dialog = managerPage.getByRole('dialog').filter({hasText: 'Edit Invitation'});
        await expect(dialog).toContainText('the current invitation will be canceled');
        await dialog.getByRole('button', {name: 'Edit Invitation'}).click();

        // The wizard reopens prefilled, without the search step.
        const wizard = new SendInvitationWizard(managerPage);
        await expect(wizard.stepHeading(/STEP 1 - Enter details/)).toBeVisible();
        await expect(wizard.emailInput).toHaveValue(recipient);

        // Change the role set and send the replacement (the prefilled row is a
        // "Select a new role" row — the newcomer has no current groups).
        await wizard.fillRoleRow({role: 'Author', startDate: today()});
        await wizard.saveAndContinue();
        await wizard.setSubject(secondMarker);
        await wizard.send();
        await wizard.dismissSentDialog();

        // The second email's links work (positive control)...
        const secondLinks = await invitationLinks(pkpMail, {to: recipient, contains: secondMarker});
        await page.goto(secondLinks.accept);
        const acceptWizard = new AcceptInvitationWizard(page);
        await expect(acceptWizard.stepHeading(/Create OJS account/)).toBeVisible();

        // ...while the first email's links no longer open the flow (Rule 12;
        // the exact error rendering is register A3, not asserted).
        await page.goto(firstLinks.accept);
        await expect(acceptWizard.stepHeading(/Create OJS account/)).toHaveCount(0);
        await expect(acceptWizard.acceptButton).toHaveCount(0);
    });

    test('S7: wrong person signed in is refused until they log out', async ({asUser, ojsApi, pkpMail}, testInfo) => {
        const tag = makeTag('s7', testInfo);
        const invitee = `ex${tag}`;
        const inviteeEmail = `${invitee}@mail.test`;
        const subject = `Invitation${tag}`;
        const {path, manager} = await seedJournal(ojsApi, tag, [{username: invitee, roles: ['author']}]);

        const managerCtx = await asUser(manager);
        const managerPage = await managerCtx.newPage();
        await sendInvitation(managerPage, path, {search: inviteeEmail, expectExisting: true, subject});
        const links = await invitationLinks(pkpMail, {to: inviteeEmail, contains: subject});

        // The signed-in manager (not the invitee) opens the accept link: refusal.
        await managerPage.goto(links.accept);
        const refusal = managerPage.getByRole('dialog').filter({hasText: 'logged in as a different user'});
        await expect(refusal).toContainText('Invitation not accepted');

        // The Logout action genuinely ends the session (Rule 6)...
        await refusal.getByRole('button', {name: 'Logout'}).click();
        await expect(managerPage.locator('form#login')).toBeVisible();

        // ...and reopening the link starts the real flow (review step, no
        // password prompt, no account fields).
        await managerPage.goto(links.accept);
        const wizard = new AcceptInvitationWizard(managerPage);
        await wizard.expectOnReviewStep();
        await expect(wizard.passwordInput).toHaveCount(0);
    });

    test('S8: manager proposes a role via the user row', async ({page, asUser, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        const tag = makeTag('s8', testInfo);
        const member = `ex${tag}`;
        const memberEmail = `${member}@mail.test`;
        const subject = `Invitation${tag}`;
        const {path, manager} = await seedJournal(ojsApi, tag, [{username: member, roles: ['author']}]);

        const managerCtx = await asUser(manager);
        const managerPage = await managerCtx.newPage();
        const usersRoles = new UsersRolesPage(managerPage, path);
        await usersRoles.goto();
        await usersRoles.rowAction(usersRoles.userRow(memberEmail), /^Edit$/);

        // The wizard opens on the member's details with no search step; the
        // roles table shows the current role with its immediate controls
        // (Rule 13) and the send path stays inactive without a new role row.
        const wizard = new SendInvitationWizard(managerPage);
        await expect(managerPage.getByText(memberEmail).first()).toBeVisible();
        await expect(managerPage.getByRole('button', {name: 'Remove Role'})).toBeVisible();
        await expect(managerPage.getByLabel(/^Journal Masthead/).first()).toBeVisible();
        await expect(wizard.saveAndContinueButton).toBeDisabled();

        // An added role row activates the send path.
        await wizard.addAnotherRoleButton.click();
        await expect(wizard.saveAndContinueButton).toBeEnabled();
        await wizard.fillRoleRow({role: ROLE, startDate: today()});
        await wizard.saveAndContinue();
        await wizard.setSubject(subject);
        await wizard.send();
        await wizard.dismissSentDialog();

        // The member is emailed, but the added role is only a proposal: not on
        // the account yet (the visible row bounds the list read).
        const links = await invitationLinks(pkpMail, {to: memberEmail, contains: subject});
        await usersRoles.goto();
        await expect(usersRoles.invitationsHeading(1)).toBeVisible();
        const memberRow = usersRoles.userRow(memberEmail);
        await expect(memberRow).toBeVisible();
        await expect(memberRow).not.toContainText(ROLE);

        // The member accepts (scenario 3's flow) — only then the role lands.
        await page.goto(links.accept);
        const acceptWizard = new AcceptInvitationWizard(page);
        await acceptWizard.expectOnReviewStep();
        await acceptWizard.accept();

        await usersRoles.goto();
        await expect(usersRoles.invitationsHeading(0)).toBeVisible();
        await expect(usersRoles.userRow(memberEmail)).toContainText(ROLE);
    });
});
