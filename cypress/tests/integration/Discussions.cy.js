/**
 * @file cypress/tests/integration/Discussions.cy.js
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2000-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
		cy.get('[data-cy="active-modal"]').should('be.visible');
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

	describe('Discussions & Tasks CRUD', function() {
		const discussionTitle = 'Test Discussion ' + Date.now();
		const discussionMessage = 'This is a test discussion message';
		const editedDiscussionTitle = 'Edited Discussion ' + Date.now();
		const replyMessage = 'This is a reply message ' + Date.now();
		const taskTitle = 'Test Task ' + Date.now();
		const taskMessage = 'Task message content';

		before(function() {
			cy.findSubmissionAsEditor('dbarnes', null, author);
			cy.get('[data-cy="discussion-manager"]').should('exist');
		});

		it('performs full CRUD on discussions and tasks', function() {
			// === DISCUSSIONS ===

			// 1. Verify status groups are displayed
			cy.contains('Yet to begin').should('exist');
			cy.contains('In progress').should('exist');
			cy.contains('Closed').should('exist');

			// 2. Create a discussion with participants and message
			openAddModal();

			cy.get('[data-cy="active-modal"]').within(() => {
				cy.get('input[name="title"]').type(discussionTitle);
				cy.get('[name="participants"]', {timeout: 15000}).eq(0).check();
				cy.get('[name="participants"]').eq(1).check();
				cy.setTinyMceContent('discussionForm-description-control', discussionMessage);
				cy.contains('button', 'Save').click();
			});

			// 3. Verify discussion appears in "In progress" group
			verifyItemInGroup(discussionTitle, 'In progress');

			// 4. View discussion, close it, and add a message
			cy.get('[data-cy="discussion-manager"]').contains('button', discussionTitle).click();
			cy.get('[data-cy="active-modal"]').should('be.visible');

			cy.get('[data-cy="active-modal"]').within(() => {
				cy.contains(discussionTitle).should('exist');
				cy.contains(discussionMessage).should('exist');
				cy.contains('Close this Discussion').click();
				cy.contains('button', 'Add New Message').click();
			});

			cy.get('[data-cy="active-modal"]')
				.find('iframe[id$="_ifr"]')
				.scrollIntoView()
				.should('be.visible')
				.invoke('attr', 'id')
				.then((iframeId) => {
					const editorId = iframeId.replace('_ifr', '');
					cy.setTinyMceContent(editorId, replyMessage);
				});

			cy.get('[data-cy="active-modal"]')
				.contains('button', 'Save')
				.click();

			cy.get('[data-cy="active-modal"]').contains(replyMessage).should('exist');
			cy.get('[data-cy="active-modal"]').contains('Closed').should('exist');

			cy.get('[data-cy="active-modal"]')
				.contains('button', 'Cancel')
				.click({force: true});

			// 5. Verify closed, then reopen via table checkbox
			verifyItemInGroup(discussionTitle, 'Closed');

			cy.get('[data-cy="discussion-manager"]').contains(discussionTitle)
				.parents('tr')
				.find('input[type="checkbox"]')
				.should('be.checked')
				.uncheck({force: true});

			cy.contains('button', 'Yes').click();
			verifyItemInGroup(discussionTitle, 'In progress');

			// 6. Edit the discussion title
			openActionsAndClick(discussionTitle, 'Edit');
			cy.get('[data-cy="active-modal"]', {timeout: 15000}).should('be.visible');

			cy.get('[data-cy="active-modal"]').within(() => {
				cy.get('input[name="title"]').clear().type(editedDiscussionTitle);
				cy.contains('button', 'Save').click();
			});

			cy.get('[data-cy="discussion-manager"]').contains(editedDiscussionTitle).should('exist');

			// 7. Delete the discussion
			openActionsAndClick(editedDiscussionTitle, 'Delete');
			cy.contains('button', 'OK').click();
			cy.get('[data-cy="discussion-manager"]').should('not.contain', editedDiscussionTitle);

			// === TASKS ===

			const futureDate = getFutureDate();

			// Create a task with "Do Not Start" option
			openAddModal();

			// Test unsaved changes warning (click No to stay in modal)
			cy.get('[data-cy="active-modal"]').within(() => {
				cy.get('input[name="title"]').type('Unsaved test');
			});
			cy.get('[data-cy="active-modal"]')
				.contains('button', 'Cancel')
				.click({force: true});
			cy.get('[data-cy="dialog"]').contains('button', 'No').click();
			cy.get('[data-cy="active-modal"]').should('be.visible');

			// Continue with task creation
			cy.get('[data-cy="active-modal"]').within(() => {
				cy.get('input[name="title"]').clear().type(taskTitle);
				cy.get('[name="participants"]', {timeout: 15000}).first().check();
				cy.get('[name="taskInfoAdd"]').check();
				cy.get('input[name="dateDue"]').type(futureDate);
				cy.get('[name="taskInfoAssignee"]').first().check();
				cy.get('select[name="taskInfoShouldStart"]').select('false');
				cy.setTinyMceContent('discussionForm-description-control', taskMessage);
				cy.contains('button', 'Save').click();
			});

			verifyItemInGroup(taskTitle, 'Yet to begin');

			// View task, start it, then complete it in one modal session
			cy.get('[data-cy="discussion-manager"]').contains('button', taskTitle).click();
			cy.get('[data-cy="active-modal"]').should('be.visible');

			cy.get('[data-cy="active-modal"]').within(() => {
				cy.contains(taskTitle).should('exist');
				cy.contains(taskMessage).should('exist');
				cy.contains('Task Information').should('exist');
				cy.contains('Start this task').click();
				cy.contains('button', 'Save').click();
			});

			// Verify task started
			cy.get('[data-cy="active-modal"]')
				.contains('Task started by')
				.should('exist');

			// Complete the task (still in same modal)
			cy.get('[data-cy="active-modal"]').within(() => {
				cy.contains('Complete this task').click();
				cy.contains('button', 'Save').click();
			});

			// Verify closed and edit disabled
			cy.get('[data-cy="active-modal"]')
				.contains('Closed')
				.should('exist');

			cy.get('[data-cy="active-modal"]')
				.contains('button', 'Edit')
				.should('be.disabled');

			cy.get('[data-cy="active-modal"]')
				.contains('Complete this task')
				.find('input')
				.should('be.disabled');

			// Now close modal
			cy.get('[data-cy="active-modal"]')
				.contains('button', 'Cancel')
				.click({force: true});

			verifyItemInGroup(taskTitle, 'Closed');
		});
	});

	describe('Access Control', function() {
		const accessTestTaskTitle = 'Access Control Test Task ' + Date.now();

		it('verifies edit/delete access based on role', function() {
			// 1. SETUP: dbarnes creates task with participants
			cy.findSubmissionAsEditor('dbarnes', null, author);
			cy.get('[data-cy="discussion-manager"]').should('exist');

			openAddModal();

			const futureDate = getFutureDate();

			cy.get('[data-cy="active-modal"]').within(() => {
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

			cy.get('[data-cy="discussion-manager"]').contains(accessTestTaskTitle).should('exist');
			cy.logout();

			// 2. NON-RESPONSIBLE PARTICIPANT: minoue (participant but NOT responsible)
			cy.findSubmissionAsEditor('minoue', null, author);
			cy.get('[data-cy="discussion-manager"]').should('exist');

			verifyActionsMenuNotVisible(accessTestTaskTitle);
			verifyTableCheckboxesDisabled(accessTestTaskTitle);

			// Verify can view task but no edit access
			cy.get('[data-cy="discussion-manager"]').contains('button', accessTestTaskTitle).click();
			cy.get('[data-cy="active-modal"]').should('be.visible');

			cy.get('[data-cy="active-modal"]')
				.contains('button', 'Edit')
				.should('not.exist');

			cy.get('[data-cy="active-modal"]')
				.contains('button', 'Cancel')
				.click({force: true});

			cy.logout();

			// 3. RESPONSIBLE PARTICIPANT + CLEANUP: dbuskins verifies access and deletes task
			cy.findSubmissionAsEditor('dbuskins', null, author);
			cy.get('[data-cy="discussion-manager"]').should('exist');

			verifyActionsMenuVisible(accessTestTaskTitle);
			openActionsAndClick(accessTestTaskTitle, 'Delete');
			cy.contains('button', 'OK').click();

			cy.get('[data-cy="discussion-manager"]').should('not.contain', accessTestTaskTitle);
		});
	});
});
