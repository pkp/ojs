// @ts-check
const {test, expect} = require('../../support/fixtures.js');
const {DiscussionManagerPage} = require('../../../lib/pkp/playwright/pages/DiscussionManagerPage.js');
const {EditorialWorkflowPage} = require('../../pages/EditorialWorkflowPage.js');
const submissionDraft = require('../../fixtures/scenarios/submission-draft.js');

/**
 * Playwright port of cypress/tests/integration/Discussions.cy.js.
 *
 * Structural differences from the Cypress source:
 *   - Each test creates its own submission via pkpApi.createSubmission()
 *     (Cypress depended on a pre-existing "Corino" submission).
 *   - Multi-actor flow uses `asUser` contexts instead of login/logout.
 *   - Locators are role-based (or stable form-field ids), not DOM paths.
 *   - TinyMCE is set via setContent() on the editor API rather than
 *     typing into the iframe body.
 */
test.describe('Discussion Manager', () => {
	test('full CRUD on discussions and tasks', {tag: '@regression'}, async ({pkpApi, asUser}) => {
		const tag = uniqueTag(test.info(), 'crud');
		const spec = submissionDraft({tag});
		const {submission} = await pkpApi.createSubmission(spec);

		const ctx = await asUser('dbarnes');
		const page = await ctx.newPage();
		const workflow = new EditorialWorkflowPage(page);
		await workflow.goto(submission.id);

		const dm = new DiscussionManagerPage(page);
		await dm.expectVisible();
		await dm.expectGroupsVisible();

		// --- Create discussion with participants + message ------------------
		const discussionTitle = `Discussion ${tag}`;
		const editedTitle = `Edited ${tag}`;
		const message = `This is a test discussion message ${tag}`;
		const reply = `This is a reply message ${tag}`;

		let form = await dm.openAdd();
		await form.fillTitle(discussionTitle);
		await form.checkParticipant('David Buskins');
		await form.checkParticipant('Minoti Inoue');
		await form.fillDescription(message);
		await form.save();

		await dm.expectInGroup(discussionTitle, 'In progress');

		// --- Open, reply, close ---------------------------------------------
		let display = await dm.openByTitle(discussionTitle);
		await display.expectContains(message);
		await display.checkCloseThisDiscussion();
		await display.clickAddNewMessage();
		await display.fillReply(reply);
		await display.save();

		await display.expectContains(reply);
		await display.expectClosedLabel();
		await display.close();

		await dm.expectInGroup(discussionTitle, 'Closed');

		// --- Reopen via row checkbox ----------------------------------------
		await dm.toggleRowCheckbox(discussionTitle);
		await dm.confirmReopen();
		await dm.expectInGroup(discussionTitle, 'In progress');

		// --- Edit title via row actions -------------------------------------
		form = /** @type {any} */ (await dm.openActions(discussionTitle, 'Edit'));
		await form.fillTitle(editedTitle);
		await form.save();
		await expect(dm.row(editedTitle)).toBeVisible();

		// --- Delete via row actions -----------------------------------------
		await dm.openActions(editedTitle, 'Delete');
		await dm.confirmDelete();
		await expect(dm.row(editedTitle)).toHaveCount(0);

		// --- Create task with Do-Not-Start + unsaved-changes warning ---------
		const taskTitle = `Task ${tag}`;
		const taskMessage = `Task message content ${tag}`;
		const futureDate = futureDateYmd();

		form = await dm.openAdd();
		await form.fillTitle('Unsaved test');
		await form.cancel();
		// Unsaved-changes dialog opens on top of the form modal. Click "No"
		// to stay in the form (dialog button scoped to the topmost dialog).
		await page
			.getByRole('dialog')
			.last()
			.getByRole('button', {name: 'No', exact: true})
			.click();

		// Continue with task creation in the same form.
		await form.fillTitle(taskTitle);
		await form.checkParticipant('David Buskins');
		await form.enableTaskInfo();
		await form.setDateDue(futureDate);
		await form.setResponsibleAssignee('David Buskins');
		await form.setShouldStart('false');
		await form.fillDescription(taskMessage);
		await form.save();

		await dm.expectInGroup(taskTitle, 'Yet to begin');

		// --- Open task, start, complete in one session ----------------------
		display = await dm.openByTitle(taskTitle);
		await display.expectContains(taskMessage);
		await display.clickStartTask();
		await display.save();
		await display.expectTaskStarted();

		await display.clickCompleteTask();
		await display.save();
		await display.expectClosedLabel();
		// Edit control is visible but disabled after completion.
		await display.expectEditDisabled();
		await display.close();

		await dm.expectInGroup(taskTitle, 'Closed');
	});

	test('edit/delete access is restricted by role', {tag: '@regression'}, async ({pkpApi, asUser}) => {
		const tag = uniqueTag(test.info(), 'access');
		const spec = submissionDraft({tag});
		const {submission} = await pkpApi.createSubmission(spec);

		const taskTitle = `Access ${tag}`;
		const futureDate = futureDateYmd();

		// dbarnes creates the task with dbuskins (responsible) + minoue (not responsible)
		{
			const ctx = await asUser('dbarnes');
			const page = await ctx.newPage();
			const workflow = new EditorialWorkflowPage(page);
			await workflow.goto(submission.id);

			const dm = new DiscussionManagerPage(page);
			await dm.expectVisible();

			const form = await dm.openAdd();
			await form.fillTitle(taskTitle);
			await form.checkParticipant('David Buskins');
			await form.checkParticipant('Minoti Inoue');
			await form.enableTaskInfo();
			await form.setDateDue(futureDate);
			await form.setResponsibleAssignee('David Buskins');
			await form.setShouldStart('true');
			await form.fillDescription('Access control test message');
			await form.save();

			await expect(dm.row(taskTitle)).toBeVisible();
		}

		// minoue — participant but NOT responsible — read-only view
		{
			const ctx = await asUser('minoue');
			const page = await ctx.newPage();
			const workflow = new EditorialWorkflowPage(page);
			await workflow.goto(submission.id);

			const dm = new DiscussionManagerPage(page);
			await dm.expectVisible();

			await dm.expectActionsMenuHidden(taskTitle);
			await dm.expectRowCheckboxDisabled(taskTitle);

			const display = await dm.openByTitle(taskTitle);
			await display.expectEditHidden();
			await display.close();
		}

		// dbuskins — responsible — has full access; delete as cleanup
		{
			const ctx = await asUser('dbuskins');
			const page = await ctx.newPage();
			const workflow = new EditorialWorkflowPage(page);
			await workflow.goto(submission.id);

			const dm = new DiscussionManagerPage(page);
			await dm.expectVisible();

			await dm.expectActionsMenuVisible(taskTitle);
			await dm.openActions(taskTitle, 'Delete');
			await dm.confirmDelete();
			await expect(dm.row(taskTitle)).toHaveCount(0);
		}
	});
});

/**
 * Build a tag scoped to this worker + test title so parallel workers
 * don't collide on the shared submissions list / discussion list.
 *
 * @param {import('@playwright/test').TestInfo} info
 * @param {string} suffix
 */
function uniqueTag(info, suffix) {
	const slug = info.title
		.toLowerCase()
		.replace(/[^a-z0-9]+/g, '-')
		.slice(0, 16);
	return `t-w${info.parallelIndex}-${suffix}-${slug}`;
}

/** Tomorrow + 1 year, YYYY-MM-DD. */
function futureDateYmd() {
	const d = new Date();
	d.setFullYear(d.getFullYear() + 1);
	return d.toISOString().split('T')[0];
}
