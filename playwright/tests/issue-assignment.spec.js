// @ts-check
const {test, expect} = require('../support/fixtures.js');
const {EditorialWorkflowPage} = require('../pages/EditorialWorkflowPage.js');
const submissionPublished = require('../fixtures/scenarios/submission-published.js');

/**
 * Issue assignment — row #30 in docs/e2e-playwright-migration.md.
 *
 * Graduated from DEFERRED. The original deferral note flagged a publication-
 * status race in the Issue side-modal save: saving the Issue panel flips the
 * publication to STATUS_READY_TO_PUBLISH, which changes which modal opens
 * when the editor next clicks Publish (skips the Review Publishing Details
 * step and goes straight to the publishModal). The simpler reassign path
 * sidestepped here is to unpublish first (which flips the status back to
 * STATUS_QUEUED) and then re-publish via `EditorialWorkflowPage#publishCurrentPanel`,
 * picking the new issue inside the Review Publishing Details side-modal —
 * the same code path the POM already exercises for first-time publish.
 *
 * Scope:
 *   - One round-trip test: editor unpublishes a published article, re-publishes
 *     it to a different published issue. Anonymous reader confirms the article
 *     appears in the new issue's TOC and is no longer in the old one.
 *
 *   - Drop the Cypress source's "unassign" arm. After OJS introduced the
 *     IssueAssignment enum (NO_ISSUE / FUTURE_PUBLISHED / FUTURE_SCHEDULED /
 *     CURRENT_BACK_PUBLISHED), unassigning maps to the NO_ISSUE option —
 *     i.e. "Don't Assign To An Issue", which sets issueId=null and STATUS_READY_TO_PUBLISH.
 *     Asserting that round-trip needs an extra modal save + a different reader
 *     surface (continuous-publishing list, not an issue TOC), which is its
 *     own row's worth of work. The capability gate this row needs — a
 *     published article moves between issues and the public TOCs reflect the
 *     move — is fully exercised by the move arm.
 *
 *   - Drop driving the standalone Issue-tab side-modal save. That path is
 *     what the original deferral note pointed at; the unpublish + re-publish
 *     route covers the same UI invariants (issue dropdown, status flip,
 *     publication's issueId persistence) without the modal-stacking race.
 *     If a future row needs the in-place issue swap, factor an
 *     `editIssuePanel()` helper into the POM at that point.
 *
 * E0 scratch journal — the bootstrap publicknowledge journal only seeds
 * one published issue (Vol. 1 No. 2 (2014)); a clean reassign-between-
 * published-issues test needs at least two. We create a scratch journal
 * with two published issues + one future issue (so the IssueAssignment
 * enum exposes both relevant options) and seed `submissionPublished` to
 * the first published issue inside that scratch journal.
 */

const SCRATCH_ISSUES = {
	source: {volume: 1, number: '1', year: 2026},
	target: {volume: 7, number: '2', year: 2026},
};

test.describe('Issue assignment', () => {
	test(
		'editor reassigns a published article between issues; reader TOCs reflect the move',
		{tag: '@regression'},
		async ({pkpApi, asUser, browser, baseURL}) => {
			const tag = uniqueTag(test.info(), 'reassign');

			// Two published issues + dbarnes as manager. IssueProcessor's
			// `published: true` branch promotes each issue in order via
			// Repo::issue::updateCurrent, so the second seeded issue ends
			// up as the journal's current — that's our reassignment target.
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
				issues: [
					{...SCRATCH_ISSUES.source, published: true},
					{...SCRATCH_ISSUES.target, published: true},
				],
			});

			// Seed the published submission against the source issue inside
			// the scratch journal. Override `journal` and `issue` so the
			// scenario's PublicationsProcessor resolves the right context
			// and looks up the right issue id.
			const spec = submissionPublished({tag});
			spec.journal = context.path;
			spec.publications[0].issue = {...SCRATCH_ISSUES.source};
			spec.publications[0].metadata.title.en = 'Reassign article';
			// PublicationsProcessor appends ` [${tag}]` to every title locale
			// for parallel isolation (see PublicationsProcessor.php#108-110),
			// so the rendered article title is "Reassign article [tag]".
			const articleTitle = `Reassign article [${tag}]`;
			const {submission} = await pkpApi.createSubmission(spec);

			// --- Editor: unpublish + republish to the target issue ----------
			const editorCtx = await asUser('dbarnes');
			const editorPage = await editorCtx.newPage();
			const workflow = new EditorialWorkflowPage(editorPage);

			// Resolve issue ids so the reader-side TOC URLs are deterministic.
			// The issues endpoint is gated on has.user — fetch through the
			// editor's authenticated request fixture, not an anonymous one.
			const issuesResp = await editorPage.request.get(
				`/index.php/${context.path}/api/v1/issues?count=100`,
			);
			expect(issuesResp.ok(), 'list issues').toBe(true);
			const issuesBody = await issuesResp.json();
			const issueItems = issuesBody.items || issuesBody;
			const sourceIssue = issueItems.find(
				(i) =>
					i.volume === SCRATCH_ISSUES.source.volume &&
					String(i.number) === SCRATCH_ISSUES.source.number &&
					i.year === SCRATCH_ISSUES.source.year,
			);
			const targetIssue = issueItems.find(
				(i) =>
					i.volume === SCRATCH_ISSUES.target.volume &&
					String(i.number) === SCRATCH_ISSUES.target.number &&
					i.year === SCRATCH_ISSUES.target.year,
			);
			expect(sourceIssue, 'source issue resolved').toBeTruthy();
			expect(targetIssue, 'target issue resolved').toBeTruthy();

			await editorPage.goto(
				`/index.php/${context.path}/en/dashboard/editorial?workflowSubmissionId=${submission.id}`,
			);

			// Unpublishing requires opening a publication sub-panel that
			// renders the Unpublish button — Title & Abstract is universal.
			// `WorkflowPublicationEditWarning` blocks editing fields on a
			// published publication, but the Unpublish action itself is
			// always available.
			await workflow.openPublicationPanel('Title & Abstract');
			await workflow.unpublishCurrentPanel();

			// After unpublish, status flips to STATUS_QUEUED. The publication
			// keeps versionStage=VoR but loses STATUS_READY_TO_PUBLISH, so the
			// next Publish click fully re-opens the Review Publishing Details
			// modal — the path `publishCurrentPanel` already drives.
			const beforeRepublish = await fetchFullPublication(
				editorPage,
				submission.id,
				context.path,
			);
			expect(beforeRepublish.status).toBe(STATUS_QUEUED);
			expect(beforeRepublish.issueId).toBe(sourceIssue.id);

			// Re-publish to the target issue. The POM picks the issue by
			// option label inside the Review Publishing Details side-modal.
			const targetLabel = `Vol. ${SCRATCH_ISSUES.target.volume} No. ${SCRATCH_ISSUES.target.number} (${SCRATCH_ISSUES.target.year})`;
			await workflow.publishCurrentPanel({issueLabel: targetLabel});

			// Confirm the publication moved to the target issue and is
			// published again — the load-bearing model invariant.
			const afterRepublish = await fetchFullPublication(
				editorPage,
				submission.id,
				context.path,
			);
			expect(afterRepublish.status).toBe(STATUS_PUBLISHED);
			expect(afterRepublish.issueId).toBe(targetIssue.id);

			// --- Reader: anonymous browser confirms the move ----------------
			await expectArticleInIssueToc({
				browser,
				baseURL,
				journalPath: context.path,
				issueId: targetIssue.id,
				articleTitle,
			});
			await expectArticleNotInIssueToc({
				browser,
				baseURL,
				journalPath: context.path,
				issueId: sourceIssue.id,
				articleTitle,
			});
		},
	);
});

