/**
 * @file cypress/tests/integration/Discussions.cy.js
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2000-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Optimized test structure: 3 batched test flows with single login per batch.
 * Batch 1: Discussions - display, templates, create, view, message, edit, delete
 * Batch 2: Tasks - create with different start options, status transitions, edit restrictions
 * Batch 3: Access Control - edit/delete permissions based on role
 * Uses minimal data-cy attributes (only 3: discussion-manager, discussion-form-modal, discussion-display-modal)
 */

describe('Tasks & Discussions Manager', function() {
	const author = 'Corino';

	/**
	 * Helper to get a future date (1 year from now) in YYYY-MM-DD format
	 */
	function getFutureDate() {
		const date = new Date();
		date.setFullYear(date.getFullYear() + 1);
		return date.toISOString().split('T')[0];
	}

	/**
	 * Helper to verify actions menu is visible for an item
	 */
	function verifyActionsMenuVisible(itemTitle) {
		cy.get('[data-cy="discussion-manager"]')
			.contains(itemTitle)
			.parents('tr')
			.find('button[aria-label="More Actions"]')
			.should('exist');
	}

	/**
	 * Helper to verify actions menu is NOT visible for an item
	 */
	function verifyActionsMenuNotVisible(itemTitle) {
		cy.get('[data-cy="discussion-manager"]')
			.contains(itemTitle)
			.parents('tr')
			.find('button[aria-label="More Actions"]')
			.should('not.exist');
	}

	/**
	 * Helper to verify table checkboxes are disabled for an item
	 */
	function verifyTableCheckboxesDisabled(itemTitle) {
		cy.get('[data-cy="discussion-manager"]')
			.contains(itemTitle)
			.parents('tr')
			.find('input[type="checkbox"]')
			.should('be.disabled');
	}

	/**
	 * Helper to open the Add modal
	 */
	function openAddModal() {
		cy.get('[data-cy="discussion-manager"]').contains('button', 'Add').click();
		cy.get('[data-cy="discussion-form-modal"]').should('be.visible');
	}

	/**
	 * Helper to open actions dropdown for a specific item and click an action
	 */
	function openActionsAndClick(itemTitle, actionName) {
		cy.get('[data-cy="discussion-manager"]').contains(itemTitle)
			.parents('tr')
			.find('button[aria-label="More Actions"]')
			.click({force: true});

		cy.contains('button', actionName).click({force: true});
	}

	/**
	 * Helper to verify an item appears under a specific status group
	 */
	function verifyItemInGroup(itemTitle, groupName) {
		cy.get('[data-cy="discussion-manager"] tbody')
			.contains(itemTitle)
			.closest('tr')
			.prevAll(':has(th[scope="rowgroup"])')
			.first()
			.should('contain', groupName);
	}

	/**
	 * Batch 1: Discussions
	 * Tests: display, templates, unsaved changes, create, view, message, edit, delete
	 */
	describe('Discussions', function() {
		const discussionTitle = 'Test Discussion ' + Date.now();
		const discussionMessage = 'This is a test discussion message';
		const editedTitle = 'Edited Discussion ' + Date.now();
		const replyMessage = 'This is a reply message ' + Date.now();

		before(function() {
			cy.findSubmissionAsEditor('dbarnes', null, author);
			cy.get('[data-cy="discussion-manager"]').should('exist');
		});

		it('displays list, creates discussion, adds messages, edits, and deletes', function() {
			// 1. Verify status groups are displayed
			cy.contains('Yet to begin').should('exist');
			cy.contains('In progress').should('exist');
			cy.contains('Closed').should('exist');

			// 2. Test template selection (if templates exist)
			openAddModal();

			cy.get('[data-cy="discussion-form-modal"]').then($modal => {
				const templateButtons = $modal.find('ul[role="list"] button');
				if (templateButtons.length > 0) {
					cy.get('[data-cy="discussion-form-modal"]')
						.find('ul[role="list"] button')
						.first()
						.click();
					cy.wait(500);
					cy.get('[data-cy="discussion-form-modal"]')
						.find('input[name="title"]')
						.invoke('val')
						.then(val => {
							expect(val).to.be.a('string');
						});
				}
			});

			// 3. Test unsaved changes warning
			cy.get('[data-cy="discussion-form-modal"]').within(() => {
				cy.get('input[name="title"]').clear().type('Unsaved test');
			});
			cy.get('[data-cy="discussion-form-modal"]')
				.contains('button', 'Cancel')
				.click({force: true});
			cy.contains('button', 'Yes').click();
			cy.get('[data-cy="discussion-form-modal"]').should('not.exist');

			// 4. Create a discussion with participants and message
			openAddModal();

			cy.get('[data-cy="discussion-form-modal"]').within(() => {
				cy.get('input[name="title"]').type(discussionTitle);
				cy.get('[name="participants"]', {timeout: 15000}).eq(0).check();
				cy.get('[name="participants"]').eq(1).check();
				cy.setTinyMceContent('discussionForm-description-control', discussionMessage);
				cy.contains('button', 'Save').click();
			});
			cy.get('[data-cy="discussion-form-modal"]').should('not.exist');

			// 5. Verify discussion appears in "In progress" group
			verifyItemInGroup(discussionTitle, 'In progress');

			// 6. View discussion and verify content is displayed
			cy.get('[data-cy="discussion-manager"]').contains('button', discussionTitle).click();
			cy.get('[data-cy="discussion-display-modal"]').should('be.visible');

			cy.get('[data-cy="discussion-display-modal"]').within(() => {
				// Verify title and message content are displayed
				cy.contains(discussionTitle).should('exist');
				cy.contains(discussionMessage).should('exist');
			});

			// 7. Close the discussion and add a new message (save together)
			cy.get('[data-cy="discussion-display-modal"]').within(() => {
				cy.contains('Close this Discussion').click();
				cy.contains('button', 'Add New Message').click();
			});

			cy.get('[data-cy="discussion-display-modal"]')
				.find('iframe[id$="_ifr"]')
				.scrollIntoView()
				.should('be.visible')
				.invoke('attr', 'id')
				.then((iframeId) => {
					const editorId = iframeId.replace('_ifr', '');
					cy.setTinyMceContent(editorId, replyMessage);
				});

			// 8. Save both the close and the new message
			cy.get('[data-cy="discussion-display-modal"]')
				.contains('button', 'Save')
				.click();

			// Verify new message appears and status is Closed
			cy.get('[data-cy="discussion-display-modal"]').contains(replyMessage).should('exist');
			cy.get('[data-cy="discussion-display-modal"]').contains('Closed').should('exist');

			cy.get('[data-cy="discussion-display-modal"]')
				.contains('button', 'Cancel')
				.click({force: true});

			cy.get('[data-cy="discussion-display-modal"]').should('not.exist');

			// 9. Verify discussion appears in "Closed" group
			verifyItemInGroup(discussionTitle, 'Closed');

			// 10. Verify discussion checkbox is checked (closed), then reopen via table checkbox
			cy.get('[data-cy="discussion-manager"]').contains(discussionTitle)
				.parents('tr')
				.find('input[type="checkbox"]')
				.should('be.checked')
				.uncheck({force: true});

			cy.contains('button', 'Yes').click();

			// 11. Verify discussion moved back to "In progress" group
			verifyItemInGroup(discussionTitle, 'In progress');

			// 12. Edit the discussion title
			openActionsAndClick(discussionTitle, 'Edit');

			cy.get('[data-cy="discussion-form-modal"]', {timeout: 15000}).should('be.visible');

			cy.get('[data-cy="discussion-form-modal"]').within(() => {
				cy.get('input[name="title"]').clear().type(editedTitle);
				cy.contains('button', 'Save').click();
			});

			cy.get('[data-cy="discussion-form-modal"]').should('not.exist');
			cy.get('[data-cy="discussion-manager"]').contains(editedTitle).should('exist');

			// 13. Delete the discussion
			openActionsAndClick(editedTitle, 'Delete');
			cy.contains('button', 'OK').click();

			cy.get('[data-cy="discussion-manager"]').should('not.contain', editedTitle);
		});
	});

	/**
	 * Batch 2: Tasks
	 * Tests: create with different start options, view details, start, close, verify edit disabled
	 */
	describe('Tasks', function() {
		const startedTaskTitle = 'Test Task Started ' + Date.now();
		const notStartedTaskTitle = 'Test Task Not Started ' + Date.now();
		const taskMessage = 'Task message content';

		before(function() {
			cy.findSubmissionAsEditor('dbarnes', null, author);
			cy.get('[data-cy="discussion-manager"]').should('exist');
		});

		it('creates tasks, manages status transitions, and restricts editing when closed', function() {
			// === PART 1: Task with "Begin upon saving" ===

			// 1. Create a task with "Begin upon saving" option
			openAddModal();

			const futureDate = getFutureDate();

			cy.get('[data-cy="discussion-form-modal"]').within(() => {
				cy.get('input[name="title"]').type(startedTaskTitle);
				cy.get('[name="participants"]', {timeout: 15000}).first().check();
				cy.get('[name="taskInfoAdd"]').check();
				cy.get('input[name="dateDue"]').type(futureDate);
				cy.get('[name="taskInfoAssignee"]').first().check();
				cy.get('select[name="taskInfoShouldStart"]').should('have.value', 'true');
				cy.setTinyMceContent('discussionForm-description-control', taskMessage);
				cy.contains('button', 'Save').click();
			});

			cy.get('[data-cy="discussion-form-modal"]').should('not.exist');

			// 2. Verify task appears in "In progress" group
			verifyItemInGroup(startedTaskTitle, 'In progress');

			// 3. View task and verify details are displayed
			cy.get('[data-cy="discussion-manager"]').contains('button', startedTaskTitle).click();
			cy.get('[data-cy="discussion-display-modal"]').should('be.visible');

			cy.get('[data-cy="discussion-display-modal"]').within(() => {
				cy.contains(startedTaskTitle).should('exist');
				cy.contains(taskMessage).should('exist');
				cy.contains('Task Information').should('exist');
			});

			// 4. Close the task via "Complete this task" checkbox in view modal
			cy.get('[data-cy="discussion-display-modal"]').within(() => {
				cy.contains('Complete this task').click();
				cy.contains('button', 'Save').click();
			});

			// Wait for task to be closed
			cy.get('[data-cy="discussion-display-modal"]')
				.contains('Closed')
				.should('exist');

			// 5. Verify Edit is disabled and checkbox is disabled for closed task (cannot reopen)
			cy.get('[data-cy="discussion-display-modal"]')
				.contains('button', 'Edit')
				.should('be.disabled');

			cy.get('[data-cy="discussion-display-modal"]')
				.contains('Complete this task')
				.find('input')
				.should('be.disabled');

			cy.get('[data-cy="discussion-display-modal"]')
				.contains('button', 'Cancel')
				.click({force: true});

			cy.get('[data-cy="discussion-display-modal"]').should('not.exist');

			// 6. Verify task moved to "Closed" group
			verifyItemInGroup(startedTaskTitle, 'Closed');

			// === PART 2: Task with "Do Not Start" ===

			// 7. Create a task with "Do Not Start" option
			openAddModal();

			cy.get('[data-cy="discussion-form-modal"]').within(() => {
				cy.get('input[name="title"]').type(notStartedTaskTitle);
				cy.get('[name="participants"]', {timeout: 15000}).first().check();
				cy.get('[name="taskInfoAdd"]').check();
				cy.get('input[name="dateDue"]').type(futureDate);
				cy.get('[name="taskInfoAssignee"]').first().check();
				cy.get('select[name="taskInfoShouldStart"]').select('false');
				cy.setTinyMceContent('discussionForm-description-control', taskMessage);
				cy.contains('button', 'Save').click();
			});

			cy.get('[data-cy="discussion-form-modal"]').should('not.exist');

			// 8. Verify task appears in "Yet to begin" group
			verifyItemInGroup(notStartedTaskTitle, 'Yet to begin');

			// 9. View task and start via "Start this task" checkbox in view modal
			cy.get('[data-cy="discussion-manager"]').contains('button', notStartedTaskTitle).click();
			cy.get('[data-cy="discussion-display-modal"]').should('be.visible');

			cy.get('[data-cy="discussion-display-modal"]').within(() => {
				cy.contains('Start this task').click();
				cy.contains('button', 'Save').click();
			});

			// Wait for task to be started
			cy.get('[data-cy="discussion-display-modal"]')
				.contains('Task started by')
				.should('exist');
			cy.get('[data-cy="discussion-display-modal"]')
				.contains('Daniel Barnes')
				.should('exist');

			cy.get('[data-cy="discussion-display-modal"]')
				.contains('button', 'Cancel')
				.click({force: true});

			cy.get('[data-cy="discussion-display-modal"]').should('not.exist');

			// 10. Verify task moved to "In progress" group
			verifyItemInGroup(notStartedTaskTitle, 'In progress');

			// 11. Close the task via table checkbox
			cy.get('[data-cy="discussion-manager"]').contains(notStartedTaskTitle)
				.parents('tr')
				.find('input[type="checkbox"]')
				.last()
				.check({force: true});

			cy.contains('button', 'Yes').click();

			// 12. Verify task moved to "Closed" group
			verifyItemInGroup(notStartedTaskTitle, 'Closed');
		});
	});

	/**
	 * Batch 3: Access Control
	 * Tests: edit/delete permissions based on role (owner, manager, responsible participant, non-responsible participant)
	 */
	describe('Access Control', function() {
		const accessTestTaskTitle = 'Access Control Test Task ' + Date.now();

		it('verifies edit/delete access based on role', function() {
			// 1. SETUP: dbarnes creates task with participants
			cy.findSubmissionAsEditor('dbarnes', null, author);
			cy.get('[data-cy="discussion-manager"]').should('exist');

			openAddModal();

			const futureDate = getFutureDate();

			cy.get('[data-cy="discussion-form-modal"]').within(() => {
				cy.get('input[name="title"]').type(accessTestTaskTitle);

				// Add participants - dbuskins will be responsible, minoue will not
				cy.contains('David Buskins').find('[name="participants"]').check();
				cy.contains('Minoti Inoue').find('[name="participants"]').check();

				// Enable task info
				cy.get('[name="taskInfoAdd"]').check();
				cy.get('input[name="dateDue"]').type(futureDate);

				// Set dbuskins as responsible assignee (radio button)
				cy.get('label:has([name="taskInfoAssignee"])')
					.contains('David Buskins')
					.click();

				cy.get('select[name="taskInfoShouldStart"]').select('true');
				cy.setTinyMceContent('discussionForm-description-control', 'Access control test message');
				cy.contains('button', 'Save').click();
			});

			cy.get('[data-cy="discussion-form-modal"]').should('not.exist');

			// Verify task was created
			cy.get('[data-cy="discussion-manager"]').contains(accessTestTaskTitle).should('exist');

			// Verify owner (dbarnes) sees actions menu
			verifyActionsMenuVisible(accessTestTaskTitle);

			cy.logout();

			// 2. MANAGER ACCESS: rvaca (Journal Manager has full access)
			cy.findSubmissionAsEditor('rvaca', null, author);
			cy.get('[data-cy="discussion-manager"]').should('exist');

			// Verify manager sees actions menu
			verifyActionsMenuVisible(accessTestTaskTitle);

			cy.logout();

			// 3. RESPONSIBLE PARTICIPANT: dbuskins (assigned as responsible)
			cy.findSubmissionAsEditor('dbuskins', null, author);
			cy.get('[data-cy="discussion-manager"]').should('exist');

			// Verify responsible participant sees actions menu
			verifyActionsMenuVisible(accessTestTaskTitle);

			cy.logout();

			// 4. NON-RESPONSIBLE PARTICIPANT: minoue (participant but NOT responsible)
			cy.findSubmissionAsEditor('minoue', null, author);
			cy.get('[data-cy="discussion-manager"]').should('exist');

			// Verify non-responsible participant does NOT see actions menu
			verifyActionsMenuNotVisible(accessTestTaskTitle);

			// Verify table checkboxes are disabled
			verifyTableCheckboxesDisabled(accessTestTaskTitle);

			// Verify can still view task (click title) but no edit/delete access
			cy.get('[data-cy="discussion-manager"]').contains('button', accessTestTaskTitle).click();
			cy.get('[data-cy="discussion-display-modal"]').should('be.visible');

			// Verify Edit button is hidden (not present)
			cy.get('[data-cy="discussion-display-modal"]')
				.contains('button', 'Edit')
				.should('not.exist');

			cy.get('[data-cy="discussion-display-modal"]')
				.contains('button', 'Cancel')
				.click({force: true});

			cy.get('[data-cy="discussion-display-modal"]').should('not.exist');

			cy.logout();

			// 5. CLEANUP: dbarnes deletes task
			cy.findSubmissionAsEditor('dbarnes', null, author);
			cy.get('[data-cy="discussion-manager"]').should('exist');

			openActionsAndClick(accessTestTaskTitle, 'Delete');
			cy.contains('button', 'OK').click();

			cy.get('[data-cy="discussion-manager"]').should('not.contain', accessTestTaskTitle);
		});
	});
});
