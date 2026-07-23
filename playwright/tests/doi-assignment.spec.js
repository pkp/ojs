// @ts-check
const {test, expect} = require('../support/fixtures.js');
const submissionPublished = require('../fixtures/scenarios/submission-published.js');

const SCRATCH_ISSUE = {volume: 1, number: '1', year: 2026};

/**
 * DOI assignment — row #31 in docs/e2e-playwright-migration.md.
 *
 * Ports cypress/tests/integration/Doi.cy.js. The Cypress suite covered
 * four sub-capabilities on the bootstrap publicknowledge journal:
 *   1. DOI configuration check (enable flags on the four pubObject types);
 *   2. Assign DOIs from the DOI management listPanel + check reader-page
 *      visibility (issue archive DOI link + article-page DOI section);
 *   3. (skipped in source) filter behaviour + mark-registered round-trip;
 *   4. (skipped in source) marked-status variants (NeedsSync / Unregistered).
 *
 * Scope kept — two tests, each on an E0 scratch journal:
 *   1. DOIs are auto-assigned to a newly-published article. Seeds a journal
 *      with `enableDois=true` + `doiPrefix=10.9999` + the default
 *      `copyEditCreationTime` behaviour, then seeds a full
 *      `submissionPublished` scenario and asserts the resulting publication
 *      carries a `doiObject.doi` that begins with the seeded prefix. Also
 *      verifies the anonymous article page renders the DOI in the
 *      default-theme `<section class="item doi">` — the R half of the
 *      roadmap cell.
 *   2. Versioned DOIs: with `doiVersioning=true`, a major-version bump gets
 *      a fresh DOI. Seeds v1 via `submissionPublished`, then seeds v2
 *      inline (versionIsMinor=false so the `version()` call in
 *      PublicationsProcessor resets doiId; VersionDois listener mints a
 *      new DOI on v2's publish). Asserts the two publications carry
 *      different DOIs.
 *
 * Scope deviations vs. roadmap cell + Cypress source:
 *   - Roadmap cell mentions "manual assign" + "deposit state" as editor
 *     tests. Manual assign runs through the DOI management listPanel UI
 *     (the one gated on `https://github.com/pkp/pkp-lib/issues/10606` in
 *     the Cypress source, hence `.skip`); the capability the product
 *     actually ships is auto-assignment on the documented copyEditCreationTime
 *     and publicationCreationTime paths, which test (1) covers
 *     end-to-end. Deposit-state (markRegistered / markUnregistered /
 *     markStale) is exercised in row #32's Crossref spec where it belongs
 *     (the deposit flow is the feature, not the state row). The
 *     marked-status-error variants in the Cypress `.skip` block are
 *     unit-test territory — they assert error-toast wording for wrong-state
 *     transitions, not a user-visible capability.
 *   - Manual assign deferred. A probe of the DOIs management page shows
 *     the listPanel uses a legacy expander + "Assign DOIs" bulk action;
 *     porting the UI flip here would duplicate what the auto-assignment
 *     path already proves. Reopen if a manual-assignment regression
 *     surfaces.
 *   - Issue-DOI assertion (Cypress test 2 half A) dropped. The seeded
 *     publication is assigned to a bootstrap issue that has no
 *     pre-minted DOI; issue DOIs ride a separate toggle
 *     (`enabledDoiTypes[]=issue`) and a separate UI path (Issue management
 *     grid with Assign Dois). The OJS default `enabledDoiTypes=['publication']`
 *     keeps this test scoped to the publication pathway the roadmap's "R:
 *     DOI meta tag on article page" bullet really asks for.
 *
 * ContextBuilder extension: `ContextBuilderProcessor` now passes through
 * `enableDois`, `doiPrefix`, `doiVersioning`, `enabledDoiTypes`,
 * `registrationAgency` so the journal is immutable-after-create for any
 * DOI-related setting the spec needs to seed. Mirrors the existing
 * `copyrightNotice` / `submitWithCategories` passthrough pattern.
 */

