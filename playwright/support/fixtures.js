// @ts-check
/**
 * @file playwright/support/fixtures.js
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * The `test` OJS specs import. It is the shared base plus whatever is
 * OJS-specific — today only `ojsApi`, an alias that lets an OJS spec say what it
 * means without reaching into the shared layer's vocabulary.
 *
 * OJS specs:      const {test, expect} = require('../support/fixtures.js');
 * Shared specs:   const {test, expect} = require('../support/base-test.js');
 *
 * Feature fixtures (a submission factory that cleans up after itself, scenario
 * builders under playwright/fixtures/scenarios/) arrive with the features that
 * need them. Adding one here is only right when SEVERAL specs need the same
 * state; a one-off belongs in the spec that wants it.
 */

const base = require('../../lib/pkp/playwright/support/base-test.js');

const test = base.test.extend({
	/**
	 * The seeding API, under the name an OJS spec expects. Same client as
	 * `pkpApi`; the alias exists so OJS specs are not written against the shared
	 * layer's name and can grow OJS-only helpers here later.
	 */
	ojsApi: async ({pkpApi}, use) => {
		await use(pkpApi);
	},
});

module.exports = {test, expect: base.expect};
