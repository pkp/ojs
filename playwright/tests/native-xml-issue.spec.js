// @ts-check
const {test, expect} = require('../support/fixtures.js');
const submissionPublished = require('../fixtures/scenarios/submission-published.js');

/**
 * Native XML — issue export + reimport — row #54 in
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
 *   - **Export test** — single scratch journal seeded with issue +
 *     assigned submission; XML round-trips volume/number/year + the
 *     submission's tag-suffixed title.
 *   - **Reimport test** — exports from one scratch journal and reimports
 *     into a second, fresh scratch journal. The Cypress source patched
 *     `<year>` to `currentYear+1` to side-step issue path / volume /
 *     number / year collisions on the same journal; instead, two-journal
 *     setup avoids the collision entirely (the import filter binds
 *     issues to `$deployment->getContext()`, so a fresh journal has no
 *     conflicting issue rows). The XML carries no source-journal
 *     identifier outside `<id type="internal" advice="ignore">` — the
 *     "ignore" advice tells NativeXmlIssueFilter to mint a fresh row in
 *     the destination context.
 *   - **One submission per issue** — the Cypress source picked "first
 *     2 issues" because the bootstrap had 2; here we control the seed
 *     so a single issue + single submission proves the wiring. Adding
 *     a second submission would only re-prove the loop.
 */
