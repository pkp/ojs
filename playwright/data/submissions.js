// @ts-check

/**
 * Static OJS test data — submission titles, abstracts, authors used
 * across specs. Keeps specs free of string literals and gives migrators
 * a single place to adjust canonical sample data.
 */
exports.submissions = {
	smoke: {
		title: 'Playwright smoke submission',
		abstract: 'Short abstract used by the smoke spec.',
		author: {
			givenName: 'Smoke',
			familyName: 'Test',
			email: 'smoke@mailinator.com',
		},
	},
};
