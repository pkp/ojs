/**
 * @file cypress/tests/integration/TaskTemplates.cy.js
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Cypress tests for Tasks & Discussions Templates management in Settings > Workflow.
 */

describe('Tasks & Discussions Templates', function() {
	// Shared template names used across tests
	const unrestrictedTemplateName = 'E2E Unrestricted Template ' + Date.now();
	const restrictedTemplateName = 'E2E Copyeditor Only Template ' + Date.now();
	const submissionAuthor = 'Woods'; // Woods submission is in Copyediting stage

	/**
	 * Helper to open the Add Template modal for a specific stage
	 */
	function openAddTemplateModal(stageName) {
		cy.contains(stageName)
			.closest('tr')
			.contains('Add template')
			.click();
		// Wait for modal to open
		cy.get('[data-cy="active-modal"]').should('be.visible');
	}

	/**
	 * Helper to open actions menu for a template and click an action
	 */
	function openActionsAndClick(templateName, actionName) {
		cy.contains(templateName)
			.closest('tr')
			.find('button[aria-label="More Actions"]')
			.click({force: true});

		cy.contains('button', actionName).click({force: true});
	}

	/**
	 * Helper to wait for task template form modal to close
	 */
	function waitForModalClose() {
		cy.get('[data-cy="active-modal"]', {timeout: 10000}).should('not.exist');
	}

	/**
	 * Helper to click Save button inside modal
	 */
	function clickModalSave() {
		cy.get('[data-cy="active-modal"]').within(() => {
			cy.contains('button', 'Save').click();
		});
	}

	/**
	 * Helper to select a specific role by name in the user groups list
	 */
	function selectRole(roleName) {
		cy.get('[data-cy="active-modal"]').within(() => {
			cy.contains('label', roleName)
				.find('input[type="checkbox"]')
				.check({force: true});
		});
	}

	before(function() {
		cy.login('admin', 'admin', 'publicknowledge');
		cy.visit('/index.php/publicknowledge/management/settings/workflow#taskTemplates');
		cy.contains('Tasks and Discussions Templates').should('be.visible');
	});

	after(function() {
		// Clean up all templates created during tests
		cy.login('admin', 'admin', 'publicknowledge');
		cy.visit('/index.php/publicknowledge/management/settings/workflow#taskTemplates');
		cy.contains('Tasks and Discussions Templates').should('be.visible');

		// Delete unrestricted template
		openActionsAndClick(unrestrictedTemplateName, 'Delete');
		cy.contains('button', 'OK').click();
		cy.contains(unrestrictedTemplateName).should('not.exist');

		// Delete restricted template
		openActionsAndClick(restrictedTemplateName, 'Delete');
		cy.contains('button', 'OK').click();
		cy.contains(restrictedTemplateName).should('not.exist');
	});

	it('manages task and discussion templates', function() {
		const editableTemplateName = 'Editable Template ' + Date.now();
		const editedTemplateName = 'Edited Template ' + Date.now();

		// ===== 1. Verify all stage groups have "Add template" button =====
		const stages = ['Submission Stage', 'Review Stage', 'Copyediting Stage', 'Production Stage'];
		stages.forEach(stage => {
			cy.contains(stage)
				.closest('tr')
				.contains('Add template')
				.should('exist');
		});

		// ===== 2. Create unrestricted template for Copyediting (reused in E2E tests) =====
		openAddTemplateModal('Copyediting Stage');

		// Validate required fields - try to save without filling anything
		clickModalSave();
		cy.get('[data-cy="active-modal"]').within(() => {
			cy.contains('Name').parents().contains('This field is required').should('exist');
			cy.get('#taskTemplate-description-control').parents().contains('This field is required').should('exist');
		});

		// Now fill in the form
		cy.setTinyMceContent('taskTemplate-description-control', 'This template is available to all users.');
		// Type title after TinyMCE (this blurs TinyMCE and enables Save button)
		cy.get('[data-cy="active-modal"]').within(() => {
			cy.get('input[name="title"]').type(unrestrictedTemplateName);
			cy.contains('Mark as unrestricted').should('exist');
		});

		clickModalSave();
		waitForModalClose();

		cy.contains(unrestrictedTemplateName).should('exist');

		// ===== 3. Create Copyeditor-restricted template (reused in E2E tests) =====
		openAddTemplateModal('Copyediting Stage');

		cy.get('[data-cy="active-modal"]').within(() => {
			cy.get('input[name="title"]').type(restrictedTemplateName);
			cy.contains('Limit access to specific roles').click();
		});

		// Select Copyeditor role specifically
		selectRole('Copyeditor');

		cy.setTinyMceContent('taskTemplate-description-control', 'This template is restricted to Copyeditors only.');

		clickModalSave();
		waitForModalClose();

		cy.contains(restrictedTemplateName).should('exist');

		// ===== 4. Test Edit, Task Info with Due Date, and Auto-Add Toggle =====
		openAddTemplateModal('Submission Stage');

		cy.get('[data-cy="active-modal"]').within(() => {
			cy.get('input[name="title"]').type(editableTemplateName);
			// Add task info with due date
			cy.get('input[name="taskInfoAdd"]').check();
			cy.get('select[name="dueInterval"]').select('P1W');
			// Enable auto-add via the include checkbox
			cy.get('input[name="include"]').check();
		});

		cy.setTinyMceContent('taskTemplate-description-control', 'Template for edit and task info test.');

		clickModalSave();
		waitForModalClose();

		// Verify template exists and auto-add is enabled (from include checkbox)
		cy.contains(editableTemplateName).should('exist');
		cy.contains(editableTemplateName)
			.closest('tr')
			.find('input[type="checkbox"]')
			.should('be.checked');

		// Edit the template
		openActionsAndClick(editableTemplateName, 'Edit');

		cy.get('[data-cy="active-modal"]').within(() => {
			cy.get('input[name="title"]').clear().type(editedTemplateName);
		});

		clickModalSave();
		waitForModalClose();

		cy.contains(editedTemplateName).should('exist');

		// Toggle Auto-Add OFF (currently checked, should become unchecked)
		cy.contains(editedTemplateName)
			.closest('tr')
			.find('input[type="checkbox"]')
			.should('be.checked')
			.click({force: true});

		cy.contains('button', 'Yes').click();

		cy.contains(editedTemplateName)
			.closest('tr')
			.find('input[type="checkbox"]')
			.should('not.be.checked');

		// Delete this template (not needed for E2E)
		openActionsAndClick(editedTemplateName, 'Delete');
		cy.contains('button', 'OK').click();
		cy.contains(editedTemplateName).should('not.exist');

		cy.logout();
	});

	/**
	 * E2E Tests: Template visibility based on role restrictions
	 * Uses templates created in the first test.
	 */
	describe('Template Visibility by Role', function() {
		it('Author sees unrestricted but NOT Copyeditor-restricted template', function() {
			// zwoods is an Author (NOT a Copyeditor) - should not see Copyeditor-only template
			cy.login('zwoods', null, 'publicknowledge');
			cy.visit('index.php/publicknowledge/dashboard/mySubmissions');

			// Find the Woods submission and click View
			cy.contains('table tr', submissionAuthor, {timeout: 20000})
				.contains('button', 'View')
				.click({force: true});

			// Wait for submission modal to load
			cy.get('[data-cy="active-modal"]', {timeout: 10000}).should('be.visible');

			// Navigate to Copyediting stage
			cy.get('[data-cy="active-modal"]').find('nav').contains('a', 'Copyediting').click();

			// Wait for discussion manager to load and scroll into view
			cy.get('[data-cy="discussion-manager"]', {timeout: 10000})
				.scrollIntoView()
				.should('exist');

			// Open Add discussion modal
			cy.get('[data-cy="discussion-manager"]').within(() => {
				cy.contains('button', 'Add').click();
			});

			// Wait for form modal to be visible
			cy.get('[data-cy="discussion-form-modal"]', {timeout: 10000}).should('be.visible');

			// Verify unrestricted template is visible but restricted is NOT
			cy.get('[data-cy="discussion-form-modal"]').within(() => {
				cy.get('ul[role="list"]').should('exist');
				cy.contains(unrestrictedTemplateName).should('exist');
				cy.contains(restrictedTemplateName).should('not.exist');
			});

			// Close the modal using Cancel button
			cy.get('[data-cy="discussion-form-modal"]').contains('button', 'Cancel').click({force: true});
			cy.logout();
		});

		it('Copyeditor sees both unrestricted and Copyeditor-restricted template', function() {
			// svogt is a Copyeditor - should see Copyeditor-only template
			cy.findSubmissionAsEditor('svogt', null, submissionAuthor);

			// Navigate to Copyediting stage
			cy.get('[data-cy="active-modal"]').find('nav').contains('a', 'Copyediting').click();

			// Wait for discussion manager to load and scroll into view
			cy.get('[data-cy="discussion-manager"]', {timeout: 10000})
				.scrollIntoView()
				.should('exist');

			// Open Add discussion modal
			cy.get('[data-cy="discussion-manager"]').within(() => {
				cy.contains('button', 'Add').click();
			});

			// Wait for form modal to be visible
			cy.get('[data-cy="discussion-form-modal"]', {timeout: 10000}).should('be.visible');

			// Verify both templates are visible (Copyeditor sees unrestricted + their restricted)
			cy.get('[data-cy="discussion-form-modal"]').within(() => {
				cy.get('ul[role="list"]').should('exist');
				cy.contains(unrestrictedTemplateName).should('exist');
				cy.contains(restrictedTemplateName).should('exist');
			});

			// Close the modal using Cancel button
			cy.get('[data-cy="discussion-form-modal"]').contains('button', 'Cancel').click({force: true});
			cy.logout();
		});
	});
});
