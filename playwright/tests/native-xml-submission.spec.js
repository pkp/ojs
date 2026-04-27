// @ts-check
const {test, expect} = require('../support/fixtures.js');
const submissionPublished = require('../fixtures/scenarios/submission-published.js');

/**
 * Native XML — submission export/import — row #53 in
 * docs/e2e-playwright-migration.md.
 *
 * Cypress source: lib/pkp/cypress/tests/integration/
 * NativeXmlImportExportSubmission.cy.js. Two Cypress tests drive
 *   1. the Tools → Native XML Plugin → Export Submissions tab — picks
 *      up to 2 submissions and submits the legacy `exportXmlForm`,
 *      then submits the "Download Exported File" form to download the
 *      serialized XML to disk.
 *   2. re-uploads the same XML through the Native plugin's import tab,
 *      drives the plupload widget to a temporaryFileId, and submits
 *      `importXmlForm` to commit the import.
 *
 * Spec lives at the OJS-root playwright/ tree because the seeded data
 * (`submissionPublished`) is journal/submission scoped — Native XML
 * submission export is OJS-only in shape (the omp/ops siblings have
 * their own rows). Approach mirrors playwright/tests/pubmed-metadata.spec.js
 * (row #37 — same plugin category, different output flow).
 *
 * Wave 7 marker — the original "needs E1" gating cell is lifted: every
 * seeded submission now ships with the default Article Text file
 * attached (Step 2 of the scenario-extensions plan), so the Native
 * exporter has real submission_files to round-trip through the
 * <article_galley><file>... pipeline.
 *
 * Native plugin export differs from the PubMed plugin (row #37) in
 * shape:
 *   - PubMed streams the XML inline as the response to
 *     `exportSubmissions` (downloadByPath() with response body = XML).
 *   - Native posts to `exportSubmissions`, writes the XML to a temp
 *     file on disk, and returns a JSONMessage `{status:true, content}`
 *     where `content` is the rendered `resultsExport.tpl` HTML
 *     fragment carrying two hidden inputs (`exportedFileDatePart` /
 *     `exportedFileContentNamePart`). A second POST to
 *     `downloadExportFile` with those two values streams the actual
 *     XML and deletes the temp file.
 *
 * So the spec drives the two-step flow as raw API requests rather than
 * the `page.waitForEvent('download')` UI route — `page.request.post`
 * captures the response body directly without engaging the browser's
 * download dispatcher.
 *
 * Scope vs. Cypress:
 *   - One test that round-trips export + reimport, plus a metadata
 *     assertion in between. The Cypress source's two-test split was a
 *     state-passing artifact (writing the file to disk between tests);
 *     in Playwright we keep the bytes in memory and avoid the cross-
 *     test coupling.
 *   - Drop the "first 2 submissions" multi-select arm. The exporter's
 *     filter is per-submission; one submission proves the pipeline
 *     wires up. Adding a second seed would only re-prove the loop.
 *   - Drop the cy.waitJQuery / hard-wait idioms — direct API requests
 *     don't race the plupload init the way the legacy fbv form did.
 */
