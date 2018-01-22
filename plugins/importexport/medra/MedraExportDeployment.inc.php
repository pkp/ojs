<?php
/**
 * @defgroup plugins_importexport_medra mEDRA export plugin
 */

/**
 * @file plugins/importexport/medra/MedraExportDeployment.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MedraExportDeployment
 * @ingroup plugins_importexport_medra
 *
 * @brief Base class configuring the medra export process to an
 * application's specifics.
 */

// XML attributes
define('MEDRA_XMLNS' , 'http://www.editeur.org/onix/DOIMetadata/2.0');
define('MEDRA_XMLNS_XSI' , 'http://www.w3.org/2001/XMLSchema-instance');
define('MEDRA_XSI_SCHEMAVERSION' , '2.0');
define('MEDRA_XSI_SCHEMALOCATION' , 'http://www.medra.org/schema/onix/DOIMetadata/2.0/ONIX_DOIMetadata_2.0.xsd');

class MedraExportDeployment {
	/** @var Context The current import/export context */
	var $_context;

	/** @var Plugin The current import/export plugin */
	var $_plugin;

	/**
	 * Get the plugin cache
	 * @return PubObjectCache
	 */
	function getCache() {
		return $this->_plugin->getCache();
	}

	/**
	 * Constructor
	 * @param $context Context
	 * @param $plugin DOIPubIdExportPlugin
	 */
	function __construct($context, $plugin) {
		$this->setContext($context);
		$this->setPlugin($plugin);
	}

	//
	// Deployment items for subclasses to override
	//
	/**
	 * Get the namespace URN
	 * @return string
	 */
	function getNamespace() {
		return MEDRA_XMLNS;
	}

	/**
	 * Get the schema instance URN
	 * @return string
	 */
	function getXmlSchemaInstance() {
		return MEDRA_XMLNS_XSI;
	}

	/**
	 * Get the schema version
	 * @return string
	 */
	function getXmlSchemaVersion() {
		return MEDRA_XSI_SCHEMAVERSION;
	}

	/**
	 * Get the schema location URL
	 * @return string
	 */
	function getXmlSchemaLocation() {
		return MEDRA_XSI_SCHEMALOCATION;
	}

	/**
	 * Get the schema filename.
	 * @return string
	 */
	function getSchemaFilename() {
		return $this->getXmlSchemaLocation();
	}

	//
	// Getter/setters
	//
	/**
	 * Set the import/export context.
	 * @param $context Context
	 */
	function setContext($context) {
		$this->_context = $context;
	}

	/**
	 * Get the import/export context.
	 * @return Context
	 */
	function getContext() {
		return $this->_context;
	}

	/**
	 * Set the import/export plugin.
	 * @param $plugin ImportExportPlugin
	 */
	function setPlugin($plugin) {
		$this->_plugin = $plugin;
	}

	/**
	 * Get the import/export plugin.
	 * @return ImportExportPlugin
	 */
	function getPlugin() {
		return $this->_plugin;
	}

}

?>
