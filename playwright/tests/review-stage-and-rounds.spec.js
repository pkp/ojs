// @ts-check
/**
 * @file playwright/tests/review-stage-and-rounds.spec.js
 *
 * Review stage & rounds — OJS suite, one test per canonical scenario the spec
 * runs on OJS (common scenarios 1–12; scenario 13 is OMP-only, 14 OPS-only).
 * Spec: lib/pkp/docs/product/specs/review-stage-and-rounds.md
 *
 * Deliberately NOT covered (register IDs from the spec's Findings register):
 * - A1 🐞: S5 asserts the documented working path only — the two resubmit
 *   status sentences and that the Revisions Uploaded panel's own "Upload"
 *   control still opens the wizard after the first upload. The bottom
 *   "Upload revisions" button's post-upload disappearance and the lingering
 *   "Resubmit for review." task are the bug's record, asserted neither way.
 * - OJS1 🐞: S12 asserts the read-review window's reviewer name, completion
 *   date and recommendation only; nothing is asserted about review text
 *   (present or absent).
 * - A2 ❓: the status box is asserted through the spec's (editor) wording in
 *   whichever view the scenario names; whether the author should see the
 *   author-tailored wording is open and not asserted either way.
 * - A3 ❓ (spec instruction): no assertion touches the read-review window's
 *   attachments section — scenario 12's pass/fail excludes it.
 * - A4 ❓: what an all-declined round reports is open; S11's declined
 *   reviewer serves only as the Rule-6 reviewer record behind the
 *   recommendation sentences, never as an A4 assertion.
 * - A5 ❓: no assistant-level access probing (no screen path exists).
 * - A6 ❓: S7 cancels rounds that carry no revision request, so the restored
 *   round's status is asserted only through its reviewer-derived sentence.
 * - A7 ❓: the review-files dialog's checkbox/untick behavior is open; the
 *   suite asserts only additive panel listings (S1, S6).
 * - Rule 17 (old authorDashboard addresses) is not a canonical scenario.
 * - Footnote r (Minimum Confirmed Reviews Required) is a settings modifier
 *   without a step-2 scenario-schema key; not covered here.
 *
 * Seeding: scenario endpoints only. publicknowledge and the seeded roster are
 * read-only (scratch submissions are the isolation unit; journal-level or
 * mail-recipient needs use scratch journals with throwaway users). The one
 * mail assertion (S4) is scoped by a throwaway recipient carrying app + test
 * in the address; its silence-side claims are row-bounded list reads, not
 * inbox reads. Waits are event-based (auto-wait, aria states, jQuery idle) —
 * no hard-coded sleeps.
 */
const {test, expect} = require('../support/fixtures.js');
const {
    WorkflowPage,
    DecisionPage,
    uploadViaWizard,
    uploadWizardDialog,
    addReviewer,
    performReview,
    assignParticipant,
    waitForJQueryIdle,
    FIXTURE_PDF_NAME,
} = require('../pages/ReviewStagePages.js');

const JOURNAL = 'publicknowledge';

/** Unique per-run tag: single alphanumeric token, app + scenario + worker. */
function makeTag(scenario, testInfo) {
    return `u26${scenario}ojsw${testInfo.parallelIndex}${Math.random().toString(36).slice(2, 8)}`;
}

/**
 * Seed a submission in external review on the given journal. Rounds are
 * created by the promoting decisions (footnote b); reviewers per round via
 * the round plans.
 */
async function seedInReview(ojsApi, tag, {
    context = JOURNAL,
    submitter = 'author.alex',
    decisions = ['sendExternalReview'],
    reviewRounds = [{reviewers: []}],
} = {}) {
    const result = await ojsApi.createSubmission({
        tag,
        context,
        submitter,
        title: `Submission ${tag}`,
        decisions,
        reviewRounds,
    });
    return result;
}

/**
 * The submissions-table search box (the dashboard also carries a side-nav
 * searchbox that owns the query in the search view — scope by accessible
 * name to dodge the two-searchbox collision).
 */
function tableSearch(page) {
    return page.getByRole('searchbox', {name: /Search submissions, ID/});
}

