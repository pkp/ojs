<?php

/**
 * @file tests/functional/plugins/lucene/FunctionalLucenePluginConfigTest.php
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

	/**
	 * SCENARIO: Plug-in de-activated + solr server switched off
	 *   GIVEN The lucene plug-in is de-activated
	 *     AND the solr server is switched off
	 *    WHEN I execute a search
	 *    THEN I will see search results served by the OJS standard
	 *         search implementation.
	 *
	 * SCENARIO: Plug-in activated + solr server switched off
	 *   GIVEN The lucene plug-in is de-activated
	 *     AND the solr server is switched off
	 *    WHEN I activate the lucene plug-in
	 *     AND I execute a search
	 *    THEN I will see an error message informing that the
	 *         solr server is not functioning.
	 *
	 * SCENARIO: Plug-in activated + solr server switched on
	 *   GIVEN The lucene plug-in is activated
	 *     AND the solr server is switched off
	 *    WHEN I switch on the solr server
	 *     AND I execute a search
	 *    THEN I will see search results served by the solr server.
	 */
}
?>