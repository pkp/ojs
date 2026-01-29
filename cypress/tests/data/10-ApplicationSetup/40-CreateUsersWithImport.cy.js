/**
 * @file cypress/tests/data/40-CreateUsersWithImport.cy.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Imports users via CLI importExport tool', function() {
		// Use the CLI import tool to quickly import users from XML
		// This is much faster than creating users through the UI
		cy.exec(
			'php tools/importExport.php UserImportExportPlugin import cypress/tests/data/10-ApplicationSetup/test-users.xml publicknowledge',
			{
				timeout: 60000,
				failOnNonZeroExit: true
			}
		).then((result) => {
			expect(result.code).to.eq(0);
			cy.log('Successfully imported test users');
		});
	});

	it('Verifies users were created', function() {
		// Login as admin to verify users exist
		cy.login('admin', 'admin', 'publicknowledge');
		cy.visit('/index.php/publicknowledge/management/settings/access');
		
		// Check for a sample of users across different roles
		const usersToVerify = [
			'dbarnes',      // Journal editor
			'jjanssen',     // Reviewer
			'mfritz',       // Copyeditor
			'gcox',         // Layout Editor
			'lrodriguez',   // Production editor
			'mchen',        // Guest editor
			'npatel',       // Designer
			'ohassan',      // Funding coordinator
			'qanderson',    // Marketing and sales coordinator
			'rsilva',       // Translator
			'smbeki'        // Subscription Manager
		];
		
		usersToVerify.forEach(username => {
			cy.get('tr').contains(username).should('exist');
		});
		
		cy.logout();
	});
});
