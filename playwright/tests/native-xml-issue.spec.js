// @ts-check
const {test, expect} = require('../support/fixtures.js');
const submissionPublished = require('../fixtures/scenarios/submission-published.js');

/**
 * Native XML — issue export — row #54 in
 * docs/e2e-playwright-migration.md.
 *
 * Cypress source: cypress/tests/integration/Y_NativeXmlImportExportIssue.cy.js.
 * Two Cypress tests:
 *   1. Tools → Native XML Plugin → Export Issues tab — pick issues from
 *      ExportableIssuesListGridHandler, submit `exportIssuesXmlForm`,
 *      then submit "Download Exported File" to fetch the XML.
 *   2. re-upload through the import tab; assertions look for "Vol. X
 *      No. Y (year)" strings in the post-import results page.
 *
 * Spec lives at the OJS-root playwright/ tree because issues are
 * OJS-only.
 *
 * Wave 7 marker — the original "needs E1" gating cell is lifted: every
 * seeded submission ships with the default Article Text file attached,
 * so the issue exporter has real <article_galley><file> contents to
 * round-trip alongside the issue scaffolding.
 *
 * Approach mirrors row #53 (native-xml-submission.spec.js) — drive the
 * two-step JSON export flow as raw API requests instead of the
 * `page.waitForEvent('download')` UI route. The Native plugin's
 * `exportIssues` action writes the XML to a temp file and returns a
 * JSONMessage `{status:true, content}` whose HTML carries
 * `exportedFileDatePart` / `exportedFileContentNamePart` hidden
 * inputs; a follow-up POST to `downloadExportFile` streams the bytes.
 *
 * Scope:
 *   - **Export only** — reimport is deliberately out of scope. Issue
 *     reimport collides on issue path / volume / number / year (the
 *     Cypress source side-stepped this by patching `<year>` to
 *     `currentYear+1` before re-uploading), and the row's promised
 *     tests are minimal. Per the prompt's explicit "Skip reimport for
 *     #54" guidance, the export-only assertion is sufficient.
 *   - **One scratch journal per test** — seeds a fresh E0 journal with
 *     a published issue + one published submission assigned to it, so
 *     parallel workers don't trample the bootstrap publicknowledge
 *     journal's issue list. Mirrors subscription-access.spec.js
 *     (row #52)'s scratch-journal pattern.
 *   - **One submission per issue** — the Cypress source picked "first
 *     2 issues" because the bootstrap had 2; here we control the seed
 *     so a single issue + single submission proves the wiring. Adding
 *     a second submission would only re-prove the loop.
 */
