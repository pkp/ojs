const { defineConfig } = require('cypress')

module.exports = defineConfig({
  env: {
    contextTitles: {
      en: 'Journal of Public Knowledge',
      fr_CA: 'Journal de la connaissance du public',
    },
    contextDescriptions: {
      en:
        'The Journal of Public Knowledge is a peer-reviewed quarterly publication on the subject of public access to science.',
      fr_CA:
        "Le Journal de Public Knowledge est une publication trimestrielle évaluée par les pairs sur le thème de l'accès du public à la science.",
    },
    contextAcronyms: {
      en: 'JPK',
    },
    defaultGenre: 'Article Text',
    authorUserGroupId: 14,
    translatorUserGroupId: 15,
    dataAvailabilityTest: {
      submission: {
        title: 'Sodium butyrate improves growth performance of weaned piglets during the first period after weaning',
        authorFamilyName: 'Christopher'
      },
      anonymousReviewer: 'phudson',
      anonymousDisclosedReviewer: 'jjanssen'
    }
  },
  watchForFileChanges: false,
  defaultCommandTimeout: 10000,
  pageLoadTimeout: 120000,
  video: false,
  numTestsKeptInMemory: 0,
  e2e: {
    // We've imported your old cypress plugins here.
    // You may want to clean this up later by importing these.
    setupNodeEvents(on, config) {
      return require('./lib/pkp/cypress/plugins/index.js')(on, config)
    },
    specPattern: [
      'cypress/tests/data/**/*.cy.{js,jsx,ts,tsx}',
      'cypress/tests/integration/**/*.cy.{js,jsx,ts,tsx}',
      'lib/pkp/cypress/tests/**/*.cy.{js,jsx,ts,tsx}',
    ],
    redirectionLimit: 1000,
    experimentalRunAllSpecs: true,
  },
  // Allow cypress to interact with iframes
  chromeWebSecurity: false
})
