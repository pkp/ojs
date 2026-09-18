// @ts-check
/**
 * @file playwright/support/app.context.js
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * What the shared `lib/pkp/playwright` layer is allowed to know about OJS.
 *
 * Shared code gates on CAPABILITIES and resolves people through ARCHETYPES; it
 * never asks which app it is running in. OMP and OPS ship the same three keys
 * with their own values, so a shared spec written once runs in all three fleets
 * and skips itself where the capability does not hold.
 *
 * The capability names are canonical in `lib/pkp/docs/product/APP-GLOSSARY.md`
 * §2 and are spelled here VERBATIM from that table's OJS column. Adding a
 * capability means adding a glossary row first, then the same key in all three
 * app contexts.
 */

const {bootstrapPayload} = require('../fixtures/bootstrap.js');

const appContext = {
	app: 'ojs',

	/** APP-GLOSSARY.md §2, OJS column. */
	capabilities: {
		hasReviewStage: true,
		hasInternalReview: false,
		hasCopyediting: true,
		hasProduction: true,
		hasIssues: true,
		hasGalleys: true,
		hasSubscriptions: true,
		hasSections: true,
		hasReviewerRoles: true,
	},

	/**
	 * APP-GLOSSARY.md §1, OJS column. Vocabulary never gates anything — it is
	 * what a shared spec puts in a label or a payload so the same test reads
	 * correctly in each app.
	 */
	vocab: {
		context: 'journal',
		contextPlural: 'journals',
		submission: 'article',
		sectionGrouping: 'section',
		issue: 'issue',
		galley: 'galley',
		publishAction: 'Publish',
	},

	seed: {
		/** The shared base context every fleet seeds at the same url path. */
		contextPath: 'publicknowledge',
		contextName: 'Journal of Public Knowledge',
		primaryLocale: 'en',
		supportedLocales: ['en', 'fr_CA'],

		/**
		 * Archetype → seeded username, or null where OJS has no such account.
		 *
		 * Shared code asks for `actors.reviewer`, not for `'reviewer.julia'`: the
		 * archetype exists in every app's vocabulary even when the account does
		 * not (OPS has no reviewer group, and its map says so with null). An app's
		 * OWN suite may name its usernames directly.
		 */
		actors: {
			siteAdmin: 'admin',
			manager: 'manager.maya',
			editor: 'editor.diana',
			sectionEditor: 'sectioneditor.ana',
			sectionEditor2: 'sectioneditor.ravi',
			sectionEditor3: 'sectioneditor.omar',
			reviewer: 'reviewer.julia',
			reviewer2: 'reviewer.paul',
			reviewer3: 'reviewer.amara',
			reviewer4: 'reviewer.adam',
			copyeditor: 'copyeditor.carla',
			copyeditor2: 'copyeditor.sam',
			layoutEditor: 'layouteditor.leo',
			proofreader: 'proofreader.pia',
			author: 'author.alex',
			author2: 'author.bea',
			assistant: 'assistant.rita',
			reader: 'reader.rosa',
			subscriptionManager: null,
		},

		/** Section abbreviations the base seed creates. */
		sections: ['ART', 'REV'],
	},

	/** The base seed, as data. See playwright/fixtures/bootstrap.js. */
	bootstrapPayload,
};

module.exports = {appContext};
