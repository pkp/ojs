<?php

/**
 * @file tests/functional/plugins/importexport/FunctionalMedraExportTest.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalMedraExportTest
 * @ingroup tests_functional_plugins_importexport_medra
 *
 * @brief Test the mEDRA plug-in.
 *
 * FEATURE: mEDRA DOI registration and export
 *   AS A    journal manager
 *   I WANT  to be able to register DOIs for issues, articles and
 *           supplementary files with the DOI registration agency mEDRA
 *   SO THAT these objects can be uniquely identified and
 *           discovered through public meta-data searches.
 */

/**
 * SCENARIO: Export serial issues as work.
 *
 *   GIVEN I navigate to the mEDRA plug-in's settings page
 *     AND I activate the default "export issues as work" option
 *     AND I navigate to any issue in the export plug-in
 *    WHEN I click the "Export" button
 *    THEN the issue will be exported as a O4DOI serial issue
 *         as work similarly to the example given in
 *         'serial-issue-as-work.xml'.
 */

/**
 * SCENARIO: Export serial issues as product/manifestation.
 *
 *   GIVEN I navigate to the mEDRA plug-in's settings page
 *     AND I activate the "export issues as manifestation" option
 *     AND I navigate to any issue in the export plug-in
 *    WHEN I click the "Export" button
 *    THEN the issue will be exported as a O4DOI serial issue
 *         as manifestation similarly to the example given in
 *         'serial-issue-as-manifestation.xml'.
 */

/**
 * SCENARIO: Export serial article as work.
 *
 *   GIVEN I assign a DOI to an OJS article
 *     AND I navigate to that article in the mEDRA export plug-in
 *    WHEN I click the "Export" button
 *    THEN the article will be exported as a O4DOI serial article
 *         as work similarly to the example given in
 *         'serial-article-as-work.xml'.
 */

/**
 * SCENARIO: Export serial article as product/manifestation.
 *
 *   GIVEN I assign a DOI to an OJS article galley
 *     AND I navigate to the corresponding article in the mEDRA
 *         export plug-in
 *    WHEN I click the "Export" button
 *    THEN all galleys of that article will be exported as O4DOI
 *         serial articles as manifestations similarly to the example
 *         given in 'serial-article-as-manifestation.xml'.
 */

/**
 * SCENARIO: Explain the work/product distinction
 *
 *    WHEN I navigate to the mEDRA export plug-in home page or any
 *         of the export pages
 *    THEN I'll see an explanatory text: "DOIs assigned to articles
 *         will be exported to mEDRA as 'works'. DOIs assigned to
 *         galleys will be exported as 'manifestations'."
 *     AND the words 'work' and 'manifestation' will link to
 *         <http://www.medra.org/en/metadata_td.htm>.
 */

/**
 * SCENARIO: Register all unregistered DOIs - part 1
 *
 *   GIVEN I navigate to the mEDRA export plug-in home page
 *    WHEN I click the "register all unregistered DOIs" button
 *    THEN a list of all unregistered objects will be compiled and
 *         displayed for confirmation
 *     AND the user will be presented with a "Confirm Export" button.
 *
 * SCENARIO: Register all unregistered DOIs - part 2
 *
 *   GIVEN I am presented with a list of unregistered objects after
 *         having clicked the "register all unregistered DOIs" button
 *    WHEN I click the "Confirm Export" button
 *    THEN all DOIs of issues, articles and galleys on that list
 *         will be automatically registered with mEDRA as new objects.
 */

/**
 * SCENARIO: Register specific issues/articles.
 *
 *   GIVEN I assign a DOI to an OJS object that has not been
 *         registered with mEDRA before
 *     AND I navigate to the corresponding object in the mEDRA
 *         export plug-in
 *    WHEN I click the "Register" button
 *    THEN the DOIs of the selected object will be automatically
 *         registered with mEDRA as a new object.
 */

/**
 * SCENARIO: Update specific issues/articles - part 1.
 *
 *    WHEN I navigate to an object in the mEDRA export plug-in
 *         that has already been transmitted to mEDRA
 *    THEN there will be an "Update" rather than a "Register" button
 *
 * SCENARIO: Update specific issues/articles - part 2.
 *
 *   GIVEN I navigate to an object in the mEDRA export plug-in
 *         that has already been transmitted to mEDRA
 *     AND the DOI has not changed
 *    WHEN I click the "Update" button
 *    THEN the meta-data of the selected object will be automatically
 *         registered with mEDRA as updated versions of a previously
 *         transmitted object.
 *
 * SCENARIO: Update specific issues/articles - part 3.
 *
 *   GIVEN I navigate to an object in the mEDRA export plug-in
 *         that has already been transmitted to mEDRA
 *     AND the DOI for the object has changed since its first registration
 *    WHEN I click the "Update" button
 *    THEN the new DOI will be automatically registered with mEDRA
 *         as a new object with a relation to the object identified
 *         by the previous DOI.
 */
?>