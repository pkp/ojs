// @ts-check
/**
 * @file playwright/tests/login-and-sessions.spec.js
 *
 * Login & sessions — OJS suite, one test per canonical scenario the spec runs
 * on OJS (scenarios 1–8; scenario 9 needs a config-gated install — see below).
 * Spec: lib/pkp/docs/e2e/specs/U01-login-and-sessions.md
 *
 * Deliberately NOT covered (one line per omission, citing the register ID):
 * - Scenario 9 (Confirm Access gate): gated on `password_timeout` in
 *   config.test.inc.php — a run-global config edit every worker sees
 *   (PRINCIPLES D9); declared instead of covered.
 * - A6 ❓ (sign-in rate limiting): needs the site-settings singleton mutated
 *   plus the open concealment question — not covered.
 * - Spam checks (reCAPTCHA/ALTCHA) and the `[security]`/`[general]` session
 *   lifetimes behind Rules 5 and 18 (remember-me window, expire-on-close,
 *   session_check_ip, Expire User Sessions): config-gated; not covered.
 * - A1 🐞 (password boxes cut at 32 chars): the shared LoginPage lifts the
 *   maxlength for long roster passwords; the cap is asserted neither way.
 * - A2 🐞 ("Keep me logged in" pre-ticked): register finding, not asserted.
 * - A3 🐞 (raw locale key in the reset page's browser tab): the page heading
 *   is asserted, the tab title is not.
 * - A4 ❓ (second Login As offered mid-impersonation): open question, never
 *   driven — each impersonation here is single-level.
 * - A5 ❓ (no users screen sets the forced-change flag): S6 uses the one
 *   screen-driven path the spec names (Create New Reviewer).
 * - Rule 2 disabled-account answers: disabling an account is the
 *   users-management feature's surface, and the leg is not a canonical
 *   scenario here.
 * - Rule 14's typed-address denial page and Rule 15's plain-signout-ends-
 *   everything are rule details outside the canonical scenarios; not covered.
 * - Settings modifier "registration disabled removes the Register links":
 *   owned by the roles-configuration feature; not covered here.
 *
 * Session hygiene: every sign-in, sign-out, forced-change and impersonation
 * flow runs in a FRESH browser context with a fresh UI login — never through
 * the shared .auth storage-state cache, whose session rows signOut/signInAs
 * would destroy for parallel tests. Password mutations happen only on
 * throwaway users in scratch journals (the roster's passwords are never
 * touched). Mailpit assertions are scoped by unique throwaway recipient
 * addresses carrying app + test in the local part. Waits are event-based —
 * no hard-coded sleeps.
 */
const {test, expect} = require('../support/fixtures.js');
const {LoginPage} = require('../../lib/pkp/playwright/pages/LoginPage.js');
const {getPassword} = require('../../lib/pkp/playwright/data/users.js');
const {
    WorkflowPage,
    waitForJQueryIdle,
} = require('../pages/ReviewStagePages.js');
const {UsersRolesPage} = require('../pages/UserInvitationPages.js');

const JOURNAL = 'publicknowledge';
const GENERIC_ERROR = 'Invalid username/email or password. Please try again.';
const LOGIN_AS_CONFIRM =
    'Log in as this user? All actions you perform will be attributed to this user.';

/** Unique per-run tag: single alphanumeric token, app + scenario + worker. */
function makeTag(scenario, testInfo) {
    return `u1${scenario}ojsw${testInfo.parallelIndex}${Math.random().toString(36).slice(2, 8)}`;
}

/**
 * A fresh, explicitly-anonymous context (never inherits cached storage state).
 * Callers close it themselves (or lean on browser teardown at worker end).
 */
async function anonContext(browser, baseURL) {
    return browser.newContext({baseURL, storageState: {cookies: [], origins: []}});
}

/**
 * Fresh UI login in its own context — used for every flow that will end or
 * migrate its session (sign-out, impersonation), so the shared .auth cache
 * for the same user is never poisoned.
 */
async function freshLogin(browser, baseURL, username, password = getPassword(username)) {
    const context = await anonContext(browser, baseURL);
    const page = await context.newPage();
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.signIn(username, password);
    return {context, page};
}

/** The top-right user menu (initials button + its dropdown content). */
function userNav(page) {
    return page.locator('[data-cy="app-user-nav"]');
}

async function openUserNav(page) {
    await userNav(page).getByRole('button').first().click();
}

