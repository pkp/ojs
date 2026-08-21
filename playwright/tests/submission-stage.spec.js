// @ts-check
/**
 * @file playwright/tests/submission-stage.spec.js
 *
 * Submission stage — OJS suite, one test per canonical scenario the spec runs
 * on OJS (common scenarios 1–7; scenario 8 is OMP-only, 9 OPS-only).
 * Spec: lib/pkp/docs/e2e/specs/U25-submission-stage.md
 *
 * Deliberately NOT covered (register IDs from the spec's Findings register):
 * - A1 ❓: whether "Schedule For Publication" belongs on a declined
 *   submission is open — S4/S5/S6 assert nothing about the shortcut in the
 *   declined state (S1 asserts its queued-state presence only, per Rule 7).
 * - A2 ❓: what a recommend-only editor is offered on this stage is open —
 *   no recommend-only assignment is probed here.
 * - A3 ❓: what a deleted submission's stale workflow address shows is open —
 *   S6 never revisits the old address after the delete.
 * - Rule 11 (legacy author-dashboard address forwards) is not a canonical
 *   scenario of this spec.
 * - Rule 1's conditional "Reviewers Suggested by Author" panel: the step-2
 *   scenario schema has no reviewer-suggestion or settings key to seed the
 *   setting-on states; the panel belongs to the Reviewer suggestions feature
 *   and returns with that suite.
 * - The server-side delete refusal for non-Manager roles (Actors table): the
 *   screens never send that request — the assertable surface is the button's
 *   absence (S4, S6); the API-level refusal stays code-derived.
 * - "On Delete … nothing is emailed" (Side effects): a mail-silence claim
 *   with no natural in-test positive control on the shared roster; not
 *   scenario'd, not asserted.
 * - Each decision wizard's own email/file-promotion behavior belongs to
 *   *Editorial decision recording*; the wizards are driven here only to
 *   record the decisions.
 *
 * Seeding: scenario endpoints only; publicknowledge and the seeded roster are
 * read-only. Every test isolates on its own scratch submission (S6 deletes
 * only its own seed). The step-2 seed carries no files, so S1 provides "the
 * author's uploaded file" through the panel's own upload wizard. No mail
 * assertions (decision mail is Editorial decision recording's), so no Mailpit
 * use. Waits are event-based — no hard-coded sleeps. Everything runs in the
 * parallel `ojs` project.
 */
const {test, expect} = require('../support/fixtures.js');
const {
    WorkflowPage,
    DecisionPage,
    uploadViaWizard,
    FIXTURE_PDF_NAME,
} = require('../pages/ReviewStagePages.js');

const JOURNAL = 'publicknowledge';

/** Unique per-run tag: single alphanumeric token, app + scenario + worker. */
function makeTag(scenario, testInfo) {
    return `u25${scenario}ojsw${testInfo.parallelIndex}${Math.random().toString(36).slice(2, 8)}`;
}

/** Seed a fresh queued submission (stage 1, no decisions). */
async function seedQueued(ojsApi, tag, {submitter = 'author.alex', decisions = []} = {}) {
    return await ojsApi.createSubmission({
        tag,
        context: JOURNAL,
        submitter,
        title: `Submission ${tag}`,
        decisions,
    });
}

/** The workflow menu's Submission-stage entry. */
function submissionMenuLink(page) {
    return page.getByRole('link', {name: 'Submission', exact: true});
}

/** The three onward decision buttons of Rules 2–4. */
const ONWARD_DECISIONS = ['Send for Review', 'Accept and Skip Review', 'Decline Submission'];

/**
 * The submissions-table search box (the manager's editorial dashboard also
 * carries a second, views-list searchbox — scope by accessible name).
 */
function tableSearch(page) {
    return page.getByRole('searchbox', {name: /Search submissions, ID/});
}

