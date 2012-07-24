<?php

/**
 * @file tests/functional/plugins/generic/lucene/FunctionalLucenePluginConfigTest.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalLucenePluginConfigTest
 * @ingroup tests_functional_plugins_generic_lucene
 * @see LucenePlugin
 *
 * @brief Integration/Functional test for the lucene plug-in
 * and its dependencies (configuration features).
 */


import('tests.functional.plugins.generic.lucene.FunctionalLucenePluginBaseTestCase');

class FunctionalLucenePluginConfigTest extends FunctionalLucenePluginBaseTestCase {

	//
	// Implement template methods from WebTestCase
	//
	/**
	 * @see WebTestCase::getAffectedTables()
	 */
	function getAffectedTables() {
		return array('plugin_settings');
	}


	/**
	 * SCENARIO: Plug-in disabled + solr server switched off
	 *   GIVEN The lucene plug-in is de-activated
	 *     AND the solr server is switched off
	 *    WHEN I execute a search
	 *    THEN I will see search results served by the OJS standard
	 *         search implementation.
	 *
	 * SCENARIO: Plug-in enabled + solr server switched off
	 *   GIVEN The lucene plug-in is de-activated
	 *     AND the solr server is switched off
	 *    WHEN I activate the lucene plug-in
	 *     AND I execute a search
	 *    THEN I will see an error message informing that the
	 *         solr server is not functioning.
	 *
	 * SCENARIO: Plug-in enabled + solr server switched on
	 *   GIVEN The lucene plug-in is activated
	 *     AND the solr server is switched off
	 *    WHEN I switch on the solr server
	 *     AND I execute a search
	 *    THEN I will see search results served by the solr server.
	 */


	/**
	 * SCENARIO OUTLINE: Settings form - valid entries
	 *   GIVEN I opened the lucene plug-in settings page
	 *    WHEN I change {setting} to a {valid value}
	 *     AND I hit the "Save" button
	 *    THEN these configuration parameters
	 *         will be saved to the database.
	 *
	 * EXAMPLES
	 *   setting                | valid value
	 *   ===============================================================
	 *   Search Endpoint URL    | http://search-server/solr/all-journals
	 *   Username               | adminuser
	 *   Password               | changed
	 *   Unique Installation ID | fqs
	 *
	 *
	 * SCENARIO OUTLINE: Settings form - valid entries
	 *   GIVEN I opened the lucene plug-in settings page
	 *    WHEN I change {setting} to an {invalid value}
	 *     AND I hit the "Save" button
	 *    THEN I will see an error message for the invalid
	 *         setting
	 *     AND the prior configuration paramters will
	 *         remain unchanged in the database.
	 *
	 * EXAMPLES
	 *   setting                | invalid value
	 *   ==========================================
	 *   Search Endpoint URL    | this-is-not-a-url // Must be a valid URL.
	 *   Username               | admin:user        // Colons are disallowed (HTTP-Basic authentication).
	 *   Password               |                   // Required value.
	 *   Unique Installation ID |                   // Required value.
	 *
	 * NB: We do not explicitly check the effect of the
	 * configuration changes as this is sufficiently
	 * checked in the other configuration/search scenarios
	 * which wouldn't work without a valid configuration.
	 * Checking configuration effect would unnecessarily
	 * bloat test code here.
	 */
}
?>