/**
 * Close the user-nav dropdown by toggling its button again (the component
 * closes on blur/toggle, not on Escape) — leaving it open covers controls
 * beneath it.
 */
async function closeUserNav(page) {
    await userNav(page).getByRole('button').first().click();
    await expect(userNav(page).getByRole('link', {name: /Logout/})).toHaveCount(0);
}

/** The Login As confirmation dialog (Rule 12), and its OK press. */
async function confirmLoginAsDialog(page) {
    const dialog = page.getByRole('dialog').filter({hasText: LOGIN_AS_CONFIRM});
    await expect(dialog).toBeVisible();
    await dialog.getByRole('button', {name: 'OK', exact: true}).click();
}

/**
 * Drive the lost-password flow for `email` on the given journal and return
 * the emailed reset link (Rules 7–8). Starts from the journal's Login page.
 */
async function requestResetLink(page, pkpMail, contextPath, email) {
    await page.goto(`/index.php/${contextPath}/login`);
    await page.getByRole('link', {name: 'Forgot your password?'}).click();
    await expect(page.getByRole('heading', {name: 'Reset Password'})).toBeVisible();
    await expect(
        page.getByText(
            'Enter your account email address below and an email will be sent with instructions on how to reset your password.'
        )
    ).toBeVisible();
    await page.locator('input#email').fill(email);
    await page.locator('form#lostPasswordForm button[type="submit"]').click();

    // The generic confirmation, with a "Login" link back (Rule 7). The link
    // is scoped to the message body — the site nav carries its own Login link.
    await expect(
        page.getByText(
            'A confirmation has been sent to your email address if a matching account was found.'
        )
    ).toBeVisible();
    await expect(
        page.getByRole('main').getByRole('link', {name: 'Login', exact: true})
    ).toBeVisible();

    // One "Password Reset Confirmation" email with the single link (Rule 8).
    const summary = await pkpMail.find({to: email, subject: 'Password Reset Confirmation'});
    const full = await pkpMail.fullMessage(summary.ID);
    const match = (full.HTML || full.Text).match(
        /https?:\/\/[^\s<>"']+\/login\/resetPassword\/[^\s<>"']+/
    );
    expect(match, 'reset link present in the email').toBeTruthy();
    return match[0].replace(/&amp;/g, '&');
}

test.describe('login & sessions', () => {
    test('S1: sign in and land on the Dashboard', {tag: '@smoke'}, async ({page}) => {
        // The journal's Login page (Rule 1).
        await page.goto(`/index.php/${JOURNAL}/en/login`);
        const loginPage = new LoginPage(page);
        await expect(page.locator('form#login')).toBeVisible();

        // Right username, wrong password: the generic failure, username kept
        // (Rule 2).
        await loginPage.usernameInput.fill('editor.diana');
        await loginPage.fillPassword('definitely-not-the-password');
        await loginPage.submitButton.click();
        await expect(page.getByText(GENERIC_ERROR)).toBeVisible();
        await expect(loginPage.usernameInput).toHaveValue('editor.diana');

        // The correct password lands on the Dashboard (Rule 3).
        await loginPage.fillPassword(getPassword('editor.diana'));
        await loginPage.submitButton.click();
        await page.waitForURL(/\/dashboard/, {waitUntil: 'commit', timeout: 15_000});
        await expect(userNav(page)).toBeVisible();
    });

    test('S2: sign out', async ({browser, baseURL}) => {
        // Fresh session (signing out would kill a cached one for other tests).
        const {context, page} = await freshLogin(browser, baseURL, 'editor.diana');
        try {
            await page.goto(`/index.php/${JOURNAL}/dashboard/editorial`);
            await expect(userNav(page)).toBeVisible();

            // The user menu offers "Logout" (Rule 6).
            await openUserNav(page);
            await userNav(page).getByRole('link', {name: 'Logout', exact: true}).click();
            await page.waitForURL(/\/login/, {waitUntil: 'commit', timeout: 15_000});

            // Back on the Login page, with the departed account's EMAIL
            // prefilled — the email even though sign-in used the username.
            await expect(page.locator('form#login')).toBeVisible();
            await expect(page.locator('input#username')).toHaveValue('editor.diana@mail.test');

            // A dashboard address now shows the Login page, not the dashboard.
            await page.goto(`/index.php/${JOURNAL}/dashboard/editorial`);
            await expect(page.locator('form#login')).toBeVisible();
        } finally {
            await context.close();
        }
    });

    test('S3: a bookmarked private page waits for sign-in', async ({page, ojsApi}, testInfo) => {
        const tag = makeTag('s3', testInfo);
        const {submissionId} = await ojsApi.createSubmission({
            tag,
            context: JOURNAL,
            submitter: 'author.alex',
            title: `Submission ${tag}`,
        });

        // Signed out, the workflow address shows the plain Login page (Rule 4).
        const workflowPath = `/index.php/${JOURNAL}/dashboard/editorial?workflowSubmissionId=${submissionId}`;
        await page.goto(workflowPath);
        const loginPage = new LoginPage(page);
        await expect(page.locator('form#login')).toBeVisible();

        // Signing in continues straight to the held submission, not the
        // Dashboard.
        await loginPage.usernameInput.fill('editor.diana');
        await loginPage.fillPassword(getPassword('editor.diana'));
        await loginPage.submitButton.click();
        await page.waitForURL((url) => url.search.includes(`workflowSubmissionId=${submissionId}`), {
            waitUntil: 'commit',
            timeout: 15_000,
        });
        const workflow = new WorkflowPage(page, JOURNAL);
        await workflow.expectOpen();
    });

    test('S4: recover a forgotten password', {tag: '@smoke'}, async ({page, browser, baseURL, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        const tag = makeTag('s4', testInfo);
        const username = `au${tag}`;
        const email = `${username}@mail.test`;
        const oldPassword = username + username;
        const newPassword = `Reset${tag}`;
        await ojsApi.createContext({tag, users: [{username, roles: ['author']}]});

        const resetLink = await requestResetLink(page, pkpMail, tag, email);

        // The link opens "Reset Password"; save a new password (Rule 9).
        await page.goto(resetLink);
        await expect(page.getByRole('heading', {name: 'Reset Password'})).toBeVisible();
        await page.locator('input[name="password"]').fill(newPassword);
        await page.locator('input[name="password2"]').fill(newPassword);
        await page.getByRole('button', {name: 'Save', exact: true}).click();
        await expect(
            page.getByText('Password has been updated successfully. Please login with updated password.')
        ).toBeVisible();
        await expect(
            page.getByRole('main').getByRole('link', {name: 'Login', exact: true})
        ).toBeVisible();

        // Not signed in by resetting: the new password works via a real
        // sign-in...
        await page.goto(`/index.php/${tag}/login`);
        const loginPage = new LoginPage(page);
        await loginPage.signIn(username, newPassword);
        await expect(page).not.toHaveURL(/\/login/);

        // ...while the old password now fails with the generic error.
        const staleCtx = await anonContext(browser, baseURL);
        try {
            const stalePage = await staleCtx.newPage();
            await stalePage.goto(`/index.php/${tag}/login`);
            const staleLogin = new LoginPage(stalePage);
            await staleLogin.usernameInput.fill(username);
            await staleLogin.fillPassword(oldPassword);
            await staleLogin.submitButton.click();
            await expect(stalePage.getByText(GENERIC_ERROR)).toBeVisible();
        } finally {
            await staleCtx.close();
        }
    });

    test('S5: a stale or altered reset link is refused', async ({page, browser, baseURL, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        const tag = makeTag('s5', testInfo);
        const username = `au${tag}`;
        const email = `${username}@mail.test`;
        const newPassword = `Reset${tag}`;
        await ojsApi.createContext({tag, users: [{username, roles: ['author']}]});

        const resetLink = await requestResetLink(page, pkpMail, tag, email);

        // Use the link once (password changes), then sign in (Rule 8's two
        // early deaths).
        await page.goto(resetLink);
        await expect(page.getByRole('heading', {name: 'Reset Password'})).toBeVisible();
        await page.locator('input[name="password"]').fill(newPassword);
        await page.locator('input[name="password2"]').fill(newPassword);
        await page.getByRole('button', {name: 'Save', exact: true}).click();
        await expect(
            page.getByText('Password has been updated successfully. Please login with updated password.')
        ).toBeVisible();
        // The sign-in happens in its own context: the probing page must stay
        // signed out, or the reset address just sends it home (Rule 1).
        const signedInCtx = await anonContext(browser, baseURL);
        try {
            const signedInPage = await signedInCtx.newPage();
            await signedInPage.goto(`/index.php/${tag}/login`);
            const loginPage = new LoginPage(signedInPage);
            await loginPage.signIn(username, newPassword);
            await expect(signedInPage).not.toHaveURL(/\/login/);
        } finally {
            await signedInCtx.close();
        }

        // The used link now answers the dead-link page, with a "Reset
        // Password" link back to the lost-password form (Rule 10).
        await page.goto(resetLink);
        await expect(
            page.getByText(
                'Sorry, the link you clicked on has expired or is not valid. Please try resetting your password again.'
            )
        ).toBeVisible();
        await expect(page.getByRole('link', {name: 'Reset Password', exact: true})).toBeVisible();

        // A link with a mangled code answers the same.
        const mangled = new URL(resetLink);
        const confirm = mangled.searchParams.get('confirm') || '';
        mangled.searchParams.set('confirm', `deadbeef${confirm.slice(8)}`);
        await page.goto(mangled.toString());
        await expect(
            page.getByText(
                'Sorry, the link you clicked on has expired or is not valid. Please try resetting your password again.'
            )
        ).toBeVisible();

        // The back link really leads to the lost-password form.
        await page.getByRole('link', {name: 'Reset Password', exact: true}).click();
        await expect(page.locator('form#lostPasswordForm')).toBeVisible();
    });

    test('S6: forced password change at first sign-in', async ({page, browser, baseURL, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        const tag = makeTag('s6', testInfo);
        const editor = `edi${tag}`;
        const author = `au${tag}`;
        const reviewerUsername = `rev${tag}`;
        const reviewerEmail = `${reviewerUsername}@mail.test`;
        const newPassword = `Changed${tag}`;
        // Scratch journal: the flagged account is a throwaway (never flag a
        // roster account) and the registration email lands in a throwaway
        // mailbox.
        await ojsApi.createContext({
            tag,
            users: [
                {username: editor, roles: ['editor']},
                {username: author, roles: ['author']},
            ],
        });
        const {submissionId} = await ojsApi.createSubmission({
            tag,
            context: tag,
            submitter: author,
            title: `Submission ${tag}`,
            decisions: ['sendExternalReview'],
        });

        // The editor creates the reviewer through Add Reviewer → "Create New
        // Reviewer" (the one screen-driven path that sets the flag, Rule 11).
        const editorCtx = await freshLogin(browser, baseURL, editor);
        try {
            const workflow = new WorkflowPage(editorCtx.page, tag);
            await workflow.gotoEditorial(submissionId);
            await editorCtx.page.getByRole('button', {name: 'Add Reviewer', exact: true}).click();
            const modal = editorCtx.page
                .getByRole('dialog')
                .filter({has: editorCtx.page.locator('.listPanel--selectReviewer')});
            await expect(modal.getByRole('link', {name: 'Create New Reviewer'})).toBeVisible({
                timeout: 30_000,
            });
            await modal.getByRole('link', {name: 'Create New Reviewer'}).click();
            // The AJAX reload swaps the search panel for the create form, so
            // the dialog is re-resolved by the form it now carries.
            const createModal = editorCtx.page
                .getByRole('dialog')
                .filter({has: editorCtx.page.locator('form#createReviewerForm')});
            const form = createModal.locator('form#createReviewerForm');
            await expect(form.locator('input[name="username"]')).toBeVisible({timeout: 30_000});
            await form.locator('input[name="givenName[en]"]').fill('Nova');
            await form.locator('input[name="familyName[en]"]').fill('Tester');
            await form.locator('input[name="username"]').fill(reviewerUsername);
            await form.locator('input[name="email"]').fill(reviewerEmail);
            await form.getByRole('button', {name: 'Add Reviewer', exact: true}).click();
            await expect(createModal).toHaveCount(0, {timeout: 30_000});
            await waitForJQueryIdle(editorCtx.page);
            await expect(workflow.panelRow('Reviewers', 'Nova Tester')).toBeVisible();
        } finally {
            await editorCtx.context.close();
        }

        // The registration email delivers the username and a generated
        // password.
        const summary = await pkpMail.find({to: reviewerEmail, subject: 'Registration as Reviewer'});
        const full = await pkpMail.fullMessage(summary.ID);
        const credentials = (full.HTML || '').match(
            /Username:\s*([^<\s]+)\s*<br\s*\/?>\s*Password:\s*([^<\s]+)/i
        );
        expect(credentials, 'emailed username and password').toBeTruthy();
        expect(credentials[1]).toBe(reviewerUsername);
        const emailedPassword = credentials[2];

        // Signing in diverts to "Change Password" instead of landing anywhere.
        await page.goto(`/index.php/${tag}/login`);
        const loginPage = new LoginPage(page);
        await loginPage.usernameInput.fill(reviewerUsername);
        await loginPage.fillPassword(emailedPassword);
        await loginPage.submitButton.click();
        await page.waitForURL(/\/login\/changePassword/, {waitUntil: 'commit', timeout: 15_000});
        await expect(page.getByRole('heading', {name: 'Change Password'})).toBeVisible();
        await expect(
            page.getByText('You must choose a new password before you can log in to this site.')
        ).toBeVisible();
        // The username arrives prefilled.
        await expect(page.locator('form#loginChangePassword input[name="username"]')).toHaveValue(
            reviewerUsername
        );

        // Emailed password as the current one, a new one twice, "OK" — signed
        // in and sent home (a reviewer's home is their review dashboard).
        await page.locator('form#loginChangePassword input[name="oldPassword"]').fill(emailedPassword);
        await page.locator('form#loginChangePassword input[name="password"]').fill(newPassword);
        await page.locator('form#loginChangePassword input[name="password2"]').fill(newPassword);
        await page.locator('form#loginChangePassword').getByRole('button', {name: 'OK', exact: true}).click();
        await page.waitForURL(/\/dashboard/, {waitUntil: 'commit', timeout: 15_000});
        await expect(userNav(page)).toBeVisible();

        // Signing in again with the new password is normal.
        const secondCtx = await anonContext(browser, baseURL);
        try {
            const secondPage = await secondCtx.newPage();
            await secondPage.goto(`/index.php/${tag}/login`);
            const secondLogin = new LoginPage(secondPage);
            await secondLogin.signIn(reviewerUsername, newPassword);
            await secondPage.waitForURL(/\/dashboard/, {waitUntil: 'commit', timeout: 15_000});
            await expect(userNav(secondPage)).toBeVisible();
        } finally {
            await secondCtx.close();
        }
    });

    test('S7: administrator impersonates a user and returns', {tag: '@smoke'}, async ({browser, baseURL}) => {
        test.slow();
        // Fresh admin session: signInAs migrates the session, which would
        // destroy the cached admin storage state for parallel tests.
        const {context, page} = await freshLogin(browser, baseURL, 'admin');
        try {
            // Users & Roles: the Author's row menu offers "Login As".
            const usersRoles = new UsersRolesPage(page, JOURNAL);
            await usersRoles.goto();
            const search = page.getByRole('searchbox');
            await search.fill('Alex');
            await search.press('Enter');
            const row = usersRoles.userRow('author.alex@mail.test');
            await expect(row).toBeVisible();
            await usersRoles.rowAction(row, 'Login As');

            // The confirmation warns about attribution (Rule 12); OK proceeds.
            await confirmLoginAsDialog(page);
            await page.waitForURL(/\/dashboard/, {waitUntil: 'commit', timeout: 30_000});

            // The browser is now the Author's session; the top bar carries
            // both identities (target initials + impersonator overlay).
            await expect(userNav(page)).toBeVisible();
            await expect(userNav(page).getByRole('button').first()).toContainText('author.alex');
            await expect(userNav(page).getByRole('button').first()).toContainText('admin');

            // The menu says so, offers only "Logout as" — no plain Logout
            // (Rules 6, 13).
            await openUserNav(page);
            await expect(
                userNav(page).getByText('You are currently logged in as author.alex')
            ).toBeVisible();
            await expect(
                userNav(page).getByRole('link', {name: 'Logout as author.alex'}).first()
            ).toBeVisible();
            await expect(
                userNav(page).getByRole('link', {name: 'Logout', exact: true})
            ).toHaveCount(0);

            // "Logout as" restores the administrator, no password asked
            // (Rule 15).
            await userNav(page).getByRole('link', {name: 'Logout as author.alex'}).first().click();
            await page.waitForURL((url) => !url.pathname.includes('/login'), {
                waitUntil: 'commit',
                timeout: 30_000,
            });
            await page.goto(`/index.php/${JOURNAL}/dashboard/editorial`);
            await expect(userNav(page)).toBeVisible();
            await expect(userNav(page).getByRole('button').first()).not.toContainText('author.alex');
            await openUserNav(page);
            await expect(userNav(page).getByText('You are currently logged in as')).toHaveCount(0);
            await expect(
                userNav(page).getByRole('link', {name: 'Logout', exact: true})
            ).toBeVisible();
        } finally {
            await context.close();
        }
    });

    test('S8: editor impersonates a participant from the Participants panel', async ({browser, baseURL, ojsApi}, testInfo) => {
        test.slow();
        test.setTimeout(240_000);
        const tag = makeTag('s8', testInfo);
        // A seeded ART submission auto-assigns the section's editors (Diana,
        // Ana, Omar) as participants; the accepted reviewer feeds the
        // Reviewers-table offering check.
        const {submissionId} = await ojsApi.createSubmission({
            tag,
            context: JOURNAL,
            submitter: 'author.alex',
            title: `Submission ${tag}`,
            decisions: ['sendExternalReview'],
            reviewRounds: [{reviewers: [{username: 'reviewer.julia', status: 'accepted'}]}],
        });

        // Fresh editor session (impersonation migrates it).
        const {context, page} = await freshLogin(browser, baseURL, 'editor.diana');
        try {
            const workflow = new WorkflowPage(page, JOURNAL);
            await workflow.gotoEditorial(submissionId);
            const participants = page.locator('[data-cy="participant-manager"]');

            // The viewer's own participant row offers no Login As (Rule 14),
            // while another participant's row does (positive control). Each
            // menu is toggled closed again — the headlessui items render
            // inline and would otherwise satisfy later menuitem lookups.
            await workflow.participantMoreActions('Diana Editor').first().click();
            await expect(participants.getByRole('menuitem').first()).toBeVisible();
            await expect(participants.getByRole('menuitem', {name: 'Login As'})).toHaveCount(0);
            await workflow.participantMoreActions('Diana Editor').first().click();
            await expect(participants.getByRole('menuitem')).toHaveCount(0);

            // The Reviewers table offers the same row action (scenario 8's
            // OJS leg — offering asserted, not driven).
            const juliaMenuButton = workflow
                .panelRow('Reviewers', 'Julia Reviewer')
                .getByRole('button', {name: /More Actions/});
            await juliaMenuButton.click();
            await expect(page.getByRole('menuitem', {name: 'Login As'})).toBeVisible();
            await juliaMenuButton.click();
            await expect(page.getByRole('menuitem', {name: 'Login As'})).toHaveCount(0);

            // Impersonate the Section Editor participant.
            await workflow.participantMoreActions('Ana Section Editor').first().click();
            await participants.getByRole('menuitem', {name: 'Login As'}).click();
            await confirmLoginAsDialog(page);
            await page.waitForURL(
                (url) =>
                    url.pathname.includes('/dashboard/editorial') &&
                    url.search.includes(`workflowSubmissionId=${submissionId}`),
                {waitUntil: 'commit', timeout: 30_000}
            );

            // Same submission, as that participant; the Participants panel
            // now opens with "Logout as {participant}" (Rule 13).
            await workflow.expectOpen();
            const logoutAsAna = page.getByRole('button', {name: 'Logout as Ana Section Editor'});
            await expect(logoutAsAna).toBeVisible();

            // Pressing it returns to the editor's view of the same submission
            // (Rule 15).
            await logoutAsAna.click();
            await page.waitForURL(
                (url) => url.search.includes(`workflowSubmissionId=${submissionId}`),
                {waitUntil: 'commit', timeout: 30_000}
            );
            await workflow.expectOpen();
            await expect(
                page.getByRole('button', {name: 'Logout as Ana Section Editor'})
            ).toHaveCount(0);
            await openUserNav(page);
            await expect(userNav(page).getByText('You are currently logged in as')).toHaveCount(0);
            await closeUserNav(page);

            // Impersonating the Author instead lands on the author's own My
            // Submissions view, which shows no Participants panel — the way
            // back is the user menu's "Logout as" entry.
            await workflow.participantMoreActions('Alex Author').first().click();
            await participants.getByRole('menuitem', {name: 'Login As'}).click();
            await confirmLoginAsDialog(page);
            await page.waitForURL(
                (url) =>
                    url.pathname.includes('/dashboard/mySubmissions') &&
                    url.search.includes(`workflowSubmissionId=${submissionId}`),
                {waitUntil: 'commit', timeout: 30_000}
            );
            await workflow.expectOpen();
            await expect(page.locator('[data-cy="participant-manager"]')).toHaveCount(0);

            await openUserNav(page);
            await userNav(page).getByRole('link', {name: 'Logout as author.alex'}).first().click();
            await page.waitForURL(
                (url) => url.search.includes(`workflowSubmissionId=${submissionId}`),
                {waitUntil: 'commit', timeout: 30_000}
            );
            await workflow.expectOpen();
            await openUserNav(page);
            await expect(userNav(page).getByText('You are currently logged in as')).toHaveCount(0);
        } finally {
            await context.close();
        }
    });
});
