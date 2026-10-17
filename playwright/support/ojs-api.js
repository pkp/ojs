// @ts-check

/**
 * OJS-only API helpers. Mirrors the OJS-specific subset of the existing
 * Cypress lib/pkp/cypress/support/api.js (issues, sections, subscriptions)
 * plus the submission factory used by the `submission` fixture in
 * playwright/support/fixtures.js.
 *
 * Stub — method bodies fill in during spec-by-spec migration.
 */
exports.createOjsApi = function createOjsApi({request, baseURL}) {
	return {
		request,
		baseURL,

		async createSubmission(/* data */) {
			throw new Error('TODO: POST /api/v1/submissions');
		},

		async deleteSubmission(/* id */) {
			throw new Error('TODO: DELETE /api/v1/submissions/:id');
		},

		async createIssue(/* data */) {
			throw new Error('TODO: POST /api/v1/issues');
		},

		async createSection(/* data */) {
			throw new Error('TODO: POST /api/v1/sections');
		},
	};
};