test.describe('Native XML — issue export', () => {
	test('editor exports an issue as native XML; XML round-trips the issue identifier and the assigned submission title', async ({
		pkpApi,
		asUser,
	}) => {
		const tag = uniqueTag(test.info(), 'iss');

		// 1. Scratch journal with a single published issue. Path stays
		//    short (journals.path is varchar(32)) — "n-" + the
		//    worker-scoped tag.
		const journalPath = `n-${tag}`;
		await pkpApi.createJournal({
			tag,
			path: journalPath,
			name: {en: `Native XML Issue ${tag}`},
			users: [{username: 'dbarnes', roles: ['manager']}],
			issues: [
				{
					volume: 1,
					number: 1,
					year: 2026,
					published: true,
					showTitle: false,
				},
			],
		});

		// 2. Seed a published submission against the scratch journal,
		//    assigned to that issue. submission-published's `journal`
		//    override points the submission at the scratch journal's
		//    path; the `issue` override matches the seeded issue.
		const spec = submissionPublished({
			tag,
			journal: journalPath,
			issue: {volume: 1, number: 1, year: 2026},
		});
		const {submission} = await pkpApi.createSubmission(spec);

		const ctx = await asUser('dbarnes');
		const page = await ctx.newPage();

		// 3. Resolve the issue id via the OJS issues REST endpoint —
		//    /api/v1/issues supports `volumes` / `numbers` / `years`
		//    query params (IssueController#getMany). Filter on all three
		//    to pick the seeded issue out of the journal's listing.
		const issuesRes = await page.request.get(
			`/index.php/${journalPath}/api/v1/issues`,
			{
				params: {
					volumes: '1',
					numbers: '1',
					years: '2026',
				},
			},
		);
		expect(issuesRes.ok()).toBe(true);
		const issuesBody = await issuesRes.json();
		const issueItem = (issuesBody.items || []).find(
			(it) =>
				it.volume === 1 && it.number === '1' && Number(it.year) === 2026,
		);
		expect(issueItem, 'seeded issue resolves via /api/v1/issues').toBeTruthy();
		const issueId = issueItem.id;

		// 4. Hit the plugin landing page for our scratch journal to
		//    obtain a CSRF token. Same pattern as
		//    native-xml-submission.spec.js / pubmed-metadata.spec.js.
		const landingResp = await page.goto(
			`/index.php/${journalPath}/en/management/importexport/plugin/NativeImportExportPlugin`,
		);
		expect(landingResp?.status()).toBe(200);
		const csrfToken = await page.evaluate(
			() => window.pkp?.currentUser?.csrfToken,
		);
		expect(csrfToken, 'manager landing page must expose csrfToken').toBeTruthy();

		// 5. Two-step export. Step A: POST `exportIssues` with the
		//    seeded issue id. The OJS app override
		//    (NativeImportExportPlugin#display, case 'exportIssues')
		//    runs the issue=>native-xml filter chain and returns a
		//    JSONMessage with `exportedFileDatePart` /
		//    `exportedFileContentNamePart` hidden inputs.
		// Important: the URL omits the `/en/` locale segment. With it,
		// OJS's routing emits a 302 redirect to the locale-less URL,
		// which downgrades the POST to a GET and silently drops the
		// `selectedIssues[]` body — leading to an empty issue list and
		// a "Missing child element issue" XSD validation error.
		// Submission export tolerates the redirect for unrelated
		// reasons; issue export does not. Use the locale-less URL.
		const exportResp = await page.request.post(
			`/index.php/${journalPath}/management/importexport/plugin/NativeImportExportPlugin/exportIssues`,
			{
				headers: {'X-Csrf-Token': csrfToken},
				form: {
					'selectedIssues[]': String(issueId),
				},
				timeout: 30_000,
			},
		);
		expect(exportResp.status()).toBe(200);
		const exportBody = await exportResp.json();
		expect(exportBody.status, 'export JSONMessage status').toBe(true);

		const datePart = pickInputValue(
			exportBody.content,
			'exportedFileDatePart',
		);
		const contentNamePart = pickInputValue(
			exportBody.content,
			'exportedFileContentNamePart',
		);
		expect(datePart, 'exportedFileDatePart in export results').toBeTruthy();
		// Same hidden-input name across submissions/issues, but the
		// content-name part for issue export is "issues".
		expect(contentNamePart, 'exportedFileContentNamePart in export results').toBe(
			'issues',
		);

		// Step B: POST `downloadExportFile` to stream the actual XML
		// bytes (FileManager::downloadByPath). Same locale-segment
		// caveat as exportIssues — omit `/en/` so the POST doesn't
		// downgrade to a GET via the locale-redirect.
		const downloadResp = await page.request.post(
			`/index.php/${journalPath}/management/importexport/plugin/NativeImportExportPlugin/downloadExportFile`,
			{
				headers: {'X-Csrf-Token': csrfToken},
				form: {
					exportedFileDatePart: datePart,
					exportedFileContentNamePart: contentNamePart,
				},
				timeout: 30_000,
			},
		);
		expect(downloadResp.status()).toBe(200);
		const xml = await downloadResp.text();

		// 6. XML assertions. Native issue XML uses `<issue>` /
		//    `<volume>` / `<number>` / `<year>` elements (see
		//    NativeXmlIssueFilter / IssueNativeXmlFilter). The
		//    submission's title also lands in the embedded
		//    `<article>...<title>` chain since the publication is
		//    assigned to the issue and gets serialized inline.
		expect(xml).toMatch(/<\?xml/);
		expect(xml).toMatch(/<issue\b[\s\S]+<\/issue>/);
		// Issue identification — volume / number / year all present.
		expect(xml).toMatch(/<volume[^>]*>1<\/volume>/);
		expect(xml).toMatch(/<number[^>]*>1<\/number>/);
		expect(xml).toMatch(/<year[^>]*>2026<\/year>/);
		// At least one of the issue's submissions surfaces — anchor on
		// the unique tag suffix to confirm it's the submission we
		// seeded against this scratch journal, not a parallel worker's.
		expect(xml).toMatch(
			new RegExp(`<title[^>]*>[^<]*Published article[^<]*${escapeRegex(tag)}[^<]*</title>`),
		);
		// Sanity — keep the seeded submission id reachable in the
		// caller's context (lint guard against unused locals).
		expect(submission.id).toBeGreaterThan(0);
	});
});

/**
 * Pluck the value attribute of a named hidden input from an HTML
 * fragment. Used by the resultsExport.tpl scrape — same helper shape
 * as native-xml-submission.spec.js.
 *
 * @param {string} html
 * @param {string} name
 * @returns {string|null}
 */
function pickInputValue(html, name) {
	if (typeof html !== 'string') return null;
	const re = new RegExp(
		`<input\\b[^>]*\\bname=["']${escapeRegex(name)}["'][^>]*\\bvalue=["']([^"']*)["']`,
		'i',
	);
	const m = re.exec(html);
	return m ? m[1] : null;
}

/**
 * Escape a string for inclusion in a RegExp.
 *
 * @param {string} s
 */
function escapeRegex(s) {
	return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Build a tag scoped to this worker + test title so parallel workers
 * don't collide on the shared journals/submissions lists. Trim
 * aggressively — journals.path is varchar(32) and we prefix with "n-".
 *
 * @param {import('@playwright/test').TestInfo} info
 * @param {string} suffix
 */
function uniqueTag(info, suffix) {
	const slug = info.title
		.toLowerCase()
		.replace(/[^a-z0-9]+/g, '-')
		.slice(0, 5);
	const rand = Math.random().toString(36).slice(2, 6);
	return `w${info.parallelIndex}-${suffix}-${slug}-${rand}`;
}
