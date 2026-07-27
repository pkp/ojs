// @ts-check
/**
 * @file playwright/tests/review-stage-and-rounds.spec.js
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * OJS suite for the feature spec `docs/product/specs/review-stage-and-rounds.md`
 * (U26 — Review stage & rounds): one test per canonical scenario OJS runs, in
 * OJS's own vocabulary (journal / article / Review stage), on the seeded
 * `publicknowledge` journal with its own submissions.
 *
 * ## What this suite deliberately does NOT cover
 *
 * - **Scenario 12 (OMP) and scenario 13 (OPS)** — app-specific scenarios; they
 *   belong to those apps' own suites. OJS has one door into review and no
 *   internal review stage, and OPS does not install the feature at all.
 * - **The register's 🐞 findings are never asserted as contract** (A1 the
 *   author's upload button vanishing after a resubmit upload, A5 the unrendered
 *   round-status notices, A6 the revisions panel's always-offered Upload, A7 the
 *   keyboard-inaccessible Notifications subjects, A8 released and withheld review
 *   files looking alike). The register entry is their record; a test would freeze
 *   the defect as contract. Where a scenario walks through one of those states
 *   (scenario 4's resubmit upload, scenario 11's release checkbox) the test
 *   asserts the rule around it and stays silent on the defect.
 * - **The open ❓ questions** (A2 author-phrased status texts, A3
 *   recommendations outranking review trouble, A4 "Returned back to review.")
 *   are not coverage gaps and get no assertion either way.
 * - **Rules with no canonical scenario**: Rule 9's minimum-confirmed-reviews
 *   prompt (its setting is U29's), Rule 3's "Reviewers Suggested by Author"
 *   panel (U31's), Rule 18's return from Copyediting (U32/U34's decision), and
 *   the rows of Rule 6's status table the canonical scenarios do not walk
 *   (overdue, pending recommendations, sent-for-external-review).
 * - **Neighbouring features' mechanics**: the decision wizard itself (U34), the
 *   Reviewers panel's own actions (U27), the reviewer's review (U28), the author
 *   response block (U30), file-manager mechanics (U36), discussions (U37) and
 *   how a participant reaches the stage at all (U24). This suite drives them only
 *   as far as a round's own behaviour needs.
 */

const fs = require('fs');
const os = require('os');
const path = require('path');
const {test, expect} = require('../support/fixtures.js');
const {WorkflowPage} = require('../../lib/pkp/playwright/pages/WorkflowPage.js');

test.use({user: 'editor.diana'});

const JOURNAL = 'publicknowledge';

/** Per-app, per-worker, per-run tag: one hyphenless alphanumeric token. */
function tagFor(name, testInfo) {
	return `u26ojs${name}w${testInfo.parallelIndex}${Math.random().toString(36).slice(2, 7)}`;
}

/** The seeded roster's addresses follow the username. */
function emailFor(username) {
	return `${username}@example.org`;
}

/** A throwaway file for an upload, named after the test's tag so the row is attributable. */
function revisionFile(tag) {
	const file = path.join(os.tmpdir(), `${tag}.pdf`);

	fs.writeFileSync(
		file,
		'%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n',
	);

	return file;
}

/**
 * A wizard-step check for the decision's file step: it lists exactly the files
 * expected and offers every one of them already ticked. Steps that carry no file
 * list (the email steps) are passed over, so the returned checker counts the file
 * steps it saw — a caller asserts on that count rather than trusting a check that
 * may never have run.
 *
 * @param {number} expected
 */
function offeredFilesKept(expected) {
	const seen = {fileSteps: 0};

	seen.check = async (decisionPage) => {
		const offered = decisionPage.locator('input[name^="promoteFile"]:visible');

		if (!(await offered.count())) {
			return;
		}

		seen.fileSteps++;
		await expect(offered).toHaveCount(expected);

		for (const box of await offered.all()) {
			await expect(box).toBeChecked();
		}
	};

	return seen;
}

/**
 * A submission of the seeded author, filed under Reviews — the section whose
 * editors are editor.diana and sectioneditor.ravi, which leaves
 * sectioneditor.omar free for a recommend-only assignment.
 */
function submissionSpec(tag, extra = {}) {
	return {
		tag,
		context: JOURNAL,
		section: 'REV',
		submitter: 'author.alex',
		...extra,
	};
}