test.describe('Native XML — issue export + reimport', () => {
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

		const xml = await exportIssueXml(page, journalPath, {
			volume: 1,
			number: 1,
			year: 2026,
		});

		// 3. XML assertions. Native issue XML uses `<issue>` /
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

	test('editor reimports an exported issue XML into a fresh journal; metadata round-trips', async ({
		pkpApi,
		asUser,
	}) => {
		// Two scratch journals: source (issue + submission) and target
		// (empty). Reimport collisions are issue path / volume / number /
		// year scoped to a journal — a fresh target journal has no rows
		// to collide against. The Cypress source side-stepped the
		// collision by patching `<year>` to `currentYear+1` and
		// re-uploading into the same journal; the two-journal pattern is
		// closer to the "publisher migrates issues between journals"
		// real-world use-case.
		const tag = uniqueTag(test.info(), 'rim');
		const sourcePath = `n-${tag}-s`;
		const targetPath = `n-${tag}-t`;

		await pkpApi.createJournal({
			tag: `${tag}-s`,
			path: sourcePath,
			name: {en: `Native XML Source ${tag}`},
			users: [{username: 'dbarnes', roles: ['manager']}],
			issues: [
				{
					volume: 3,
					number: 7,
					year: 2027,
					published: true,
					showTitle: false,
				},
			],
		});

		await pkpApi.createJournal({
			tag: `${tag}-t`,
			path: targetPath,
			name: {en: `Native XML Target ${tag}`},
			users: [{username: 'dbarnes', roles: ['manager']}],
			// No issues — keep the target's issue list empty so the
			// reimport lands without colliding on path/volume/number/year.
		});

		// Submission seeded into the source journal, assigned to the
		// seeded issue. The submission's title carries the unique tag,
		// which we re-find in the post-import target's submission list
		// to prove the round-trip wired up.
		const spec = submissionPublished({
			tag,
			journal: sourcePath,
			issue: {volume: 3, number: 7, year: 2027},
		});
		const {submission} = await pkpApi.createSubmission(spec);
		expect(submission.id).toBeGreaterThan(0);

		const ctx = await asUser('dbarnes');
		const page = await ctx.newPage();

		// Step 1 — export the source journal's issue. Reuses the same
		// helper the export-only test uses.
		const xml = await exportIssueXml(page, sourcePath, {
			volume: 3,
			number: 7,
			year: 2027,
		});
		expect(xml).toMatch(/<volume[^>]*>3<\/volume>/);
		expect(xml).toMatch(/<number[^>]*>7<\/number>/);
		expect(xml).toMatch(/<year[^>]*>2027<\/year>/);

		// Step 2 — import the bytes into the TARGET journal. Land on
		// the target's plugin landing page first to refresh CSRF /
		// session affinity for the new journal context.
		const targetLanding = await page.goto(
			`/index.php/${targetPath}/en/management/importexport/plugin/NativeImportExportPlugin`,
		);
		expect(targetLanding?.status()).toBe(200);
		const csrfToken = await page.evaluate(
			() => window.pkp?.currentUser?.csrfToken,
		);
		expect(csrfToken, 'target journal landing must expose csrfToken').toBeTruthy();

		// Step 2a — POST `uploadImportXML` against the target journal.
		// Locale-less URL — same caveat as exportIssues. On scratch
		// journals (E0) the dispatcher emits a 302 from the
		// /{journal}/en/... path to the locale-less /{journal}/...
		// path, which downgrades the POST to a GET and silently drops
		// the multipart body — `$_FILES['uploadedFile']` ends up unset
		// and TemporaryFileManager::handleUpload returns false with
		// `common.uploadFailed`. The submission spec gets away with
		// the locale prefix because it runs against the bootstrap
		// publicknowledge journal whose dispatcher cache is warmer
		// (the redirect doesn't fire on its first hit). Using the
		// locale-less URL is robust across both shapes.
		const uploadResp = await page.request.post(
			`/index.php/${targetPath}/management/importexport/plugin/NativeImportExportPlugin/uploadImportXML`,
			{
				headers: {'X-Csrf-Token': csrfToken},
				multipart: {
					uploadedFile: {
						name: `native-issue-${tag}.xml`,
						mimeType: 'text/xml',
						buffer: Buffer.from(xml, 'utf8'),
					},
				},
				timeout: 30_000,
			},
		);
		expect(uploadResp.status()).toBe(200);
		const uploadBodyText = await uploadResp.text();
		let uploadBody;
		try {
			uploadBody = JSON.parse(uploadBodyText);
		} catch {
			throw new Error(
				`uploadImportXML returned non-JSON body: ${uploadBodyText.slice(0, 200)}`,
			);
		}
		expect(
			uploadBody.status,
			`upload JSONMessage status; body=${uploadBodyText.slice(0, 400)}`,
		).toBe(true);
		const temporaryFileId = uploadBody.temporaryFileId;
		expect(
			temporaryFileId,
			'temporaryFileId returned from uploadImportXML',
		).toBeTruthy();

		// Step 2b — POST `import` to commit. Issue XML root element is
		// `<issue>` / `<issues>` so PKPNativeImportExportPlugin routes
		// to the `native-xml=>issue` filter automatically. Send both
		// the X-Csrf-Token header AND the `csrfToken` form var: the
		// `import` op explicitly calls `$request->checkCSRF()` which
		// reads the form var (PKPRequest.php#431-433), and the route
		// middleware separately enforces the header.
		// Locale-less URL — same caveat as exportIssues; the locale
		// segment provokes a 302 that downgrades the POST to a GET and
		// silently drops the body.
		const importResp = await page.request.post(
			`/index.php/${targetPath}/management/importexport/plugin/NativeImportExportPlugin/import`,
			{
				headers: {'X-Csrf-Token': csrfToken},
				form: {
					temporaryFileId: String(temporaryFileId),
					csrfToken,
				},
				timeout: 60_000,
			},
		);
		expect(importResp.status()).toBe(200);
		// Importer returns rendered HTML (resultsImport.tpl) directly —
		// not JSONMessage. Negative signal: `processFailed` translation
		// key surfaces on errors. Positive signals checked via REST below.
		const importContent = await importResp.text();
		expect(importContent).not.toMatch(/processFailed/i);

		// Step 3 — verify a new issue lives in the target journal with
		// matching volume/number/year. The OJS issues REST endpoint
		// `/api/v1/issues` supports `volumes`/`numbers`/`years` query
		// filters (IssueController#getMany).
		const issuesRes = await page.request.get(
			`/index.php/${targetPath}/api/v1/issues`,
			{
				params: {
					volumes: '3',
					numbers: '7',
					years: '2027',
				},
			},
		);
		expect(issuesRes.ok()).toBe(true);
		const issuesBody = await issuesRes.json();
		const importedIssue = (issuesBody.items || []).find(
			(it) =>
				it.volume === 3 && it.number === '7' && Number(it.year) === 2027,
		);
		expect(
			importedIssue,
			'imported issue surfaces in target journal /api/v1/issues',
		).toBeTruthy();

		// Step 4 — verify at least one submission with the seeded title
		// rode along into the target journal. `submissionPublished`
		// titles publications as `Published article [${tag}]`; we
		// search by the tag alone (most selective single-token phrase
		// per submissions.searchPhrase tokenisation rules) and confirm
		// at least one match has the tag in the publication title.
		const subsRes = await page.request.get(
			`/index.php/${targetPath}/api/v1/submissions`,
			{
				params: {
					searchPhrase: tag,
					count: 30,
				},
			},
		);
		expect(subsRes.ok()).toBe(true);
		const subsBody = await subsRes.json();
		const matching = (subsBody.items || []).filter((it) => {
			const pubs = it.publications || [];
			return pubs.some((p) => {
				const title = (p.fullTitle && (p.fullTitle.en || '')) || '';
				return title.includes(tag);
			});
		});
		expect(
			matching.length,
			`expected >=1 submission in target journal matching tag '${tag}', got ${matching.length}`,
		).toBeGreaterThanOrEqual(1);
	});
});

