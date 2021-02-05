/**
 * @file cypress/tests/data/10-Installation.spec.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data Suite Tests', function() {
	it('Installs the software', function() {
		cy.install();
	})
})