test.describe('Review stage & rounds', () => {
	test('scenario 1 — round 1 opens with the chosen files', async ({page, ojsApi}, testInfo) => {
		const tag = tagFor('s1', testInfo);
		const submission = await ojsApi.createSubmission(submissionSpec(tag));

		const workflow = new WorkflowPage(page, JOURNAL);
		await workflow.gotoEditorial(submission.submissionId);

		// The submission stage's door into review; its file step offers the
		// submission's files pre-ticked, which the editor keeps as offered.
		const offered = offeredFilesKept(1);
		await workflow.recordDecision('Send for Review', {onStep: offered.check});
		expect(offered.fileSteps).toBe(1);

		await expect(workflow.menuItem('Review Round 1')).toBeVisible();

		await workflow.openRound(1);
		await expect(workflow.workflowHeading).toHaveText(/Workflow:\s+Review\s+\(Round 1\)/);
		await expect(workflow.statusHeading('Round 1 Status')).toBeVisible();
		await expect(workflow.statusLines('Round 1 Status')).toHaveText([
			'Waiting for reviewers to be assigned.',
		]);

		await expect(
			workflow.panelTable('Files for Review').getByRole('link', {name: 'article.pdf'}),
		).toBeVisible();
		await expect(workflow.panelTable('Revisions Uploaded')).toContainText('No Items');
	});

	test('scenario 2 — the round status follows reviewer activity', async ({
		page,
		ojsApi,
	}, testInfo) => {
		const tag = tagFor('s2', testInfo);

		// One round per state the scenario walks: the status line is computed
		// live from the round's reviewer activity, so each state is its own round.
		const [awaiting, submitted, confirmed] = await Promise.all(
			['accepted', 'completed', 'confirmed'].map((status, index) =>
				ojsApi.createSubmission(
					submissionSpec(`${tag}r${index}`, {
						decisions: [{decision: 'sendExternalReview'}],
						reviewRounds: [{reviewers: [{user: 'reviewer.julia', status}]}],
					}),
				),
			),
		);

		const workflow = new WorkflowPage(page, JOURNAL);

		for (const [submission, expected] of [
			[awaiting, 'Awaiting responses from reviewers.'],
			[submitted, 'New reviews have been submitted.'],
			[confirmed, 'All reviews are confirmed and a decision is needed.'],
		]) {
			await workflow.gotoEditorial(submission.submissionId);
			await workflow.openRound(1);
			await expect(workflow.statusLines('Round 1 Status')).toHaveText([expected]);
		}
	});

	test('scenario 3 — revisions round-trip inside the round', async ({
		page,
		ojsApi,
		asUser,
		pkpMail,
	}, testInfo) => {
		const tag = tagFor('s3', testInfo);
		const submission = await ojsApi.createSubmission(
			submissionSpec(tag, {
				decisions: [{decision: 'sendExternalReview'}, {decision: 'requestRevisions'}],
			}),
		);

		const editorView = new WorkflowPage(page, JOURNAL);
		await editorView.gotoEditorial(submission.submissionId);
		await editorView.openRound(1);
		await expect(editorView.statusLines('Round 1 Status')).toHaveText([
			'Revisions have been requested.',
		]);

		const authorPage = await (await asUser('author.alex')).newPage();
		const authorView = new WorkflowPage(authorPage, JOURNAL);
		await authorView.gotoAuthor(submission.submissionId);
		await authorView.openRound(1);
		await expect(authorView.statusLines('Round 1 Status')).toHaveText([
			'Revisions have been requested.',
		]);

		await expect(authorView.action('Upload revisions')).toBeVisible();
		await authorView.action('Upload revisions').click();
		await authorView.completeFileWizard(revisionFile(tag));

		await expect(authorView.statusLines('Round 1 Status')).toHaveText([
			'Revisions have been submitted and a decision is needed.',
		]);
		await expect(
			authorView.panelTable('Revisions Uploaded').getByRole('link', {name: `${tag}.pdf`}),
		).toBeVisible();

		// The same round, read by the editor: one status, both views.
		await editorView.gotoEditorial(submission.submissionId);
		await editorView.openRound(1);
		await expect(editorView.statusLines('Round 1 Status')).toHaveText([
			'Revisions have been submitted and a decision is needed.',
		]);
		await expect(
			editorView.panelTable('Revisions Uploaded').getByRole('link', {name: `${tag}.pdf`}),
		).toBeVisible();

		// One "Revised Version Uploaded" email, addressed to the stage's editors
		// together rather than one message each.
		const [message] = await pkpMail.find({
			to: emailFor('editor.diana'),
			contains: tag,
			subject: 'Revised Version Uploaded',
		});
		const recipients = (message.To ?? []).map((entry) => entry.Address);

		expect(recipients).toContain(emailFor('editor.diana'));
		expect(recipients).toContain(emailFor('sectioneditor.ravi'));
	});

	test('scenario 4 — a resubmit request leads to a new review round', async ({
		page,
		ojsApi,
		asUser,
	}, testInfo) => {
		const tag = tagFor('s4', testInfo);
		const submission = await ojsApi.createSubmission(
			submissionSpec(tag, {
				decisions: [{decision: 'sendExternalReview'}, {decision: 'resubmit'}],
			}),
		);

		const editorView = new WorkflowPage(page, JOURNAL);
		await editorView.gotoEditorial(submission.submissionId);
		await editorView.openRound(1);
		await expect(editorView.statusLines('Round 1 Status')).toHaveText([
			'Revisions requested from the author to be taken to a new review round.',
		]);

		const authorPage = await (await asUser('author.alex')).newPage();
		const authorView = new WorkflowPage(authorPage, JOURNAL);
		await authorView.gotoAuthor(submission.submissionId);
		await authorView.openRound(1);
		await authorView.action('Upload revisions').click();
		await authorView.completeFileWizard(revisionFile(tag));

		await expect(authorView.statusLines('Round 1 Status')).toHaveText([
			'Revisions submitted. A new review round needs to be created.',
		]);

		// The new round carries the uploaded revision over as its review file.
		await editorView.gotoEditorial(submission.submissionId);
		await editorView.openRound(1);
		// The wizard offers the uploaded revision, preselected, as the new round's
		// review file.
		const carried = offeredFilesKept(1);
		await editorView.recordDecision('Create New Review Round', {onStep: carried.check});
		expect(carried.fileSteps).toBe(1);

		await expect(editorView.menuItem('Review Round 2')).toBeVisible();
		await expect(editorView.workflowHeading).toHaveText(/Workflow:\s+Review\s+\(Round 2\)/);
		await expect(editorView.statusLines('Round 2 Status')).toHaveText([
			'Waiting for reviewers to be assigned.',
		]);
		await expect(
			editorView.panelTable('Files for Review').getByRole('link', {name: `${tag}.pdf`}),
		).toBeVisible();
	});

	test('scenario 5 — an earlier round is read-only', async ({page, ojsApi}, testInfo) => {
		const tag = tagFor('s5', testInfo);
		const submission = await ojsApi.createSubmission(
			submissionSpec(tag, {
				decisions: [
					{decision: 'sendExternalReview'},
					{decision: 'newExternalReviewRound'},
				],
				reviewRounds: [
					{reviewers: [{user: 'reviewer.julia'}]},
					{reviewers: [{user: 'reviewer.paul'}]},
				],
			}),
		);

		const workflow = new WorkflowPage(page, JOURNAL);
		await workflow.gotoEditorial(submission.submissionId);

		// Positive control: the latest round does offer the decisions.
		await workflow.openRound(2);
		await expect(workflow.action('Request Revisions')).toBeVisible();
		await expect(workflow.action('Accept Submission')).toBeVisible();
		await expect(workflow.action('Create New Review Round')).toBeVisible();

		await workflow.openRound(1);
		await expect(workflow.statusHeading('Status')).toBeVisible();
		await expect(workflow.statusLines('Status')).toHaveText([
			'The submission has been advanced to the next round of review',
		]);
		await expect(workflow.statusHeading('Round 1 Status')).toHaveCount(0);
		await expect(workflow.actionItems.getByRole('button')).toHaveCount(0);

		// The round still shows its own files and reviewers, with their controls.
		await expect(workflow.panelTable('Reviewers')).toContainText('Julia Reviewer');
		await expect(workflow.panelTable('Reviewers')).not.toContainText('Paul Reviewer');
		await expect(
			workflow.activeModal.getByRole('button', {name: 'Upload/Select Files', exact: true}),
		).toBeVisible();
		await expect(
			workflow.activeModal.getByRole('button', {name: 'Add Reviewer', exact: true}),
		).toBeVisible();
	});

	test('scenario 6 — the author reads an open review', async ({
		page,
		ojsApi,
		asUser,
	}, testInfo) => {
		const tag = tagFor('s6', testInfo);
		const shared = `Shared with the author ${tag}`;
		const editorOnly = `For the editor alone ${tag}`;

		const [open, anonymous] = await Promise.all([
			ojsApi.createSubmission(
				submissionSpec(`${tag}o`, {
					decisions: [{decision: 'sendExternalReview'}],
					reviewRounds: [
						{
							reviewers: [
								{
									user: 'reviewer.julia',
									status: 'completed',
									method: 'open',
									commentsForAuthor: shared,
									commentsForEditor: editorOnly,
									recommendation: 'Revisions Required',
									attachment: true,
								},
							],
						},
					],
				}),
			),
			ojsApi.createSubmission(
				submissionSpec(`${tag}a`, {
					decisions: [{decision: 'sendExternalReview'}],
					reviewRounds: [
						{
							reviewers: [
								{
									user: 'reviewer.julia',
									status: 'completed',
									method: 'doubleAnonymous',
									commentsForAuthor: shared,
								},
							],
						},
					],
				}),
			),
		]);

		const authorPage = await (await asUser('author.alex')).newPage();
		const authorView = new WorkflowPage(authorPage, JOURNAL);

		await authorView.gotoAuthor(open.submissionId);
		await authorView.openRound(1);
		await expect(authorView.panel('Reviewers')).toBeVisible();
		await expect(authorView.panelTable('Reviewers')).toContainText('Julia Reviewer');

		await authorView.panelTable('Reviewers').getByRole('button', {name: 'Read Review'}).click();

		const review = authorView.activeModal;
		await expect(review.getByRole('heading', {name: /^Review:/})).toBeVisible({timeout: 30_000});
		await expect(review).toContainText('Julia Reviewer');
		await expect(review).toContainText('Completed:');
		await expect(review).toContainText('Recommendation: Revisions Required');
		await expect(review).toContainText(shared);
		await expect(review).toContainText('reviewer-attachment.pdf');
		await expect(review).not.toContainText(editorOnly);

		// The same review run anonymously never reaches the author's round at all;
		// the round's other panels bound the absence.
		await authorView.gotoAuthor(anonymous.submissionId);
		await authorView.openRound(1);
		await expect(authorView.statusLines('Round 1 Status')).toHaveText([
			'New reviews have been submitted.',
		]);
		await expect(authorView.panel('Revisions Uploaded')).toBeVisible();
		await expect(authorView.panel('Reviewers')).toHaveCount(0);
	});

	test("scenario 7 — the author follows the editor's messages", async ({
		page,
		ojsApi,
		asUser,
	}, testInfo) => {
		const tag = tagFor('s7', testInfo);
		const submission = await ojsApi.createSubmission(
			submissionSpec(tag, {decisions: [{decision: 'sendExternalReview'}]}),
		);

		const authorPage = await (await asUser('author.alex')).newPage();
		const authorView = new WorkflowPage(authorPage, JOURNAL);
		await authorView.gotoAuthor(submission.submissionId);
		await authorView.openRound(1);

		// Before any decision email: no panel. Bounded by the round's own render.
		await expect(authorView.statusHeading('Round 1 Status')).toBeVisible();
		await expect(authorView.panel('Notifications')).toHaveCount(0);

		const editorView = new WorkflowPage(page, JOURNAL);
		await editorView.gotoEditorial(submission.submissionId);
		await editorView.openRound(1);
		await editorView.recordDecision('Request Revisions', {revisions: 'stayInRound'});

		await authorView.gotoAuthor(submission.submissionId);
		await authorView.openRound(1);
		await expect(authorView.panel('Notifications')).toBeVisible();

		// Each message is one row: its subject, and the date it was sent.
		const messages = authorView.panel('Notifications').locator('xpath=following-sibling::ul/li');
		await expect(messages).toHaveCount(1);

		const subject = (await messages.first().locator('a').innerText()).trim();
		expect(subject.length).toBeGreaterThan(0);
		await expect(messages.first()).toContainText(new Date().getFullYear().toString());

		await messages.first().locator('a').click();
		await expect(authorPage.locator('[data-cy="active-modal"]').last()).toContainText(subject, {
			timeout: 30_000,
		});
	});

	test('scenario 8 — recommendations reach the deciding editor', async ({
		ojsApi,
		asUser,
	}, testInfo) => {
		const tag = tagFor('s8', testInfo);
		const submission = await ojsApi.createSubmission(
			submissionSpec(tag, {
				participants: [
					{user: 'sectioneditor.omar', role: 'sectionEditor', recommendOnly: true},
				],
				decisions: [{decision: 'sendExternalReview'}],
			}),
		);

		const recommenderPage = await (await asUser('sectioneditor.omar')).newPage();
		const recommender = new WorkflowPage(recommenderPage, JOURNAL);
		await recommender.gotoEditorial(submission.submissionId);
		await recommender.openRound(1);

		await expect(recommender.action('Recommend Revisions')).toBeVisible();
		await expect(recommender.action('Recommend Accept')).toBeVisible();
		await expect(recommender.action('Recommend Decline')).toBeVisible();
		await expect(recommender.action('Accept Submission')).toHaveCount(0);

		// No side-column listing for the recommender; the side column is there.
		await expect(
			recommender.secondaryItems.getByRole('heading', {name: 'Participants'}),
		).toBeVisible();
		await expect(
			recommender.secondaryItems.getByRole('heading', {name: 'Recommendation'}),
		).toHaveCount(0);

		await recommender.recordDecision('Recommend Revisions', {revisions: 'stayInRound'});
		await recommender.openRound(1);

		await expect(
			recommender.actionItems.getByRole('heading', {name: 'Recommendation'}),
		).toBeVisible();
		await expect(recommender.action('Change decision')).toBeVisible();
		await expect(
			recommender.secondaryItems.getByRole('heading', {name: 'Recommendation'}),
		).toHaveCount(0);

		const managerPage = await (await asUser('manager.maya')).newPage();
		const manager = new WorkflowPage(managerPage, JOURNAL);
		await manager.gotoEditorial(submission.submissionId);
		await manager.openRound(1);

		const listing = manager.secondaryItems.getByRole('heading', {name: 'Recommendation'});
		await expect(listing).toBeVisible();
		await expect(manager.secondaryItems).toContainText('Request Revisions');
		await expect(manager.statusLines('Round 1 Status')).toHaveText([
			'All recommendations are in and a decision is needed.',
		]);
	});

	test('scenario 9 — a fresh round can be cancelled', async ({page, ojsApi}, testInfo) => {
		const tag = tagFor('s9', testInfo);

		const [fresh, answered] = await Promise.all([
			ojsApi.createSubmission(
				submissionSpec(`${tag}f`, {
					decisions: [
						{decision: 'sendExternalReview'},
						{decision: 'newExternalReviewRound'},
					],
					reviewRounds: [
						{reviewers: [{user: 'reviewer.julia'}]},
						{reviewers: [{user: 'reviewer.amara', status: 'invited'}]},
					],
				}),
			),
			ojsApi.createSubmission(
				submissionSpec(`${tag}a`, {
					decisions: [{decision: 'sendExternalReview'}],
					reviewRounds: [{reviewers: [{user: 'reviewer.paul', status: 'accepted'}]}],
				}),
			),
		]);

		const workflow = new WorkflowPage(page, JOURNAL);
		await workflow.gotoEditorial(fresh.submissionId);
		await workflow.openRound(2);
		await expect(workflow.action('Cancel Review Round')).toBeVisible();

		await workflow.recordDecision('Cancel Review Round');

		await expect(workflow.menuItem('Review Round 2')).toHaveCount(0);
		await expect(workflow.menuItem('Review Round 1')).toBeVisible();
		await expect(workflow.workflowHeading).toHaveText(/Workflow:\s+Review\s+\(Round 1\)/);
		await expect(workflow.statusHeading('Round 1 Status')).toBeVisible();

		// Once the invitation has been answered the decision is no longer offered;
		// the round's other decisions bound the absence.
		await workflow.gotoEditorial(answered.submissionId);
		await workflow.openRound(1);
		await expect(workflow.action('Accept Submission')).toBeVisible();
		await expect(workflow.action('Cancel Review Round')).toHaveCount(0);
	});

	test('scenario 10 — decline and revert in review', async ({ojsApi, asUser}, testInfo) => {
		const tag = tagFor('s10', testInfo);
		const submission = await ojsApi.createSubmission(
			submissionSpec(tag, {decisions: [{decision: 'sendExternalReview'}]}),
		);

		const sectionEditorPage = await (await asUser('sectioneditor.ravi')).newPage();
		const sectionEditor = new WorkflowPage(sectionEditorPage, JOURNAL);
		await sectionEditor.gotoEditorial(submission.submissionId);
		await sectionEditor.openRound(1);
		await sectionEditor.recordDecision('Decline Submission');
		await sectionEditor.openRound(1);

		await expect(sectionEditor.statusLines('Round 1 Status')).toHaveText([
			'Submission declined.',
		]);
		await expect(sectionEditor.action('Revert Decline')).toBeVisible();
		await expect(sectionEditor.action('Accept Submission')).toHaveCount(0);
		await expect(sectionEditor.action('Request Revisions')).toHaveCount(0);
		// A Section Editor is never offered Delete; Revert Decline is the control.
		await expect(sectionEditor.action('Delete')).toHaveCount(0);

		const managerPage = await (await asUser('manager.maya')).newPage();
		const manager = new WorkflowPage(managerPage, JOURNAL);
		await manager.gotoEditorial(submission.submissionId);
		await manager.openRound(1);
		await expect(manager.action('Revert Decline')).toBeVisible();
		await expect(manager.action('Delete')).toBeVisible();

		await sectionEditor.recordDecision('Revert Decline');
		await sectionEditor.openRound(1);
		await expect(sectionEditor.statusLines('Round 1 Status')).toHaveText([
			'Waiting for reviewers to be assigned.',
		]);
		await expect(sectionEditor.action('Accept Submission')).toBeVisible();
	});

	test('scenario 11 — curating Files for Review mid-round', async ({
		page,
		ojsApi,
	}, testInfo) => {
		const tag = tagFor('s11', testInfo);
		const submission = await ojsApi.createSubmission(submissionSpec(tag));

		const workflow = new WorkflowPage(page, JOURNAL);
		await workflow.gotoEditorial(submission.submissionId);
		await workflow.recordDecision('Send for Review');
		await workflow.openRound(1);

		const openSelector = async () => {
			await workflow.activeModal
				.getByRole('button', {name: 'Upload/Select Files', exact: true})
				.click();
			const selector = workflow.activeModal;
			await expect(
				selector.getByRole('heading', {name: 'Current Review Files For Round 1'}),
			).toBeVisible({timeout: 30_000});

			return selector;
		};
		const fileBoxes = (selector) => selector.locator('input[name="selectedFiles[]"]');
		const fileIds = (selector) =>
			fileBoxes(selector).evaluateAll((boxes) => boxes.map((box) => box.id));
		const save = async (selector) => {
			await selector.getByRole('button', {name: 'OK', exact: true}).click();
			await expect(
				workflow.page.getByRole('heading', {name: 'Current Review Files For Round 1'}),
			).toHaveCount(0, {timeout: 30_000});
			await workflow.waitForOpen();
		};

		// By default the modal lists the round's own review files only.
		let selector = await openSelector();
		await expect(fileBoxes(selector)).toHaveCount(1);
		const roundFiles = await fileIds(selector);

		// The toggle reaches the submission's other files; ticking one copies it in.
		await selector.locator('input[name="allStages"]').check();
		await expect(fileBoxes(selector)).toHaveCount(2, {timeout: 30_000});

		const [otherStageFile] = (await fileIds(selector)).filter(
			(id) => !roundFiles.includes(id),
		);
		await selector.locator(`#${otherStageFile}`).check();
		await save(selector);

		await expect(workflow.panelTable('Files for Review').locator('tbody tr')).toHaveCount(2);

		// The panel now lists a COPY of it — a review file of its own, which
		// arrives not yet released to reviewers.
		selector = await openSelector();
		await expect(fileBoxes(selector)).toHaveCount(2);

		const [copy] = (await fileIds(selector)).filter((id) => !roundFiles.includes(id));
		expect(copy).not.toBe(otherStageFile);
		await expect(selector.locator(`#${copy}`)).not.toBeChecked();

		// Ticking it releases it to the reviewers.
		await selector.locator(`#${copy}`).check();
		await save(selector);

		selector = await openSelector();
		await expect(selector.locator(`#${copy}`)).toBeChecked();

		// Unticking withdraws it from the reviewers without deleting anything:
		// the panel and the modal both still list the file.
		await selector.locator(`#${copy}`).uncheck();
		await save(selector);

		await expect(workflow.panelTable('Files for Review').locator('tbody tr')).toHaveCount(2);
		selector = await openSelector();
		await expect(fileBoxes(selector)).toHaveCount(2);
		await expect(selector.locator(`#${copy}`)).not.toBeChecked();
	});
});
