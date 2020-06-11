/**
 * @file cypress/tests/integration/Subscriptions.spec.js
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Subscription tests', function() {
	it('Checks open-access publishing', function() {
		cy.visit('');
		cy.get('a:contains("Archives")').click();
		cy.get('a:contains("Vol. 1 No. 2 (2014)")').click();
		cy.get('a.obj_galley_link').should('not.have.class', 'restricted');
		cy.get('a.obj_galley_link:first').click();
		cy.get('iframe'); // The PDF viewer loads; we can't inspect within it, though.
	});

	it('Configures subscriptions', function() {
		cy.login('dbarnes', null, 'publicknowledge');
		cy.get('a:contains("Distribution")').click();

		// Payment settings
		cy.get('button#payments-button').click();
		cy.get('span:contains("Payments will be enabled")').click(); // Turn on payments
		cy.get('select#paymentSettings-paymentPluginName-control').select('Manual Fee Payment');
		cy.get('select#paymentSettings-currency-control').select('Canadian Dollar');
		cy.get('textarea#paymentSettings-manualInstructions-control').type('In order to complete your payment, please...', {delay: 0});
		cy.get('div#payments button:contains("Save")').click();
		cy.get('#payments [role="status"]').contains('Saved');

		// Access settings
		cy.waitJQuery();
		cy.get('button#access-button').click();
		cy.get('label:contains("The journal will require subscriptions") input').click();
		cy.get('div#access button:contains("Save")').click();
		cy.get('#access [role="status"]').contains('Saved');

		// FIXME: The payment menu should now be visible, but it's not. (pkp/pkp-lib#5408)
		cy.reload();

		// Configure an issue for subscription.
		cy.get('.app__nav a:contains("Issues")').click();
		cy.get('button:contains("Back Issues")').click();
		cy.get('a:contains("Vol. 1 No. 2 (2014)")').click();
		cy.get('div.pkp_modal_panel a:contains("Access")').click();
		cy.get('select#accessStatus').select('Subscription');
		cy.get('form#issueAccessForm button:contains("Save")').click();
		cy.get('div:contains("Your changes have been saved.")');

		// Set up subscription policies
		cy.get('.app__nav a:contains("Payments")').click();
		cy.get('a[name=subscriptionPolicies]').click();
		cy.get('input[id^="subscriptionName-"]').type('Sebastiano Mortensen', {delay: 0});
		cy.get('input[id^="subscriptionEmail-"]').type('smortensen@mailinator.com', {delay: 0});
		cy.get('textarea[id^="subscriptionMailingAddress"]').type('123 456th Street', {delay: 0});
		cy.get('form#subscriptionPolicies button:contains("Save")').click();
		cy.get('div:contains("Your changes have been saved.")');
	});

	it('Checks subscription-based publishing without login', function() {
		cy.visit('');
		cy.get('a:contains("Archives")').click();
		cy.get('a:contains("Vol. 1 No. 2 (2014)")').click();
		cy.get('a.obj_galley_link').should('have.class', 'restricted');
		cy.get('a.obj_galley_link:first').click();
		cy.get('p:contains("Subscription required to access item.")');
	});

	it('Checks editorial access to subscription-based content', function() {
		cy.login('dbarnes', null, 'publicknowledge');
		cy.visit('');
		cy.get('a:contains("Archives")').click();
		cy.get('a:contains("Vol. 1 No. 2 (2014)")').click();
		cy.get('a.obj_galley_link').should('have.class', 'restricted');
		cy.get('a.obj_galley_link:first').click();
		cy.get('iframe'); // The PDF viewer loads; we can't inspect within it, though.
	});

	it('Checks unauthorized access to subscription-based content', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		// Create a reader user for the subscription
		cy.get('.app__nav a:contains("Users & Roles")').click();
		cy.createUser({
			'username': 'reader',
			'givenName': 'Rea',
			'familyName': 'Der',
			'country': 'Canada',
			'affiliation': 'Simon Fraser University',
			'roles': ['Reader']
		});

		cy.logout();

		// See if the newly-subscribed user has a subscription
		cy.login('reader', null, 'publicknowledge');
		cy.visit('');
		cy.get('a:contains("Archives")').click();
		cy.get('a:contains("Vol. 1 No. 2 (2014)")').click();
		cy.get('a.obj_galley_link').should('have.class', 'restricted');
		cy.get('a.obj_galley_link:first').click();
		cy.get('h3:contains("Subscriptions Contact")');
	});

	it('Creates a subscription', function() {
		cy.login('dbarnes', null, 'publicknowledge');

		// Set up an individual subscription type
		cy.get('.app__nav a:contains("Payments")').click();
		cy.get('a[name="subscriptionTypes"]').click();
		cy.get('a:contains("Create New Subscription Type")').click();
		cy.wait(1000); // Form initialization problem
		cy.get('form#subscriptionTypeForm input[id^="typeName-en_US-"]').type('Yearly Subscription', {delay: 0});
		cy.get('form#subscriptionTypeForm select[name=currency]').select('Canadian Dollar (CAD)');
		cy.get('form#subscriptionTypeForm input[name=cost]').type('50', {delay: 0});
		cy.get('form#subscriptionTypeForm input[name=duration]').type('12', {delay: 0});
		cy.get('form#subscriptionTypeForm input#individual').click();
		cy.get('form#subscriptionTypeForm button:contains("Save")').click();
		cy.get('div:contains("Your changes have been saved.")');
		cy.waitJQuery();

		// Grant the reader a new subscription
		cy.get('div#subscriptionsTabs a[name="individualSubscription"]').click();
		cy.waitJQuery();
		cy.get('div#individualSubscriptionsGridContainer a:contains("Create New Subscription")').click();
		cy.wait(1000); // Form initialization problem
		cy.get('form#userSearchForm input[name=search]').type('Der');
		cy.get('form#userSearchForm button:contains("Search")').click();
		cy.waitJQuery();
		cy.get('form#individualSubscriptionForm input[name="userId"]').click(); // Should be only match
		cy.get('form#individualSubscriptionForm select#typeId').select('Yearly Subscription - 1 year - 50.00 CAD');
		cy.get('form#individualSubscriptionForm select#status').select('Active');
		cy.get('form#individualSubscriptionForm input[id^="dateStart-"]:visible').type((new Date().getFullYear()) + "-01-01", {delay: 0});
		cy.get('form#individualSubscriptionForm input[id^="dateEnd-"]:visible').type((new Date().getFullYear()) + "-12-31", {delay: 0});
		cy.get('form#individualSubscriptionForm button:contains("Save")').click();
		cy.get('div:contains("Your changes have been saved.")');

		cy.logout();

		// See if the newly-subscribed user has a subscription
		cy.login('reader', null, 'publicknowledge');
		cy.visit('');
		cy.get('a:contains("Archives")').click();
		cy.get('a:contains("Vol. 1 No. 2 (2014)")').click();
		cy.get('a.obj_galley_link').should('not.have.class', 'restricted');
		cy.get('a.obj_galley_link:first').click();
		cy.get('iframe'); // The PDF viewer loads; we can't inspect within it, though.
	});
})