/**
 * Find the submission's row on a dashboard list by its unique tag. The search
 * commits on Enter (Search.vue submits on keydown.enter) and flips the
 * dashboard to its cross-status search view; the wait is bounded by the
 * resulting row, not a timer.
 */
async function findRowByTag(page, tag) {
    const search = tableSearch(page);
    await expect(search).toBeVisible({timeout: 30_000});
    await search.click();
    await search.pressSequentially(tag, {delay: 25});
    await search.press('Enter');
    const row = page.getByRole('row').filter({hasText: tag});
    await expect(row).toBeVisible({timeout: 30_000});
    return row;
}

test.describe('review stage & rounds', () => {
    test('S1: round 1 opens with the submission', {tag: '@smoke'}, async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s1', testInfo);
        const {submissionId} = await seedInReview(ojsApi, tag, {decisions: [], reviewRounds: []});

        const editorPage = await (await asUser('sectioneditor.ana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        await workflow.expectPageTitle('Submission');

        // A submission file to choose when sending to review (the step-2 seed
        // carries no files, so the screen's own upload path provides one).
        await workflow.panel('Submission Files').getByRole('button', {name: 'Upload', exact: true}).click();
        await uploadViaWizard(editorPage);

        // Record the decision that sends it to review.
        await workflow.decisionButton('Send for Review').click();
        const decision = new DecisionPage(editorPage);
        await decision.expectOpen('Send for Review');
        await decision.continueStep();
        await decision.promoteFileCheckbox(FIXTURE_PDF_NAME).check();
        await decision.record();

        // The workflow lands on Review Round 1 with the seeded file under review.
        await workflow.expectPageTitle('Review (Round 1)');
        await expect(workflow.roundLink(1)).toBeVisible();
        await workflow.expectStatus('Round 1 Status', 'Waiting for reviewers to be assigned.');
        await expect(workflow.panelRow('Files for Review', FIXTURE_PDF_NAME)).toBeVisible();
    });

    test('S2: the status line follows the reviewers', {tag: '@smoke'}, async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s2', testInfo);
        const {submissionId} = await seedInReview(ojsApi, tag);

        const editorPage = await (await asUser('sectioneditor.ana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        await workflow.expectStatus('Round 1 Status', 'Waiting for reviewers to be assigned.');

        // Add a reviewer through the Reviewers panel.
        await addReviewer(editorPage, 'Julia Reviewer');
        await editorPage.reload();
        await workflow.expectOpen();
        await workflow.expectStatus('Round 1 Status', 'Awaiting responses from reviewers.');

        // The reviewer submits their review.
        const reviewerPage = await (await asUser('reviewer.julia')).newPage();
        await performReview(reviewerPage, JOURNAL, submissionId);

        await editorPage.reload();
        await workflow.expectOpen();
        await workflow.expectStatus('Round 1 Status', 'New reviews have been submitted.');

        // The editor confirms the review from the Reviewers panel.
        await workflow
            .panelRow('Reviewers', 'Julia Reviewer')
            .getByRole('button', {name: 'Read Review', exact: true})
            .click();
        const readModal = editorPage
            .getByRole('dialog')
            .filter({has: editorPage.locator('form#readReviewForm')});
        await expect(readModal).toBeVisible({timeout: 30_000});
        await readModal.getByRole('button', {name: 'Confirm', exact: true}).click();
        await expect(readModal).toHaveCount(0, {timeout: 30_000});
        await waitForJQueryIdle(editorPage);

        await editorPage.reload();
        await workflow.expectOpen();
        await workflow.expectStatus('Round 1 Status', 'All reviews are confirmed and a decision is needed.');
    });

    test('S3: request revisions within the round', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s3', testInfo);
        const {submissionId} = await seedInReview(ojsApi, tag, {
            reviewRounds: [{reviewers: [{username: 'reviewer.paul', status: 'accepted'}]}],
        });

        const editorPage = await (await asUser('sectioneditor.ana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        await workflow.clickRequestRevisions({newRound: false});
        const decision = new DecisionPage(editorPage);
        await decision.expectOpen('Request Revisions');
        await decision.completeAll();
        await workflow.expectStatus('Round 1 Status', 'Revisions have been requested.');

        // The author finds a revisions task and the upload button.
        const authorPage = await (await asUser('author.alex')).newPage();
        await authorPage.goto(`/index.php/${JOURNAL}/dashboard/mySubmissions`);
        const row = await findRowByTag(authorPage, tag);
        await expect(row).toContainText('Revision requested');
        await expect(row.getByRole('button', {name: 'Submit revisions'})).toBeVisible();

        const authorWorkflow = new WorkflowPage(authorPage, JOURNAL);
        await authorWorkflow.gotoAuthor(submissionId);
        await expect(
            authorPage.getByRole('button', {name: 'Upload revisions', exact: true})
        ).toBeVisible();
    });

    test('S4: author uploads a revision', async ({asUser, ojsApi, pkpMail}, testInfo) => {
        test.slow();
        const tag = makeTag('s4', testInfo);
        const editor = `edi${tag}`;
        const author = `au${tag}`;
        // Scratch journal: the revised-version notice must land in a throwaway
        // mailbox (Mailpit is shared), and scratch submissions auto-assign no
        // editor (footnote s) — the editor assigns themselves on screen. The
        // assignment dialog offers no "Journal manager" group; "Journal
        // editor" is the manager-level stage-assignable group.
        await ojsApi.createContext({
            tag,
            users: [
                {username: editor, roles: ['editor']},
                {username: author, roles: ['author']},
            ],
        });
        const {submissionId} = await seedInReview(ojsApi, tag, {
            context: tag,
            submitter: author,
            decisions: ['sendExternalReview', 'requestRevisions'],
        });

        const managerPage = await (await asUser(editor)).newPage();
        const workflow = new WorkflowPage(managerPage, tag);
        await workflow.gotoEditorial(submissionId);
        await workflow.expectStatus('Round 1 Status', 'Revisions have been requested.');
        await assignParticipant(managerPage, {
            group: 'Journal editor',
            name: editor,
            searchName: editor,
        });

        // The author's task stands before the upload (control for its clearing).
        const authorPage = await (await asUser(author)).newPage();
        await authorPage.goto(`/index.php/${tag}/dashboard/mySubmissions`);
        const rowBefore = await findRowByTag(authorPage, tag);
        await expect(rowBefore.getByRole('button', {name: 'Submit revisions'})).toBeVisible();

        // Upload through the review stage's own button.
        const authorWorkflow = new WorkflowPage(authorPage, tag);
        await authorWorkflow.gotoAuthor(submissionId);
        await authorPage.getByRole('button', {name: 'Upload revisions', exact: true}).click();
        await uploadViaWizard(authorPage);
        await expect(authorWorkflow.panelRow('Revisions Uploaded', FIXTURE_PDF_NAME)).toBeVisible();

        // Editor view: status flipped.
        await managerPage.reload();
        await workflow.expectOpen();
        await workflow.expectStatus('Round 1 Status', 'Revisions have been submitted and a decision is needed.');

        // The author's task is gone (same row, bounded by the row's presence).
        await authorPage.goto(`/index.php/${tag}/dashboard/mySubmissions`);
        const rowAfter = await findRowByTag(authorPage, tag);
        await expect(rowAfter.getByRole('button', {name: 'Submit revisions'})).toHaveCount(0);

        // The assigned editor's mailbox holds the revised-version notice, sent
        // under the author's own address.
        const notice = await pkpMail.find({
            to: `${editor}@mail.test`,
            subject: 'Revised Version Uploaded',
        });
        expect(notice.From.Address).toBe(`${author}@mail.test`);
    });

    test('S5: request revisions toward a new round', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s5', testInfo);
        const {submissionId} = await seedInReview(ojsApi, tag);

        const editorPage = await (await asUser('sectioneditor.ana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        await workflow.clickRequestRevisions({newRound: true});
        const decision = new DecisionPage(editorPage);
        // The new-round choice continues under the wizard name "Resubmit for
        // Review" (Rule 11).
        await decision.expectOpen('Resubmit for Review');
        await decision.completeAll();
        await workflow.expectStatus(
            'Round 1 Status',
            'Revisions requested from the author to be taken to a new review round.'
        );

        // The author uploads one revised file.
        const authorPage = await (await asUser('author.alex')).newPage();
        const authorWorkflow = new WorkflowPage(authorPage, JOURNAL);
        await authorWorkflow.gotoAuthor(submissionId);
        await authorPage.getByRole('button', {name: 'Upload revisions', exact: true}).click();
        await uploadViaWizard(authorPage);
        await expect(authorWorkflow.panelRow('Revisions Uploaded', FIXTURE_PDF_NAME)).toBeVisible();

        await editorPage.reload();
        await workflow.expectOpen();
        await workflow.expectStatus(
            'Round 1 Status',
            'Revisions submitted. A new review round needs to be created.'
        );

        // The documented working path after the first upload: the Revisions
        // Uploaded panel's own Upload control still opens the same wizard
        // (the bottom button's fate is register A1, asserted neither way).
        await authorPage.reload();
        await authorWorkflow.expectOpen();
        await authorWorkflow
            .panel('Revisions Uploaded')
            .getByRole('button', {name: 'Upload', exact: true})
            .click();
        await expect(uploadWizardDialog(authorPage)).toBeVisible({timeout: 30_000});
        await uploadWizardDialog(authorPage).getByRole('link', {name: 'Cancel'}).click();
        await expect(uploadWizardDialog(authorPage)).toHaveCount(0, {timeout: 30_000});
    });

    test('S6: a new round', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s6', testInfo);
        const {submissionId} = await seedInReview(ojsApi, tag, {
            decisions: ['sendExternalReview', 'resubmit'],
        });

        const editorPage = await (await asUser('sectioneditor.ana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);

        // File revisions on the author's behalf through the panel's own
        // control (Rule 9's editorial path) so the new-round wizard has a
        // revised file to carry over.
        await workflow
            .panel('Revisions Uploaded')
            .getByRole('button', {name: 'Upload', exact: true})
            .click();
        await uploadViaWizard(editorPage);
        await expect(workflow.panelRow('Revisions Uploaded', FIXTURE_PDF_NAME)).toBeVisible();

        await workflow.decisionButton('Create New Review Round').click();
        const decision = new DecisionPage(editorPage);
        // The wizard page titles itself "New Review Round" (the button label
        // stays "Create New Review Round").
        await decision.expectOpen('New Review Round');
        await decision.continueStep();
        // The wizard's file step offers the revised file already ticked.
        await expect(decision.promoteFileCheckbox(FIXTURE_PDF_NAME)).toBeChecked();
        await decision.record();

        // Round 2 is current, waiting for reviewers, with the carried file.
        await workflow.expectPageTitle('Review (Round 2)');
        await expect(workflow.roundLink(2)).toBeVisible();
        await workflow.expectStatus('Round 2 Status', 'Waiting for reviewers to be assigned.');
        await expect(workflow.panelRow('Files for Review', FIXTURE_PDF_NAME)).toBeVisible();

        // Round 1 is a past round: panels, no decision buttons, the
        // advanced-to-next-round note under a plain "Status" heading.
        await workflow.selectRound(1);
        await expect(workflow.panel('Reviewers')).toBeVisible();
        await workflow.expectStatus('Status', 'The submission has been advanced to the next round of review');
        await expect(workflow.decisionButton('Request Revisions')).toHaveCount(0);
        await expect(workflow.decisionButton('Accept Submission')).toHaveCount(0);
        await expect(workflow.decisionButton('Create New Review Round')).toHaveCount(0);
        await expect(workflow.decisionButton('Cancel Review Round')).toHaveCount(0);
        await expect(workflow.decisionButton('Decline Submission')).toHaveCount(0);
    });

    test('S7: cancel a round', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s7', testInfo);
        const manager = `mgr${tag}`;
        const author = `au${tag}`;
        const reviewer = `rev${tag}`;
        // Scratch journal: the reviewer-side absence claim needs a bounded
        // assignment list, which the shared roster's reviewers cannot give.
        await ojsApi.createContext({
            tag,
            users: [
                {username: manager, roles: ['manager']},
                {username: author, roles: ['author']},
                {username: reviewer, roles: ['externalReviewer']},
            ],
        });
        // Round 1 via the promoting decision, round 2 as an explicitly
        // declared extra round (declaring newExternalReviewRound as a seeded
        // decision double-creates rounds — the decision does not report a new
        // stage id, so the builder also builds every declared round).
        const {submissionId} = await seedInReview(ojsApi, tag, {
            context: tag,
            submitter: author,
            decisions: ['sendExternalReview'],
            reviewRounds: [
                {reviewers: []},
                {reviewers: [{username: reviewer, status: 'invited'}]},
            ],
        });

        // The invited reviewer sees the assignment (control for its vanishing).
        const reviewerPage = await (await asUser(reviewer)).newPage();
        await reviewerPage.goto(`/index.php/${tag}/dashboard/reviewAssignments`);
        const assignmentRow = reviewerPage.getByRole('row').filter({hasText: tag});
        await expect(assignmentRow).toBeVisible({timeout: 30_000});

        const managerPage = await (await asUser(manager)).newPage();
        const workflow = new WorkflowPage(managerPage, tag);
        await workflow.gotoEditorial(submissionId);
        await workflow.expectPageTitle('Review (Round 2)');
        await workflow.decisionButton('Cancel Review Round').click();
        const decision = new DecisionPage(managerPage);
        await decision.expectOpen('Cancel Review Round');
        await decision.completeAll();

        // Round 2 is gone; the submission stands on Round 1.
        await workflow.expectPageTitle('Review (Round 1)');
        await expect(workflow.roundLink(2)).toHaveCount(0);
        await workflow.expectStatus('Round 1 Status', 'Waiting for reviewers to be assigned.');

        // The withdrawn invitation is gone from the reviewer's list (bounded
        // by the same row locator that was visible above).
        await reviewerPage.goto(`/index.php/${tag}/dashboard/reviewAssignments`);
        await expect(reviewerPage.getByRole('table')).toBeVisible({timeout: 30_000});
        await expect(assignmentRow).toHaveCount(0);

        // Cancelling Round 1 returns the submission to the Submission stage.
        await workflow.decisionButton('Cancel Review Round').click();
        await decision.expectOpen('Cancel Review Round');
        await decision.completeAll();
        await workflow.expectPageTitle('Submission');
        await expect(workflow.roundLink(1)).toHaveCount(0);
    });

    test('S8: cancelling is blocked once a review is in', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s8', testInfo);
        const {submissionId} = await seedInReview(ojsApi, tag, {
            reviewRounds: [{reviewers: [{username: 'reviewer.amara', status: 'invited'}]}],
        });

        // While the invitation is unanswered the button is offered (control).
        const editorPage = await (await asUser('sectioneditor.ana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        await expect(workflow.decisionButton('Cancel Review Round')).toBeVisible();

        // The reviewer accepts and completes their review.
        const reviewerPage = await (await asUser('reviewer.amara')).newPage();
        await performReview(reviewerPage, JOURNAL, submissionId);

        // The button is simply absent, while the other decisions remain.
        await editorPage.reload();
        await workflow.expectOpen();
        await expect(workflow.decisionButton('Accept Submission')).toBeVisible();
        await expect(workflow.decisionButton('Create New Review Round')).toBeVisible();
        await expect(workflow.decisionButton('Cancel Review Round')).toHaveCount(0);
    });

    test('S9: accept out of review', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s9', testInfo);
        const {submissionId} = await seedInReview(ojsApi, tag);

        const editorPage = await (await asUser('sectioneditor.ana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        await workflow.decisionButton('Accept Submission').click();
        const decision = new DecisionPage(editorPage);
        await decision.expectOpen('Accept Submission');
        await decision.completeAll();

        // The submission moved to Copyediting; the review rounds remain, the
        // status box reporting the current stage.
        await workflow.expectPageTitle('Copyediting');
        await workflow.selectRound(1);
        await workflow.expectStatus('Status', 'The submission is currently in the Copyediting stage.');
    });

    test('S10: decline, revert, delete', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s10', testInfo);
        const {submissionId} = await seedInReview(ojsApi, tag, {
            reviewRounds: [{reviewers: [{username: 'reviewer.paul', status: 'accepted'}]}],
        });

        const editorPage = await (await asUser('sectioneditor.ana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        await workflow.expectStatus('Round 1 Status', 'Awaiting responses from reviewers.');
        await workflow.decisionButton('Decline Submission').click();
        const decision = new DecisionPage(editorPage);
        await decision.expectOpen('Decline Submission');
        await decision.completeAll();

        // While declined: the Section Editor gets only "Revert Decline".
        await workflow.expectStatus('Round 1 Status', 'Submission declined.');
        await expect(workflow.decisionButton('Revert Decline')).toBeVisible();
        await expect(workflow.decisionButton('Delete')).toHaveCount(0);
        await expect(workflow.decisionButton('Accept Submission')).toHaveCount(0);
        await expect(workflow.decisionButton('Decline Submission')).toHaveCount(0);

        // A Journal Manager additionally sees "Delete".
        const managerPage = await (await asUser('manager.maya')).newPage();
        const managerWorkflow = new WorkflowPage(managerPage, JOURNAL);
        await managerWorkflow.gotoEditorial(submissionId);
        await expect(managerWorkflow.decisionButton('Revert Decline')).toBeVisible();
        await expect(managerWorkflow.decisionButton('Delete')).toBeVisible();

        // Revert Decline restores the round's reviewer-derived status.
        await workflow.decisionButton('Revert Decline').click();
        await decision.expectOpen('Revert Decline');
        await decision.completeAll();
        await workflow.expectStatus('Round 1 Status', 'Awaiting responses from reviewers.');
        await expect(workflow.decisionButton('Accept Submission')).toBeVisible();
    });

    test('S11: recommend-only round', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s11', testInfo);
        // A declined reviewer is reviewer record enough for the recommendation
        // sentences to engage (Rule 6).
        const {submissionId} = await seedInReview(ojsApi, tag, {
            reviewRounds: [{reviewers: [{username: 'reviewer.paul', status: 'declined'}]}],
        });

        // A Journal Manager limits omar's participation to recommendations
        // (stage-participants surface, per the spec's recipe; a fellow section
        // editor's save of this form is silently discarded — see report note).
        const managerPage = await (await asUser('manager.maya')).newPage();
        const managerWorkflow = new WorkflowPage(managerPage, JOURNAL);
        await managerWorkflow.gotoEditorial(submissionId);
        await managerWorkflow.participantMoreActions('Omar Section Editor').click();
        await managerPage.getByRole('menuitem', {name: 'Edit'}).click();
        const editModal = managerPage
            .getByRole('dialog')
            .filter({has: managerPage.locator('input[name="recommendOnly"]')});
        await expect(editModal.locator('input[name="recommendOnly"]')).toBeVisible({timeout: 30_000});
        await editModal.locator('input[name="recommendOnly"]').check();
        await editModal.getByRole('button', {name: 'OK', exact: true}).click();
        // The legacy form's success closes the modal; the wrapper can linger
        // hidden in the DOM, so wait on visibility, not on count.
        await expect(editModal.locator('input[name="recommendOnly"]')).toBeHidden({
            timeout: 30_000,
        });
        await waitForJQueryIdle(managerPage);

        // The recommending editor sees recommendation controls, not decisions.
        const omarPage = await (await asUser('sectioneditor.omar')).newPage();
        const omarWorkflow = new WorkflowPage(omarPage, JOURNAL);
        await omarWorkflow.gotoEditorial(submissionId);
        await expect(omarWorkflow.decisionButton('Recommend Revisions')).toBeVisible();
        await expect(omarWorkflow.decisionButton('Recommend Accept')).toBeVisible();
        await expect(omarWorkflow.decisionButton('Recommend Decline')).toBeVisible();
        await expect(omarWorkflow.decisionButton('Request Revisions')).toHaveCount(0);
        await expect(omarWorkflow.decisionButton('Accept Submission')).toHaveCount(0);
        await omarWorkflow.expectStatus('Round 1 Status', 'Awaiting recommendations from editors.');

        // They record "Accept Submission" as a recommendation.
        await omarWorkflow.decisionButton('Recommend Accept').click();
        const decision = new DecisionPage(omarPage);
        await decision.expectOpen('Recommend Accept');
        await decision.completeAll();

        // The deciding editor's screen shows the Recommendation box and the
        // all-recommendations-in status.
        const editorPage = await (await asUser('sectioneditor.ana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        // The box root is the bordered div whose first cell holds the heading.
        const recommendationBox = editorPage
            .locator('div.border')
            .filter({has: editorPage.getByRole('heading', {name: 'Recommendation', exact: true})});
        await expect(recommendationBox).toContainText('Accept Submission');
        await workflow.expectStatus('Round 1 Status', 'All recommendations are in and a decision is needed.');
    });

    test('S12: author reads an open review', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        test.setTimeout(300_000);
        const tag = makeTag('s12', testInfo);
        const controlTag = `${tag}b`;
        // The open-review submission; the reviewer is added on screen so the
        // review type can be set to Open (the seed's addReviewer path uses the
        // journal default, double-anonymous).
        const {submissionId} = await seedInReview(ojsApi, tag, {submitter: 'author.bea'});
        // The anonymous control on another submission.
        const {submissionId: controlId} = await seedInReview(ojsApi, controlTag, {
            submitter: 'author.bea',
            reviewRounds: [{reviewers: [{username: 'reviewer.paul', status: 'accepted'}]}],
        });

        const editorPage = await (await asUser('sectioneditor.ana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        await addReviewer(editorPage, 'Julia Reviewer', {method: 'Open'});

        // Both reviews complete: the open one and the anonymous control.
        const juliaPage = await (await asUser('reviewer.julia')).newPage();
        await performReview(juliaPage, JOURNAL, submissionId, {recommendation: 'Accept Submission'});
        const paulPage = await (await asUser('reviewer.paul')).newPage();
        await performReview(paulPage, JOURNAL, controlId);

        // A decision letter for the Notifications list (Rule 16): the editor
        // requests revisions, notifying the author by email.
        await editorPage.reload();
        await workflow.expectOpen();
        await workflow.clickRequestRevisions({newRound: false});
        const decision = new DecisionPage(editorPage);
        await decision.expectOpen('Request Revisions');
        await decision.completeAll();

        // The author reads the open review: reviewer's name, completion date
        // and recommendation. (Review text: register OJS1; attachments
        // section: register A3 — neither asserted.)
        const authorPage = await (await asUser('author.bea')).newPage();
        const authorWorkflow = new WorkflowPage(authorPage, JOURNAL);
        await authorWorkflow.gotoAuthor(submissionId);
        const reviewerRow = authorWorkflow.panelRow('Reviewers', 'Julia Reviewer');
        await expect(reviewerRow).toBeVisible();
        await reviewerRow.getByRole('button', {name: 'Read Review', exact: true}).click();
        const readModal = authorPage
            .getByRole('dialog')
            .filter({has: authorPage.locator('form#readReviewForm')});
        await expect(readModal.getByRole('heading', {name: 'Julia Reviewer'})).toBeVisible({
            timeout: 30_000,
        });
        await expect(readModal).toContainText('Completed:');
        await expect(readModal).toContainText('Recommendation:');
        await expect(readModal).toContainText('Accept Submission');
        await readModal.getByRole('button', {name: 'Close'}).click();
        await expect(readModal).toHaveCount(0, {timeout: 30_000});

        // The decision letter sits under "Notifications" and opens read-only.
        // (The subject anchors carry no href, so they expose no link role.)
        await expect(authorPage.getByRole('heading', {name: 'Notifications'})).toBeVisible();
        const letterLink = authorPage
            .locator('div')
            .filter({has: authorPage.getByRole('heading', {name: 'Notifications'})})
            .last()
            .getByRole('listitem')
            .first()
            .locator('a')
            .first();
        const letterSubject = (await letterLink.textContent())?.trim();
        await letterLink.click();
        const letterModal = authorPage.getByRole('dialog').filter({hasText: letterSubject || ''});
        await expect(letterModal.first()).toBeVisible({timeout: 30_000});
        await expect(letterModal.first().getByRole('textbox')).toHaveCount(0);
        await letterModal.first().getByRole('button', {name: 'Close'}).click();

        // The anonymous control: the author's review stage shows no reviewers
        // list at all — not an empty one (positive control: the Revisions
        // Uploaded panel of the same view renders).
        await authorWorkflow.gotoAuthor(controlId);
        await expect(authorPage.getByRole('heading', {name: 'Revisions Uploaded'})).toBeVisible();
        await expect(authorPage.getByRole('table', {name: 'Reviewers', exact: true})).toHaveCount(0);
        await expect(authorPage.getByRole('button', {name: 'Read Review', exact: true})).toHaveCount(0);
    });
});
