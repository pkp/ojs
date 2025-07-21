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
import './commands';

require('cypress-failed-log');

// Rather than processing all pending jobs at the end of each test which may sometimes lead to timeouts
// if there are a large number of time consuming jobs queued, we will process 2 before each test
// and process 3 after each test and each of these 3 will be individually to minimize the chances of
// server timeouts via one single long running process.
beforeEach(function() {
	cy.runQueueJobs(null, false, true);
	cy.runQueueJobs(null, false, true);
	cy.runQueueJobs(null, false, true);
});

afterEach(function() {
	cy.runQueueJobs(null, false, true);
	cy.runQueueJobs(null, false, true);
	cy.runQueueJobs(null, false, true);
});