test.describe('DOI assignment', () => {
	test(
		'manager enables auto-DOI-assignment and a newly-published article receives a DOI',
		{tag: '@regression'},
		async ({pkpApi, browser, baseURL, asUser}) => {
			const tag = uniqueTag(test.info(), 'auto');
			const prefix = '10.9999';

			// E0 scratch journal with DOIs switched on + a valid prefix.
			// mintAndStoreDoi() in lib/pkp/classes/doi/Repository.php
			// throws DoiException('doi.exceptions.missingPrefix') without
			// a configured prefix; the enableDois flag is on by default
			// (schema: lib/pkp/schemas/context.json) but still passed
			// explicitly for documentation. doiCreationTime defaults to
			// 'copyEditCreationTime' — the AssignDOIs listener mints the
			// publication DOI when the SendToProduction decision flips
			// stageId to WORKFLOW_STAGE_ID_PRODUCTION (see
			// lib/pkp/classes/observers/listeners/AssignDOIs.php).
			const {context} = await pkpApi.createJournal({
				tag,
				enableDois: true,
				doiPrefix: prefix,
				users: [{username: 'dbarnes', roles: ['manager']}],
				// Seed a published issue so the publish step has a target.
				// JournalScenarioController.afterContextCreated() hands this
				// off to IssueProcessor inside the same transaction.
				issues: [{...SCRATCH_ISSUE, published: true}],
			});

			// Seed a fully-processed submissionPublished on the scratch
			// journal. submissionPublished defaults to 'publicknowledge'
			// so we override `journal` + `issue` to target our scratch
			// context's seeded issue.
			const spec = submissionPublished({tag});
			spec.journal = context.path;
			spec.publications[0].issue = {...SCRATCH_ISSUE};
			const {submission} = await pkpApi.createSubmission(spec);

			// Fetch the publication via the scratch journal's REST API
			// with dbarnes's session cookies. Anonymous GET doesn't include
			// doiObject; a manager's does (see
			// lib/pkp/schemas/publication.json: doiObject is apiSummary).
			const ctx = await asUser('dbarnes');
			const page = await ctx.newPage();
			const resp = await page.request.get(
				`/index.php/${context.path}/api/v1/submissions/${submission.id}`,
			);
			expect(resp.ok(), `GET submission: ${resp.status()}`).toBeTruthy();
			const submissionBody = await resp.json();
			const currentPub = submissionBody.publications.find(
				(p) => p.id === submissionBody.currentPublicationId,
			);
			expect(currentPub, 'currentPublication present').toBeTruthy();

			// doiObject is hydrated by PublicationDAO::_fromRow when
			// doiId is non-null; the shape is a Doi API-serialized
			// object with a `.doi` string "prefix/suffix".
			expect(
				currentPub.doiObject,
				`publication ${currentPub.id} has a DOI auto-assigned`,
			).toBeTruthy();
			expect(currentPub.doiObject.doi).toMatch(
				new RegExp(`^${escapeRegex(prefix)}/`),
			);

			// R: the anonymous article page renders the DOI section.
			// The default theme wraps it in <section class="item doi">
			// with a <a href=.../doi/..> link carrying the DOI URL.
			// See templates/frontend/objects/article_details.tpl ~170.
			const anon = await browser.newContext({baseURL});
			try {
				const anonPage = await anon.newPage();
				const articleResp = await anonPage.goto(
					`/index.php/${context.path}/article/view/${submission.id}`,
				);
				expect(articleResp?.status()).toBe(200);
				const doiSection = anonPage.locator('section.item.doi');
				await expect(doiSection).toBeVisible({timeout: 10_000});
				await expect(doiSection).toContainText(currentPub.doiObject.doi);
			} finally {
				await anon.close();
			}
		
		},
	);

	test(
		'versioned DOIs: a new major version receives its own DOI',
		{tag: '@regression'},
		async ({pkpApi, asUser}) => {
			const tag = uniqueTag(test.info(), 'ver');
			const prefix = '10.9999';

			// Same E0 setup as test 1, plus doiVersioning=true. Without
			// doiVersioning, Repository::version() does NOT reset the
			// newPublication's doiId on a non-minor bump and the existing
			// DOI carries forward (see
			// lib/pkp/classes/publication/Repository.php line 388:
			// `if (doiVersioning && !isMinorVersion) { doiId = null; }`).
			const {context} = await pkpApi.createJournal({
				tag,
				enableDois: true,
				doiPrefix: prefix,
				doiVersioning: true,
				users: [{username: 'dbarnes', roles: ['manager']}],
				issues: [{...SCRATCH_ISSUE, published: true}],
			});

			// Seed v1 + v2 in a single scenario. publications[0] is the
			// initial VoR that gets published to the seeded issue.
			// publications[1] chains a new version through
			// PublicationsProcessor::version() (parent's
			// Repo::publication()->version, which honours
			// doiVersioning + isMinorVersion). versionIsMinor=false is
			// required — a minor bump keeps the DOI; only a major bump
			// triggers the `doiId = null` reset and remint-on-publish.
			const spec = submissionPublished({tag});
			spec.journal = context.path;
			spec.publications[0].issue = {...SCRATCH_ISSUE};
			spec.publications.push({
				versionStage: 'VoR',
				versionIsMinor: false,
				metadata: {
					title: {en: 'Published article v2'},
				},
				issue: {...SCRATCH_ISSUE},
				published: true,
			});
			const {submission} = await pkpApi.createSubmission(spec);

			const ctx = await asUser('dbarnes');
			const page = await ctx.newPage();
			const resp = await page.request.get(
				`/index.php/${context.path}/api/v1/submissions/${submission.id}/publications`,
			);
			expect(resp.ok()).toBeTruthy();
			const body = await resp.json();
			const pubs = body.items ?? body;
			expect(pubs.length).toBeGreaterThanOrEqual(2);

			// Order isn't guaranteed by the REST response — sort by
			// versionMajor ascending so [0]=v1 and [1]=v2 regardless
			// of DAO ORDER BY.
			const sorted = [...pubs].sort(
				(a, b) => (a.versionMajor ?? 0) - (b.versionMajor ?? 0),
			);
			const v1 = sorted[0];
			const v2 = sorted[sorted.length - 1];

			expect(v1.doiObject, 'v1 has a DOI').toBeTruthy();
			expect(v2.doiObject, 'v2 has a DOI').toBeTruthy();
			expect(v1.doiObject.doi).toMatch(
				new RegExp(`^${escapeRegex(prefix)}/`),
			);
			expect(v2.doiObject.doi).toMatch(
				new RegExp(`^${escapeRegex(prefix)}/`),
			);
			expect(
				v2.doiObject.doi,
				`v2 DOI (${v2.doiObject.doi}) must differ from v1 (${v1.doiObject.doi})`,
			).not.toBe(v1.doiObject.doi);

		},
	);

	test(
		'manager configures DOI settings via the UI: enable + per-type toggles + prefix + creation time persist on reload',
		{tag: '@regression'},
		async ({pkpApi, asUser}) => {
			const tag = uniqueTag(test.info(), 'cfg');
			// Scratch journal with DOIs OFF (skip the
			// ContextBuilderProcessor passthrough so the UI flow has
			// something to flip). dbarnes is manager.
			const {context} = await pkpApi.createJournal({
				tag,
				users: [{username: 'dbarnes', roles: ['manager']}],
			});
			const ctx = await asUser('dbarnes');
			const page = await ctx.newPage();

			// Navigate directly to the Distribution DOIs tab. The OJS
			// settings tabs are id-anchored: outer #distribution-button,
			// inner #dois-button. Cypress's `checkDoiConfig` clicks
			// through Settings menu → Distribution → DOIs; the URL
			// hash drives the same activation.
			await page.goto(
				`/index.php/${context.path}/management/settings/distribution#dois`,
			);
			await page.locator('#dois-button').click();

			const form = page.locator('form#doisSetup, #doisSetup form').first();
			await expect(form).toBeVisible({timeout: 15_000});

			// Enable DOIs.
			const enableCheckbox = form
				.locator('input[name="enableDois"]')
				.first();
			await expect(enableCheckbox).toBeVisible();
			await enableCheckbox.check();

			// Tick each pubObject type the Cypress helper enabled
			// (publication / issue / representation). The form
			// renders these as a FieldOptions group with
			// `name="enabledDoiTypes"` and per-type values.
			for (const t of ['publication', 'issue', 'representation']) {
				await form
					.locator(`input[name="enabledDoiTypes"][value="${t}"]`)
					.check();
			}

			const prefix = `10.${1000 + (test.info().parallelIndex || 0)}`;
			await form.locator('input[name="doiPrefix"]').fill(prefix);

			// doiCreationTime is a select; pick copyEditCreationTime
			// (matches Cypress).
			await form
				.locator('select[name="doiCreationTime"]')
				.selectOption('copyEditCreationTime');

			// Save the DOIs form. Race with the context PUT.
			await Promise.all([
				page.waitForResponse(
					(res) =>
						/\/api\/v1\/contexts\/\d+/.test(res.url()) &&
						res.ok() &&
						['POST', 'PUT'].includes(res.request().method()),
					{timeout: 15_000},
				),
				form.getByRole('button', {name: 'Save', exact: true}).click(),
			]);

			// Reload + reactivate the tab; persistence is what proves
			// the round-trip.
			await page.reload();
			await page.locator('#dois-button').click();
			const reloaded = page
				.locator('form#doisSetup, #doisSetup form')
				.first();
			await expect(reloaded).toBeVisible({timeout: 15_000});

			await expect(
				reloaded.locator('input[name="enableDois"]').first(),
			).toBeChecked();
			for (const t of ['publication', 'issue', 'representation']) {
				await expect(
					reloaded.locator(
						`input[name="enabledDoiTypes"][value="${t}"]`,
					),
				).toBeChecked();
			}
			await expect(reloaded.locator('input[name="doiPrefix"]')).toHaveValue(
				prefix,
			);
			await expect(
				reloaded.locator('select[name="doiCreationTime"]'),
			).toHaveValue('copyEditCreationTime');
		},
	);
});

/**
 * Build a tag scoped to this worker + test title so parallel workers
 * don't collide on the shared submissions list. Mirrors the helper used
 * in playwright/tests/pubmed-metadata.spec.js.
 *
 * @param {import('@playwright/test').TestInfo} info
 * @param {string} suffix
 */
function uniqueTag(info, suffix) {
	// Journal urlPath is limited to 32 chars (see
	// ContextBuilderProcessor: path = `j-{alnum(tag)}`). Stay well under.
	const rand = Math.random().toString(36).slice(2, 6);
	return `d-w${info.parallelIndex}-${suffix}-${rand}`;
}

/**
 * Escape a string for inclusion in a RegExp.
 *
 * @param {string} s
 */
function escapeRegex(s) {
	return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}