/**
 * Find the submission's row on a dashboard list by its unique tag. The search
 * commits on Enter (Search.vue submits on keydown.enter) and flips the
 * dashboard to its search view.
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

test.describe('submission stage', () => {
    test('S1: a new submission opens at the Submission stage', {tag: '@smoke'}, async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s1', testInfo);
        const {submissionId} = await seedQueued(ojsApi, tag);

        const editorPage = await (await asUser('editor.diana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        await workflow.expectPageTitle('Submission');

        // The Submission Files panel lists the submission's file (the step-2
        // seed carries no files, so the panel's own upload wizard provides
        // the file whose listing Rule 1 describes).
        await workflow.panel('Submission Files').getByRole('button', {name: 'Upload', exact: true}).click();
        await uploadViaWizard(editorPage);
        await expect(workflow.panelRow('Submission Files', FIXTURE_PDF_NAME)).toBeVisible();

        // The other two panels of Rule 1.
        await expect(
            editorPage.getByRole('heading', {name: 'Desk Review Tasks & Discussions'})
        ).toBeVisible();
        await expect(editorPage.getByRole('heading', {name: /^participants$/i})).toBeVisible();

        // The decision buttons at the top (Rules 2–4), plus the journal's
        // Schedule For Publication shortcut (Rule 7).
        for (const label of ONWARD_DECISIONS) {
            await expect(workflow.decisionButton(label)).toBeVisible();
        }
        await expect(workflow.decisionButton('Schedule For Publication')).toBeVisible();

        // Rule 8: the status box is quiet while the submission is active at
        // this stage (bounded by the panels above having rendered).
        await expect(editorPage.getByRole('heading', {name: 'Status', exact: true})).toHaveCount(0);
    });

    test('S2: send the submission to review', {tag: '@smoke'}, async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s2', testInfo);
        const {submissionId} = await seedQueued(ojsApi, tag);

        const editorPage = await (await asUser('editor.diana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        await workflow.decisionButton('Send for Review').click();
        const decision = new DecisionPage(editorPage);
        await decision.expectOpen('Send for Review');
        await decision.completeAll();

        // The workflow moved to the Review stage with Round 1 open.
        await workflow.expectPageTitle('Review (Round 1)');
        await expect(workflow.roundLink(1)).toBeVisible();

        // Reopening the Submission-stage entry shows its panels but none of
        // the decision buttons (Rule 9).
        await submissionMenuLink(editorPage).click();
        await workflow.expectPageTitle('Submission');
        await expect(editorPage.getByRole('heading', {name: 'Submission Files'})).toBeVisible();
        await expect(
            editorPage.getByRole('heading', {name: 'Desk Review Tasks & Discussions'})
        ).toBeVisible();
        for (const label of ONWARD_DECISIONS) {
            await expect(workflow.decisionButton(label)).toHaveCount(0);
        }
    });

    test('S3: accept and skip review', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s3', testInfo);
        const {submissionId} = await seedQueued(ojsApi, tag);

        const editorPage = await (await asUser('editor.diana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        await workflow.decisionButton('Accept and Skip Review').click();
        const decision = new DecisionPage(editorPage);
        await decision.expectOpen('Accept and Skip Review');
        await decision.completeAll();

        // Straight to the Copyediting stage, no review round created.
        await workflow.expectPageTitle('Copyediting');
        await expect(workflow.roundLink(1)).toHaveCount(0);

        // The Submission-stage entry: panels, no decision buttons (Rule 9).
        await submissionMenuLink(editorPage).click();
        await workflow.expectPageTitle('Submission');
        await expect(editorPage.getByRole('heading', {name: 'Submission Files'})).toBeVisible();
        for (const label of ONWARD_DECISIONS) {
            await expect(workflow.decisionButton(label)).toHaveCount(0);
        }
    });

    test('S4: decline a submission', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s4', testInfo);
        const {submissionId} = await seedQueued(ojsApi, tag);

        const editorPage = await (await asUser('editor.diana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);
        await workflow.decisionButton('Decline Submission').click();
        const decision = new DecisionPage(editorPage);
        await decision.expectOpen('Decline Submission');
        await decision.completeAll();

        // Back on the Submission stage: the stage label under the title reads
        // "Declined" (Rule 4; scoped to the workflow dialog — the dashboard
        // behind it has a "Declined" view of its own).
        await workflow.expectPageTitle('Submission');
        await expect(
            editorPage.locator('[data-cy="active-modal"]').getByText('Declined', {exact: true})
        ).toBeVisible();

        // The onward buttons are replaced by "Revert Decline" (Rule 5).
        await expect(workflow.decisionButton('Revert Decline')).toBeVisible();
        for (const label of ONWARD_DECISIONS) {
            await expect(workflow.decisionButton(label)).toHaveCount(0);
        }

        // A Journal Manager additionally sees "Delete" …
        const managerPage = await (await asUser('manager.maya')).newPage();
        const managerWorkflow = new WorkflowPage(managerPage, JOURNAL);
        await managerWorkflow.gotoEditorial(submissionId);
        await expect(managerWorkflow.decisionButton('Revert Decline')).toBeVisible();
        await expect(managerWorkflow.decisionButton('Delete')).toBeVisible();

        // … while a Section Editor does not.
        const sePage = await (await asUser('sectioneditor.ana')).newPage();
        const seWorkflow = new WorkflowPage(sePage, JOURNAL);
        await seWorkflow.gotoEditorial(submissionId);
        await expect(seWorkflow.decisionButton('Revert Decline')).toBeVisible();
        await expect(seWorkflow.decisionButton('Delete')).toHaveCount(0);
    });

    test('S5: revert a decline', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        const tag = makeTag('s5', testInfo);
        // The declined state seeded through the real InitialDecline decision.
        const {submissionId} = await seedQueued(ojsApi, tag, {decisions: ['initialDecline']});

        const editorPage = await (await asUser('editor.diana')).newPage();
        const workflow = new WorkflowPage(editorPage, JOURNAL);
        await workflow.gotoEditorial(submissionId);

        // Declined state as the starting point (control for the restore).
        await expect(workflow.decisionButton('Revert Decline')).toBeVisible();
        for (const label of ONWARD_DECISIONS) {
            await expect(workflow.decisionButton(label)).toHaveCount(0);
        }

        await workflow.decisionButton('Revert Decline').click();
        const decision = new DecisionPage(editorPage);
        await decision.expectOpen('Revert Decline');
        await decision.completeAll();

        // The submission is queued again: the onward buttons are back and
        // "Revert Decline" is gone.
        await workflow.expectPageTitle('Submission');
        for (const label of ONWARD_DECISIONS) {
            await expect(workflow.decisionButton(label)).toBeVisible();
        }
        await expect(workflow.decisionButton('Revert Decline')).toHaveCount(0);
    });

    test('S6: delete a declined submission', async ({asUser, ojsApi}, testInfo) => {
        test.slow();
        // Two sibling tags (neither a substring of the other's rows): the
        // scratch seed to delete, plus a second declined seed that stays —
        // the positive control for the Declined-view search after the delete.
        const tag = `${makeTag('s6', testInfo)}d`;
        const keptTag = `${tag.slice(0, -1)}k`;
        const {submissionId} = await seedQueued(ojsApi, tag, {decisions: ['initialDecline']});
        await seedQueued(ojsApi, keptTag, {decisions: ['initialDecline']});

        // Control first: on the same declined submission a Section Editor
        // sees no "Delete" (bounded by Revert Decline rendering).
        const sePage = await (await asUser('sectioneditor.ana')).newPage();
        const seWorkflow = new WorkflowPage(sePage, JOURNAL);
        await seWorkflow.gotoEditorial(submissionId);
        await expect(seWorkflow.decisionButton('Revert Decline')).toBeVisible();
        await expect(seWorkflow.decisionButton('Delete')).toHaveCount(0);

        // The declined submission is listed on the dashboard's Declined view
        // (positive control for its later absence).
        const managerPage = await (await asUser('manager.maya')).newPage();
        await managerPage.goto(`/index.php/${JOURNAL}/dashboard/editorial?currentViewId=declined`);
        await findRowByTag(managerPage, tag);

        // The Journal Manager deletes it through the confirm dialog (Rule 6).
        const managerWorkflow = new WorkflowPage(managerPage, JOURNAL);
        await managerWorkflow.gotoEditorial(submissionId);
        await managerWorkflow.decisionButton('Delete').click();
        const confirm = managerPage
            .getByRole('dialog')
            .filter({hasText: 'Are you sure you want to permanently delete this submission?'});
        await expect(confirm).toBeVisible({timeout: 30_000});
        await confirm.getByRole('button', {name: 'Confirm', exact: true}).click();

        // The workflow closes with the delete.
        await expect(managerPage.getByRole('heading', {name: /^Workflow:/})).toHaveCount(0, {
            timeout: 30_000,
        });

        // Back on the Declined view: the kept seed is still found the same
        // way (positive control) …
        await managerPage.goto(`/index.php/${JOURNAL}/dashboard/editorial?currentViewId=declined`);
        await findRowByTag(managerPage, keptTag);

        // … while the deleted one no longer appears, searched the same way.
        // A committed search flips the dashboard to its search view and
        // removes the in-page search control, so re-open the Declined view
        // first; the absence is bounded by the search's own response.
        await managerPage.goto(`/index.php/${JOURNAL}/dashboard/editorial?currentViewId=declined`);
        const filtered = managerPage.waitForResponse(
            (r) => r.url().includes('_submissions') && r.url().includes(tag)
        );
        const search = tableSearch(managerPage);
        await expect(search).toBeVisible({timeout: 30_000});
        await search.click();
        await search.pressSequentially(tag, {delay: 25});
        await search.press('Enter');
        await filtered;
        await expect(managerPage.getByRole('row').filter({hasText: tag})).toHaveCount(0);
    });

    test('S7: the author\'s view offers no decisions', {tag: '@smoke'}, async ({asUser, ojsApi}, testInfo) => {
        const tag = makeTag('s7', testInfo);
        const {submissionId} = await seedQueued(ojsApi, tag, {submitter: 'author.alex'});

        const authorPage = await (await asUser('author.alex')).newPage();
        const workflow = new WorkflowPage(authorPage, JOURNAL);
        await workflow.gotoAuthor(submissionId);

        // The two panels of the author view (Rule 10).
        await expect(authorPage.getByRole('heading', {name: 'Submission Files'})).toBeVisible();
        await expect(
            authorPage.getByRole('heading', {name: 'Desk Review Tasks & Discussions'})
        ).toBeVisible();

        // And nothing else: no Participants panel, no decision buttons, no
        // Delete, no Schedule For Publication (bounded by the panels above).
        await expect(authorPage.getByRole('heading', {name: /^participants$/i})).toHaveCount(0);
        for (const label of ONWARD_DECISIONS) {
            await expect(workflow.decisionButton(label)).toHaveCount(0);
        }
        await expect(workflow.decisionButton('Delete')).toHaveCount(0);
        await expect(workflow.decisionButton('Schedule For Publication')).toHaveCount(0);
    });
});
