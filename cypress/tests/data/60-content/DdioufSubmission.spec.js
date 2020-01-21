/**
 * @file cypress/tests/data/60-content/DdioufSubmission.spec.js
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Create a submission', function() {
		var title = 'Genetic transformation of forest trees';
		cy.register({
			'username': 'ddiouf',
			'givenName': 'Diaga',
			'familyName': 'Diouf',
			'affiliation': 'Alexandria University',
			'country': 'Egypt',
		});

		cy.createSubmission({
			'section': 'Preprints',
			title,
			'abstract': 'In this review, the recent progress on genetic transformation of forest trees were discussed. Its described also, different applications of genetic engineering for improving forest trees or understanding the mechanisms governing genes expression in woody plants.',
		});
	});
});
