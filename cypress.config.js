const { defineConfig } = require('cypress')

module.exports = defineConfig({
  env: {
    contextTitles: {
      en_US: 'Journal of Public Knowledge',
      fr_CA: 'Journal de la connaissance du public',
    },
    contextDescriptions: {
      en_US:
        'The Journal of Public Knowledge is a peer-reviewed quarterly publication on the subject of public access to science.',
      fr_CA:
        "Le Journal de Public Knowledge est une publication trimestrielle évaluée par les pairs sur le thème de l'accès du public à la science.",
    },
    contextAcronyms: {
      en_US: 'JPK',
    },
    defaultGenre: 'Article Text',
  },
  watchForFileChanges: false,
  defaultCommandTimeout: 10000,
  video: false,
  numTestsKeptInMemory: 0,

  e2e: {

    setupNodeEvents(on, config) {
      return require('./lib/pkp/cypress/plugins/index.js')(on, config)
    },
    specPattern: 'cypress/tests/**/*.cy.{js,jsx,ts,tsx}',
	  experimentalSessionAndOrigin: true,
	  chromeWebSecurity: false,
  },
})