test.describe('Native XML — submission export/import', () => {
	test('editor exports a submission as native XML and reimports it; reimport creates a new submission with matching metadata', async ({
		pkpApi,
		asUser,
	}) => {
		const tag = uniqueTag(test.info(), 'export');
		// submissionPublished appends `[${tag}]` to every title locale via
		// PublicationsProcessor (see
		// playwright/fixtures/scenarios/submission-published.js); the full
		// title written to disk is `Published article [${tag}]`. We
		// search the exported XML for that suffix to confirm the seeded
		// submission — not some sibling — landed in the response, and we
		// search post-import via the REST search endpoint for the same.
		const spec = submissionPublished({tag});
		const {submission} = await pkpApi.createSubmission(spec);

		const ctx = await asUser('dbarnes');
		const page = await ctx.newPage();

		// 1. Hit the plugin landing page to obtain a CSRF token. Mirrors
		//    pubmed-metadata.spec.js — `window.pkp.currentUser.csrfToken`
		//    is the canonical source the rest of the suite already uses.
		const landingResp = await page.goto(
			'/index.php/publicknowledge/en/management/importexport/plugin/NativeImportExportPlugin',
		);
		expect(landingResp?.status()).toBe(200);
		const csrfToken = await page.evaluate(
			() => window.pkp?.currentUser?.csrfToken,
		);
		expect(csrfToken, 'manager landing page must expose csrfToken').toBeTruthy();

		// 2. Two-step export. Step A: POST `exportSubmissions` with the
		//    seeded submission id. Response is a JSONMessage whose
		//    `content` carries the two hidden inputs naming the on-disk
		//    XML file.
		const exportResp = await page.request.post(
			'/index.php/publicknowledge/en/management/importexport/plugin/NativeImportExportPlugin/exportSubmissions',
			{
				headers: {'X-Csrf-Token': csrfToken},
				form: {
					'selectedSubmissions[]': String(submission.id),
				},
				timeout: 30_000,
			},
		);
		expect(exportResp.status()).toBe(200);
		const exportBody = await exportResp.json();
		expect(exportBody.status, 'export JSONMessage status').toBe(true);

		// Pluck the two hidden inputs from the rendered resultsExport.tpl
		// fragment. Both values are URL-safe ASCII (date format
		// `Ymd-His`, content name e.g. "submissions") so a forgiving
		// regex over the HTML is enough.
		const datePart = pickInputValue(
			exportBody.content,
			'exportedFileDatePart',
		);
		const contentNamePart = pickInputValue(
			exportBody.content,
			'exportedFileContentNamePart',
		);
		expect(
			datePart,
			'exportedFileDatePart hidden input present in export results',
		).toBeTruthy();
		expect(
			contentNamePart,
			'exportedFileContentNamePart hidden input present in export results',
		).toBe('submissions');

		// Step B: POST `downloadExportFile` — streams the actual XML
		// inline (FileManager::downloadByPath) and deletes the temp file
		// after. We need `maxRedirects: 0` so the response body is the
		// XML bytes; otherwise downstream content-type juggling can
		// re-route us.
		const downloadResp = await page.request.post(
			'/index.php/publicknowledge/en/management/importexport/plugin/NativeImportExportPlugin/downloadExportFile',
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

		// 3. Sanity — the XML carries the seeded title (with our tag) and
		//    the canonical native-xml wrapper element. Anchoring on the
		//    tag ensures we got *our* submission's bytes, not a sibling's.
		expect(xml).toMatch(/<\?xml/);
		expect(xml).toMatch(/<article\b[\s\S]+<\/article>/);
		expect(xml).toMatch(
			new RegExp(`<title[^>]*>[^<]*Published article[^<]*${escapeRegex(tag)}[^<]*</title>`),
		);

		// 4. Reimport. Step A: POST `uploadImportXML` as multipart with
		//    field name `uploadedFile` (TemporaryFileManager::handleUpload
		//    reads $_FILES['uploadedFile']). Returns
		//    {status:true, temporaryFileId:N}.
		const uploadResp = await page.request.post(
			'/index.php/publicknowledge/en/management/importexport/plugin/NativeImportExportPlugin/uploadImportXML',
			{
				headers: {'X-Csrf-Token': csrfToken},
				multipart: {
					uploadedFile: {
						name: `native-submission-${tag}.xml`,
						mimeType: 'text/xml',
						buffer: Buffer.from(xml, 'utf8'),
					},
				},
				timeout: 30_000,
			},
		);
		expect(uploadResp.status()).toBe(200);
		const uploadBody = await uploadResp.json();
		expect(uploadBody.status, 'upload JSONMessage status').toBe(true);
		const temporaryFileId = uploadBody.temporaryFileId;
		expect(
			temporaryFileId,
			'temporaryFileId returned from uploadImportXML',
		).toBeTruthy();

		// Step B: POST `import` with the temporaryFileId. The plugin
		// dispatches based on the XML root element ("article" /
		// "articles") and runs the native-xml=>article filter chain.
		// Returns a JSONMessage with the rendered resultsImport.tpl.
		// Note: this op explicitly calls `$request->checkCSRF()` which
		// reads the form var `csrfToken` (PKPRequest.php#431-433) — the
		// X-Csrf-Token header is enforced by the route middleware but
		// the inline check requires the form value too. Send both.
		const importResp = await page.request.post(
			'/index.php/publicknowledge/en/management/importexport/plugin/NativeImportExportPlugin/import',
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
		const importBody = await importResp.json();
		expect(importBody.status, 'import JSONMessage status').toBe(true);
		// resultsImport.tpl renders "The import process has been
		// completed successfully" (plugins.importexport.native.importComplete).
		// Surface as a soft signal — the strong signal is the
		// REST round-trip below.
		expect(importBody.content).not.toMatch(/processFailed/i);

		// 5. Verify a NEW submission was created with the same title.
		//    `searchPhrase=Published+article+[tag]` matches both the
		//    original and the freshly imported submission, so we expect
		//    >=2 hits. The original submission id is `submission.id`; the
		//    second item is the newly imported one — its id differs.
		const searchTerm = `Published article ${tag}`;
		const subsRes = await page.request.get(
			'/index.php/publicknowledge/api/v1/submissions',
			{
				params: {
					searchPhrase: searchTerm,
					count: 30,
				},
			},
		);
		expect(subsRes.ok()).toBe(true);
		const subsBody = await subsRes.json();
		const items = subsBody.items || [];
		// Filter to items whose current publication's title actually
		// matches our tag — `searchPhrase` is broad-match across many
		// fields, so we narrow on the structured payload.
		const matchingIds = items
			.filter((it) => {
				const pubs = it.publications || [];
				return pubs.some((p) => {
					const title = (p.fullTitle && (p.fullTitle.en || '')) || '';
					return title.includes(tag);
				});
			})
			.map((it) => it.id);
		expect(
			matchingIds.length,
			`expected >=2 submissions matching tag '${tag}' (1 seeded + 1 imported), got ${matchingIds.length}`,
		).toBeGreaterThanOrEqual(2);
		// Sanity — the original is still there, and at least one *other*
		// id (the imported submission) joins it.
		expect(matchingIds).toContain(submission.id);
		const importedIds = matchingIds.filter((id) => id !== submission.id);
		expect(
			importedIds.length,
			'imported submission has a fresh id distinct from the seed',
		).toBeGreaterThanOrEqual(1);
	});
});

/**
 * Pluck the value attribute of a named hidden input from an HTML
 * fragment. The Native plugin's resultsExport.tpl renders both
 * `exportedFileDatePart` and `exportedFileContentNamePart` as
 * `<input type="hidden" name="..." id="..." value="..." />`; we look
 * up by `name` to be robust against the per-fragment uuid suffix on
 * the id attribute.
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
 * don't collide on the shared submissions list.
 *
 * @param {import('@playwright/test').TestInfo} info
 * @param {string} suffix
 */
function uniqueTag(info, suffix) {
	const slug = info.title
		.toLowerCase()
		.replace(/[^a-z0-9]+/g, '-')
		.slice(0, 16);
	return `t-w${info.parallelIndex}-${suffix}-${slug}`;
}
