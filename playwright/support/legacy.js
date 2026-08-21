/**
 * @file playwright/support/legacy.js
 *
 * Helpers for the legacy jQuery-driven surfaces (grids, AjaxModals, the
 * file-upload wizard). APP-LOCAL: the shared lib/pkp/playwright layer does not
 * ship a jQuery helper yet; if a second app suite needs this, promote it via a
 * shared-layer proposal (do not edit lib/pkp from a feature session).
 */

/**
 * Wait until jQuery has no in-flight AJAX requests. The Playwright counterpart
 * of Cypress's cy.waitJQuery(); call it after interacting with legacy
 * jQuery-driven UI (AjaxModal saves, grid refreshes). No-op on pages without
 * jQuery.
 *
 * @param {import('@playwright/test').Page} page
 */
async function waitForJQueryIdle(page) {
    await page.waitForFunction(() => !window.jQuery || window.jQuery.active === 0);
}

module.exports = {waitForJQueryIdle};
