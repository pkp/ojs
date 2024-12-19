/**
 * @file cypress/tests/integration/Orcid.cy.js
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2000-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Email Template Access Tests', function() {
	it('Checks that user cannot access restricted template not assigned to their group', () => {
		cy.login('admin', 'admin', 'publicknowledge');
		cy.visit('/index.php/publicknowledge/management/settings/manageEmails');

		cy.openEmailTemplate('Discussion (Copyediting)', 'Discussion (Copyediting)');
		// Remove all existing access
		cy.setEmailTemplateUnrestrictedTo(false);
		cy.get('input[name="assignedUserGroupIds"]')
			.as('checkboxes')
			.uncheck({force: true});

		cy.contains('button', 'Save').click();
		cy.logout();

		// Login as user without access - copyedit
		cy.login('svogt');
		cy.visit(
			'index.php/publicknowledge/en/dashboard/editorial?currentViewId=assigned-to-me'
		);
		cy.contains('button', 'View').first().click();
		cy.contains('a', 'Copyediting').click();
		cy.contains('a', 'Add discussion').click();

		cy.get('select#template').find('option').contains('Discussion (Copyediting)').should('not.exist');
	});

	it('Checks that user can access unrestricted template not specifically assigned to their group', () => {
		cy.login('admin', 'admin', 'publicknowledge');
		cy.visit('/index.php/publicknowledge/management/settings/manageEmails');

		cy.openEmailTemplate('Discussion (Copyediting)', 'Discussion (Copyediting)');

		cy.get('input[name="assignedUserGroupIds"]')
			.as('checkboxes')
			.uncheck({force: true});

		cy.contains('button', 'Save').click();
		cy.reload();
		cy.openEmailTemplate('Discussion (Copyediting)', 'Discussion (Copyediting)');

		cy.setEmailTemplateUnrestrictedTo(true);

		cy.contains('button', 'Save').click();
		cy.logout();

		// Login as user with access - copyedit
		cy.login('svogt');
		cy.visit(
			'index.php/publicknowledge/en/dashboard/editorial?currentViewId=assigned-to-me'
		);
		cy.contains('button', 'View').first().click();
		cy.contains('a', 'Copyediting').click();
		cy.contains('a', 'Add discussion').click();

		cy.get('select#template').find('option').contains('Discussion (Copyediting)').should('to.exist');
	});

	it('Checks that user can access template assigned to their group', () => {
		cy.login('admin', 'admin', 'publicknowledge');
		cy.visit('/index.php/publicknowledge/management/settings/manageEmails');

		cy.openEmailTemplate('Discussion (Copyediting)', 'Discussion (Copyediting)');
		cy.setEmailTemplateUnrestrictedTo(false);
		cy.contains('label', 'Copyeditor').find('input[type="checkbox"]').check({force: true});
		cy.contains('button', 'Save').click();
		cy.logout();

		// Login as user with access - copyedit
		cy.login('svogt');
		cy.visit(
			'index.php/publicknowledge/en/dashboard/editorial?currentViewId=assigned-to-me'
		);
		cy.contains('button', 'View').first().click();
		cy.contains('a', 'Copyediting').click();
		cy.contains('a', 'Add discussion').click();

		cy.get('select#template').find('option').contains('Discussion (Copyediting)').should('to.exist');
	});
});
