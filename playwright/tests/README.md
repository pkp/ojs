# OJS Playwright specs

One spec file (or a small set) per feature, named after the feature, **flat** —
no subfolder taxonomy until ~25–30 specs exist and the natural groupings are
obvious, at which point clusters emerge as one refactor commit.

Specs here import `../support/fixtures.js`. The app-agnostic infrastructure
(base fixtures, shared POMs, the bootstrap seed and the login smoke) lives in
`lib/pkp/playwright/` and is shared with OMP and OPS; a feature suite belongs
here even when the scenario it implements is common to all three apps.
