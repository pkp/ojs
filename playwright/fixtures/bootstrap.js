/**
 * @file playwright/fixtures/bootstrap.js
 *
 * Static seed data for the OJS base install — the declarative payload
 * bootstrap.setup.js POSTs to /api/v1/_test/bootstrap on a cold DB.
 *
 * The seeded journal and its 18 users are READ-ONLY for tests (PRINCIPLES
 * principle 1): no test may mutate journal-level settings, sections,
 * categories, issues, or seeded users. Tests needing journal-level mutations
 * create a scratch journal via the context scenario endpoint.
 *
 * A change here requires checking every implemented spec against the new
 * defaults — do it deliberately.
 */
const {getEmail} = require('../../lib/pkp/playwright/data/users.js');

const user = (username, givenName, familyName, roles, extra = {}) => ({
    username,
    givenName,
    familyName,
    email: getEmail(username),
    roles,
    ...extra,
});

module.exports = {
    context: {
        path: 'publicknowledge',
        name: {
            en: 'Journal of Public Knowledge',
            fr_CA: 'Journal de la connaissance du public',
        },
        acronym: {en: 'JPK'},
        primaryLocale: 'en',
        supportedLocales: ['en', 'fr_CA'],
    },
    sections: [
        {
            abbrev: 'ART',
            title: {en: 'Articles', fr_CA: 'Articles'},
            policy: {en: 'Section default policy'},
            wordCount: 500,
        },
        {
            abbrev: 'REV',
            title: {en: 'Reviews', fr_CA: 'Critiques'},
            abstractsNotRequired: true,
            identifyType: {en: 'Review Article'},
        },
    ],
    categories: [
        {
            path: 'applied-science',
            title: {en: 'Applied Science'},
            children: [
                {
                    path: 'comp-sci',
                    title: {en: 'Computer Science'},
                    children: [{path: 'computer-vision', title: {en: 'Computer Vision'}}],
                },
                {path: 'eng', title: {en: 'Engineering'}},
            ],
        },
        {
            path: 'social-sciences',
            title: {en: 'Social Sciences'},
            children: [
                {path: 'sociology', title: {en: 'Sociology'}},
                {path: 'anthropology', title: {en: 'Anthropology'}},
            ],
        },
    ],
    issues: [
        {volume: 1, number: '2', year: 2014, published: true},
        {volume: 2, number: '1', year: 2015, published: false},
    ],
    users: [
        user('manager.maya', 'Maya', 'Manager', ['manager']),
        // editor.diana is also a sub-editor of both sections (users.md)
        user('editor.diana', 'Diana', 'Editor', ['editor'], {sections: ['ART', 'REV']}),
        user('sectioneditor.ana', 'Ana', 'Section Editor', ['sectionEditor'], {sections: ['ART']}),
        user('sectioneditor.ravi', 'Ravi', 'Section Editor', ['sectionEditor'], {sections: ['REV']}),
        user('sectioneditor.omar', 'Omar', 'Section Editor', ['sectionEditor'], {sections: ['ART']}),
        user('reviewer.julia', 'Julia', 'Reviewer', ['externalReviewer']),
        user('reviewer.paul', 'Paul', 'Reviewer', ['externalReviewer']),
        user('reviewer.amara', 'Amara', 'Reviewer', ['externalReviewer']),
        user('reviewer.adam', 'Adam', 'Reviewer', ['externalReviewer']),
        user('copyeditor.carla', 'Carla', 'Copyeditor', ['copyeditor']),
        user('copyeditor.sam', 'Sam', 'Copyeditor', ['copyeditor']),
        user('layouteditor.leo', 'Leo', 'Layout Editor', ['layoutEditor']),
        user('proofreader.pia', 'Pia', 'Proofreader', ['proofreader']),
        user('author.alex', 'Alex', 'Author', ['author']),
        user('author.bea', 'Bea', 'Author', ['author']),
        // funding coordinator: the one default assistant group with
        // review-stage access (stages 1,3)
        user('assistant.rita', 'Rita', 'Assistant', ['funding']),
        user('reader.rosa', 'Rosa', 'Reader', ['reader']),
    ],
};
