// ***********************************************************
// This example support/index.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands'

import '@foreachbe/cypress-tinymce'

require('cypress-failed-log')

// See https://stackoverflow.com/questions/58657895/is-there-a-reliable-way-to-have-cypress-exit-as-soon-as-a-test-fails/58660504#58660504
function abortEarly() {
	if (this.currentTest.state === 'failed') {
		return cy.task('shouldSkip', true);
	}
	cy.task('shouldSkip').then(value => {
		if (value) this.skip();
	});
}

beforeEach(abortEarly);
afterEach(abortEarly);

before(() => {
	if (Cypress.browser.isHeaded) {
		// Reset the shouldSkip flag at the start of a run, so that it
		//  doesn't carry over into subsequent runs.
		// Do this only for headed runs because in headless runs,
		//  the `before` hook is executed for each spec file.
		cy.task('resetShouldSkipFlag');
	}
});
