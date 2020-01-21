/**
 * @file cypress/tests/data/60-content/FpaglieriSubmission.spec.js
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Create a submission', function() {
		var title = 'Hansen & Pinto: Reason Reclaimed';
		cy.register({
			'username': 'fpaglieri',
			'givenName': 'Fabio',
			'familyName': 'Paglieri',
			'affiliation': 'University of Rome',
			'country': 'Italy',
		});

		cy.createSubmission({
			'section': 'Reviews',
			title,
			'abstract': 'None.',
		});
	});
});
