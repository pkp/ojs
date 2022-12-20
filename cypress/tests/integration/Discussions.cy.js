/**
 * @file cypress/tests/integration/Discussions.spec.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 */

 describe('Discussions', function() {
    var author = 'Corino';
    var discussion = 'Test discussion';
    var message = 'Test discussion message';
    var discussionGrid = '[id^="component-grid-queries"]';

	it('#8097 Discussion deleted or preserved correctly when modal closed while creating discussion', function() {
		cy.findSubmissionAsEditor('dbarnes', null, author);
        cy.get(discussionGrid);
        cy.contains('Add discussion').click();
        cy.get('#queryForm label:contains("' + author + '")').click();
        cy.get('#queryForm input[name="subject"]').type(discussion);
        cy.get('textarea[name="comment"]').then(node => {
			cy.setTinyMceContent(node.attr('id'), message);
		});
        cy.get('.pkpModalCloseButton').click();
        cy.on('window:confirm', () => true);
        cy.reload();
        cy.get(discussionGrid);
        cy.contains(discussion).should('not.exist');
        cy.contains('Add discussion').click();
        cy.get('#queryForm label:contains("' + author + '")').click();
        cy.get('#queryForm input[name="subject"]').type(discussion);
        cy.get('textarea[name="comment"]').then(node => {
			cy.setTinyMceContent(node.attr('id'), message);
		});
        cy.get('.pkpModalCloseButton').click();
        cy.on('window:confirm', () => false);
        cy.get('#queryForm button:contains("OK")').click();
        cy.get(discussionGrid + ' a:contains("' + discussion + '")');
    });
});
