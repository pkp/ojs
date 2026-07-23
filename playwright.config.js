// @ts-check

// OJS Playwright config. All logic lives in the shared factory in lib/pkp
// so OJS/OMP/OPS stay in sync; this stub just declares the app name.
module.exports = require('./lib/pkp/playwright/config-factory.js')({
	app: 'ojs',
});
