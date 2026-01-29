/**
 * @file cypress/tests/data/40-CreateUsers.cy.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Creates users with invitation', function() {
		cy.login('admin', 'admin');
		cy.get('a:contains("admin"):visible').click();
		cy.get('a:contains("Dashboard")').click();
		cy.get('nav').contains('Settings').click();
		// Ensure submenu item click despite animation
		cy.get('nav').contains('Users & Roles').click({ force: true });

		var users = [
			{
				'username': 'rvaca',
				'givenName': 'Ramiro',
				'familyName': 'Vaca',
				'country': 'Mexico',
				'affiliation': 'Universidad Nacional Autónoma de México',
				'mustChangePassword': true,
				'roles': ['Journal manager']
			}
		];
		cy.logout();
		users.forEach(user => {
			cy.createUserByInvitation(user);
		});
	});
})