/**
 * Drive the Native plugin's two-step JSON export of a single issue from
 * the named journal and return the raw XML bytes. Caller is responsible
 * for being authenticated as a manager on `journalPath`. Mirrors the
 * inline export in the export-only test; factored out so the reimport
 * test can reuse the exact same flow without copy-paste.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} journalPath
 * @param {{volume: number, number: number, year: number}} issueRef
 * @returns {Promise<string>} the exported XML as a UTF-8 string
 */
async function exportIssueXml(page, journalPath, {volume, number, year}) {
	// Resolve the issue id via the OJS issues REST endpoint —
	// /api/v1/issues supports `volumes` / `numbers` / `years` query
	// params (IssueController#getMany).
	const issuesRes = await page.request.get(
		`/index.php/${journalPath}/api/v1/issues`,
		{
			params: {
				volumes: String(volume),
				numbers: String(number),
				years: String(year),
			},
		},
	);
	expect(issuesRes.ok()).toBe(true);
	const issuesBody = await issuesRes.json();
	const issueItem = (issuesBody.items || []).find(
		(it) =>
			it.volume === volume &&
			it.number === String(number) &&
			Number(it.year) === year,
	);
	expect(
		issueItem,
		`seeded issue (vol ${volume} no ${number} year ${year}) resolves via /api/v1/issues`,
	).toBeTruthy();
	const issueId = issueItem.id;

	// CSRF — the manager landing page exposes
	// `window.pkp.currentUser.csrfToken`.
	const landingResp = await page.goto(
		`/index.php/${journalPath}/en/management/importexport/plugin/NativeImportExportPlugin`,
	);
	expect(landingResp?.status()).toBe(200);
	const csrfToken = await page.evaluate(
		() => window.pkp?.currentUser?.csrfToken,
	);
	expect(csrfToken, 'manager landing page must expose csrfToken').toBeTruthy();

	// Step A — POST `exportIssues` (locale-less URL; see spec header).
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
	const datePart = pickInputValue(exportBody.content, 'exportedFileDatePart');
	const contentNamePart = pickInputValue(
		exportBody.content,
		'exportedFileContentNamePart',
	);
	expect(datePart, 'exportedFileDatePart in export results').toBeTruthy();
	expect(
		contentNamePart,
		'exportedFileContentNamePart in export results',
	).toBe('issues');

	// Step B — POST `downloadExportFile` to stream the bytes.
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
	return await downloadResp.text();
}

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