// Publication status ints — see lib/pkp/classes/submission/PKPSubmission.php
// + classes/publication/Publication.php.
const STATUS_QUEUED = 1;
const STATUS_PUBLISHED = 3;

/**
 * Fetch the full publication object for a submission's current publication.
 * The summary returned by `…/publications` collection endpoint omits
 * `issueId` (only present on the full publication payload); we hit the
 * single-publication GET to assert the issue assignment moved.
 *
 * Always uses the journal path passed in — the shared
 * `EditorialWorkflowPage#fetchPublications` helper hard-codes
 * `publicknowledge`, which doesn't fit a scratch-journal spec.
 */
async function fetchFullPublication(page, submissionId, journalPath) {
	const subRes = await page.request.get(
		`/index.php/${journalPath}/api/v1/submissions/${submissionId}`,
	);
	if (!subRes.ok()) {
		throw new Error(
			`GET submission: ${subRes.status()} ${await subRes.text()}`,
		);
	}
	const subBody = await subRes.json();
	const publicationId = subBody.currentPublicationId;
	const pubRes = await page.request.get(
		`/index.php/${journalPath}/api/v1/submissions/${submissionId}/publications/${publicationId}`,
	);
	if (!pubRes.ok()) {
		throw new Error(
			`GET publication: ${pubRes.status()} ${await pubRes.text()}`,
		);
	}
	return pubRes.json();
}

/**
 * Anonymous reader visits the issue's public view page and asserts the
 * article title appears as a TOC link. The issue page renders one
 * `<a>` per published submission inside `.obj_article_summary`; matching
 * by visible text is enough for an OJS-only test.
 *
 * @param {{
 *   browser: import('@playwright/test').Browser,
 *   baseURL?: string,
 *   journalPath: string,
 *   issueId: number,
 *   articleTitle: string,
 * }} opts
 */
async function expectArticleInIssueToc({
	browser,
	baseURL,
	journalPath,
	issueId,
	articleTitle,
}) {
	const ctx = await browser.newContext({baseURL, reducedMotion: 'reduce'});
	try {
		const page = await ctx.newPage();
		const resp = await page.goto(
			`/index.php/${journalPath}/issue/view/${issueId}`,
		);
		expect(resp?.status()).toBe(200);
		await expect(
			page.locator('.obj_issue_toc').getByRole('link', {name: articleTitle}),
		).toBeVisible({timeout: 10_000});
	} finally {
		await ctx.close();
	}
}

/**
 * Anonymous reader visits the issue's public view page and asserts the
 * article title is NOT listed in its TOC. The page must still load (200);
 * the article just isn't there.
 */
async function expectArticleNotInIssueToc({
	browser,
	baseURL,
	journalPath,
	issueId,
	articleTitle,
}) {
	const ctx = await browser.newContext({baseURL, reducedMotion: 'reduce'});
	try {
		const page = await ctx.newPage();
		const resp = await page.goto(
			`/index.php/${journalPath}/issue/view/${issueId}`,
		);
		expect(resp?.status()).toBe(200);
		await expect(
			page.locator('.obj_issue_toc').getByRole('link', {name: articleTitle}),
		).toHaveCount(0);
	} finally {
		await ctx.close();
	}
}

/**
 * Build a worker-scoped tag so parallel runs don't collide.
 *
 * @param {import('@playwright/test').TestInfo} info
 * @param {string} suffix
 */
function uniqueTag(info, suffix) {
	const random = Math.random().toString(36).slice(2, 8);
	return `ia-w${info.parallelIndex}-${suffix}-${random}`;
}
