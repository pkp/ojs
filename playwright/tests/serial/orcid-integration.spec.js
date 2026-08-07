// @ts-check
/**
 * @file playwright/tests/serial/orcid-integration.spec.js
 *
 * ORCID integration — the two OJS canonical scenarios that assert on ORCID
 * request EMAIL (spec scenarios 4 and 8). Every ORCID mailable is queued-job
 * mail and the fleets run with `[queues] job_runner = Off`, so nothing
 * reaches Mailpit until `php lib/pkp/tools/jobs.php run` drains the queue —
 * and that drain pops the SHARED queue, so it must never run while parallel
 * agents seed. Hence the serial project (patterns.md parallel lesson 7).
 * Spec: lib/pkp/docs/product/specs/orcid-integration.md
 *
 * Coverage boundaries are declared in the parallel suite's header
 * (playwright/tests/orcid-integration.spec.js); this file adds only:
 * - S4's emailed authorization link is recorded, never followed (OAuth
 *   cannot complete on the egress-firewalled fleets).
 * - A6 ❓: S8 asserts the toggle's accept-time behavior, never its label
 *   wording (the open question's territory).
 *
 * Mailpit is shared across fleets and workers: every assertion is scoped by
 * a unique throwaway recipient (the seeded contributor's address carries
 * app + scenario + run), and the silence claim rides on a positive control
 * delivered by the same queue drain.
 */
const {test, expect} = require('../../support/fixtures.js');
const {
    openContributors,
    openContributorEditor,
    orcidField,
} = require('../../pages/OrcidPages.js');
const {WorkflowPage, DecisionPage} = require('../../pages/ReviewStagePages.js');
const {runJobs} = require('../../../lib/pkp/playwright/support/jobs.js');

/** Unique per-run tag: single alphanumeric token, carries app + scenario. */
function makeTag(scenario, testInfo) {
    return `u4${scenario}ojsw${testInfo.parallelIndex}${Math.random().toString(36).slice(2, 8)}`;
}

test.describe('ORCID integration (queued email)', () => {
    test('S4: "Request verification" emails the contributor an authorization link', async ({asUser, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        const tag = makeTag('s4', testInfo);
        const manager = `mgr${tag}`;
        const author = `aut${tag}`;
        const recipient = `${author}@mail.test`; // seeded contributor address
        await ojsApi.createContext({
            tag,
            users: [
                {username: manager, roles: ['manager']},
                {username: author, roles: ['author']},
            ],
            orcid: {}, // enabled, Public Sandbox → the "Submission ORCID" template (Rule 14)
        });
        const {submissionId} = await ojsApi.createSubmission({
            tag,
            context: tag,
            submitter: author,
        });

        const managerPage = await (await asUser(manager)).newPage();
        const workflow = new WorkflowPage(managerPage, tag);
        await workflow.gotoEditorial(submissionId);
        await openContributors(managerPage);
        let modal = await openContributorEditor(managerPage, author);

        // No iD → "Request verification"; the confirm dialog carries the
        // Rule 8 question; confirming flips the field to the requested state.
        const field = orcidField(modal);
        await field.getByRole('button', {name: 'Request verification'}).click();
        const dialog = managerPage
            .getByRole('dialog')
            .filter({hasText: 'Would you like to send an email to this author requesting they verify their ORCID?'});
        await expect(dialog).toContainText('Request ORCID verification');
        const requested = managerPage.waitForResponse(
            (response) => response.url().includes('/orcid/requestAuthorVerification/') && response.ok()
        );
        await dialog.getByRole('button', {name: 'Yes', exact: true}).click();
        await requested;
        await expect(field).toContainText('ORCID Verification has been requested!');
        await expect(field.getByText('Resend Verification Email')).toBeVisible();

        // Save; the requested state persists on reopen. (The seeded
        // auto-author carries no country and the form requires one — filling
        // it is form furniture, not the behavior under test.)
        await modal.locator('#contributor-country-control').selectOption({label: 'Canada'});
        await modal.getByRole('button', {name: 'Save', exact: true}).click();
        await expect(modal).toHaveCount(0);
        modal = await openContributorEditor(managerPage, author);
        await expect(orcidField(modal)).toContainText('ORCID Verification has been requested!');

        // The mail is queued-job mail: drain the queue, then read the
        // contributor's mailbox (recipient-scoped).
        runJobs();
        const summary = await pkpMail.find({to: recipient, subject: 'Submission ORCID'});
        const full = await pkpMail.fullMessage(summary.ID);
        const authLink = pkpMail.extractLink(full.HTML, /Register or connect your ORCID iD/);
        expect(authLink).toContain('sandbox.orcid.org'); // leads to ORCID's (sandbox) site
        const aboutLink = pkpMail.extractLink(full.HTML, /More information about ORCID/);
        expect(aboutLink).toContain(`/${tag}/orcid/about`);
        // Recorded, never followed: OAuth cannot complete from this install.
    });

    test('S8: accepting a submission emails contributors only while the toggle is on', async ({asUser, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        const tagOn = makeTag('s8n', testInfo);
        const tagOff = makeTag('s8f', testInfo);
        const seedFor = async (tag, sendMail) => {
            await ojsApi.createContext({
                tag,
                users: [
                    {username: `mgr${tag}`, roles: ['manager']},
                    {username: `aut${tag}`, roles: ['author']},
                ],
                orcid: {sendMailToAuthorsOnPublication: sendMail},
            });
            const {submissionId} = await ojsApi.createSubmission({
                tag,
                context: tag,
                submitter: `aut${tag}`,
                decisions: ['sendExternalReview'],
            });
            return submissionId;
        };
        const subOn = await seedFor(tagOn, true);
        const subOff = await seedFor(tagOff, false);

        // Record Accept on both submissions in review (Rule 13's trigger).
        const accept = async (tag, submissionId) => {
            const managerPage = await (await asUser(`mgr${tag}`)).newPage();
            const workflow = new WorkflowPage(managerPage, tag);
            await workflow.gotoEditorial(submissionId);
            await workflow.decisionButton('Accept Submission').click();
            const decision = new DecisionPage(managerPage);
            await decision.expectOpen('Accept Submission');
            await decision.completeAll();
        };
        await accept(tagOn, subOn);
        await accept(tagOff, subOff);

        // One queue drain delivers everything both accepts enqueued.
        runJobs();

        // Toggle on: the contributor without a verified iD receives the
        // Rule 14 request email ("Submission ORCID" under the public API),
        // carrying the personal authorization link.
        const onRecipient = `aut${tagOn}@mail.test`;
        const summary = await pkpMail.find({to: onRecipient, subject: 'Submission ORCID'});
        const full = await pkpMail.fullMessage(summary.ID);
        expect(pkpMail.extractLink(full.HTML, /Register or connect your ORCID iD/)).toContain(
            'sandbox.orcid.org'
        );

        // Toggle off (control): the same accept sends no request email — the
        // silence is bounded by the toggle-on request mail from the SAME
        // drain, and the off-journal contributor still got the decision's own
        // notify-authors mail (same-recipient positive control).
        const offRecipient = `aut${tagOff}@mail.test`;
        await pkpMail.find({to: offRecipient, contains: 'accepted'});
        await pkpMail.expectNone({
            to: offRecipient,
            subject: 'Submission ORCID',
            afterControl: {to: onRecipient, subject: 'Submission ORCID'},
        });
    });
});
