/**
 * @file playwright/support/app.context.js
 *
 * OJS capability map + seeded-actor roster. Shared lib/pkp code gates on
 * these capabilities (never app names) and resolves personas through
 * seed.actors (archetype → seeded-username-or-null). Capability names are
 * canonical in lib/pkp/docs/e2e/specs/GLOSSARY.md Part II §2 — verbatim.
 */
const bootstrap = require('../fixtures/bootstrap.js');

module.exports = {
    appName: 'ojs',
    contextPath: 'publicknowledge',
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
    seed: {
        // archetype → seeded username (null where the app has no such role)
        actors: {
            admin: 'admin',
            manager: 'manager.maya',
            editor: 'editor.diana',
            sectionEditor: 'sectioneditor.ana',
            reviewer: 'reviewer.julia',
            copyeditor: 'copyeditor.carla',
            layoutEditor: 'layouteditor.leo',
            proofreader: 'proofreader.pia',
            assistant: 'assistant.rita',
            author: 'author.alex',
            reader: 'reader.rosa',
        },
        bootstrap,
    },
};
