<?php

/**
 * @defgroup tests_plugins_metadata Metadata Plugin Tests
 */

/**
 * @file tests/plugins/metadata/MetadataPluginTestCase.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetadataPluginTestCase
 * @ingroup tests_plugins_metadata
 * @see MetadataPlugin
 *
 * @brief Abstract base class for MetadataPlugin tests.
 */

import('lib.pkp.tests.plugins.PluginTestCase');
import('lib.pkp.classes.plugins.MetadataPlugin');

class MetadataPluginTestCase extends PluginTestCase {
	/**
	 * @copydoc DatabaseTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		$affectedTables = parent::getAffectedTables();
		return array_merge(
			$affectedTables,
			array('controlled_vocabs', 'controlled_vocab_entries', 'controlled_vocab_entry_settings')
		);
	}

	/**
	 * Executes the metadata plug-in test.
	 * @param $pluginDir string
	 * @param $pluginName string
	 * @param $filterGroups array
	 * @param $controlledVocabs array
	 */
	protected function executeMetadataPluginTest($pluginDir, $pluginName, $filterGroups, $controlledVocabs) {
		// Make sure that the vocab xml configuration is valid.
		$controlledVocabFile = 'plugins/metadata/'.$pluginDir.'/schema/'.METADATA_PLUGIN_VOCAB_DATAFILE;
		$this->validateXmlConfig(array('./'.$controlledVocabFile, './lib/pkp/'.$controlledVocabFile));

		// Delete vocab data so that we can re-install it.
		$controlledVocabDao = DAORegistry::getDAO('ControlledVocabDAO'); /* @var $controlledVocabDao ControlledVocabDAO */
		foreach($controlledVocabs as $controlledVocabSymbolic) {
			$controlledVocab = $controlledVocabDao->getBySymbolic($controlledVocabSymbolic, 0, 0);
			if ($controlledVocab) $controlledVocabDao->deleteObject($controlledVocab);
		}

		// Unregister the plug-in so that we're sure it will be registered again.
		$plugins =& PluginRegistry::getPlugins();
		unset($plugins['metadata'][$pluginName]);

		$this->executePluginTest('metadata', $pluginDir, $pluginName, $filterGroups);

		// Test whether the controlled vocabs have been installed.
		foreach($controlledVocabs as $controlledVocab) {
			self::assertInstanceOf('ControlledVocab', $controlledVocabDao->getBySymbolic($controlledVocab, 0, 0));
		}
	}
}
?>
