<?php

/**
 * @file classes/context/Context.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Context
 * @ingroup core
 *
 * @brief Basic class describing a context.
 */

class Context extends DataObject {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Get the localized name of the context
	 * @param $preferredLocale string
	 * @return string
	 */
	function getLocalizedName($preferredLocale = null) {
		return $this->getLocalizedSetting('name', $preferredLocale);
	}

	/**
	 * Set the name of the context
	 * @param $name string
	 */
	function setName($name, $locale = null) {
		$this->setData('name', $name, $locale);
	}

	/**
	 * get the name of the context
	 */
	function getName($locale = null) {
		return $this->getSetting('name', $locale);
	}

	/**
	 * Get the contact name for this context
	 * @return string
	 */
	function getContactName() {
		return $this->getSetting('contactName');
	}

	/**
	 * Set the contact name for this context
	 * @param $contactName string
	 */
	function setContactName($contactName) {
		$this->setData('contactName', $contactName);
	}

	/**
	 * Get the contact email for this context
	 * @return string
	 */
	function getContactEmail() {
		return $this->getSetting('contactEmail');
	}

	/**
	 * Set the contact email for this context
	 * @param $contactEmail string
	 */
	function setContactEmail($contactEmail) {
		$this->setData('contactEmail', $contactEmail);
	}

	/**
	 * Get context description.
	 * @param $description string optional
	 * @return string
	 */
	function getDescription($locale = null) {
		return $this->getData('description', $locale);
	}

	/**
	 * Set context description.
	 * @param $description string
	 * @param $locale string optional
	 */
	function setDescription($description, $locale = null) {
		$this->setData('description', $description, $locale);
	}

	/**
	 * Get path to context (in URL).
	 * @return string
	 */
	function getPath() {
		return $this->getData('path');
	}

	/**
	 * Set path to context (in URL).
	 * @param $path string
	 */
	function setPath($path) {
		$this->setData('path', $path);
	}

	/**
	 * Get enabled flag of context
	 * @return int
	 */
	function getEnabled() {
		return $this->getData('enabled');
	}

	/**
	 * Set enabled flag of context
	 * @param $enabled int
	 */
	function setEnabled($enabled) {
		$this->setData('enabled', $enabled);
	}

	/**
	 * Return the primary locale of this context.
	 * @return string
	 */
	function getPrimaryLocale() {
		return $this->getData('primaryLocale');
	}

	/**
	 * Set the primary locale of this context.
	 * @param $locale string
	 */
	function setPrimaryLocale($primaryLocale) {
		$this->setData('primaryLocale', $primaryLocale);
	}
	/**
	 * Get sequence of context in site-wide list.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence of context in site table of contents.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		$this->setData('sequence', $sequence);
	}

	/**
	 * Get the localized description of the context.
	 * @return string
	 */
	function getLocalizedDescription() {
		return $this->getLocalizedSetting('description');
	}

	/**
	 * Get localized acronym of context
	 * @return string
	 */
	function getLocalizedAcronym() {
		return $this->getLocalizedSetting('acronym');
	}

	/**
	 * Get the acronym of the context.
	 * @param $locale string
	 * @return string
	 */
	function getAcronym($locale) {
		return $this->getSetting('acronym', $locale);
	}

	/**
	 * Get the supported form locales.
	 * @return array
	 */
	function getSupportedFormLocales() {
		return $this->getSetting('supportedFormLocales');
	}

	/**
	 * Return associative array of all locales supported by forms on the site.
	 * These locales are used to provide a language toggle on the main site pages.
	 * @return array
	 */
	function getSupportedFormLocaleNames() {
		$supportedLocales =& $this->getData('supportedFormLocaleNames');

		if (!isset($supportedLocales)) {
			$supportedLocales = array();
			$localeNames =& AppLocale::getAllLocales();

			$locales = $this->getSupportedFormLocales();
			if (!isset($locales) || !is_array($locales)) {
				$locales = array();
			}

			foreach ($locales as $localeKey) {
				$supportedLocales[$localeKey] = $localeNames[$localeKey];
			}
		}

		return $supportedLocales;
	}

	/**
	 * Get the supported submission locales.
	 * @return array
	 */
	function getSupportedSubmissionLocales() {
		return $this->getSetting('supportedSubmissionLocales');
	}

	/**
	 * Return associative array of all locales supported by submissions on the
	 * site. These locales are used to provide a language toggle on the main
	 * site pages.
	 * @return array
	 */
	function getSupportedSubmissionLocaleNames() {
		$supportedLocales =& $this->getData('supportedSubmissionLocaleNames');

		if (!isset($supportedLocales)) {
			$supportedLocales = array();
			$localeNames =& AppLocale::getAllLocales();

			$locales = $this->getSupportedSubmissionLocales();
			if (!isset($locales) || !is_array($locales)) {
				$locales = array();
			}

			foreach ($locales as $localeKey) {
				$supportedLocales[$localeKey] = $localeNames[$localeKey];
			}
		}

		return $supportedLocales;
	}

	/**
	 * Get the supported locales.
	 * @return array
	 */
	function getSupportedLocales() {
		return $this->getSetting('supportedLocales');
	}

	/**
	 * Return associative array of all locales supported by the site.
	 * These locales are used to provide a language toggle on the main site pages.
	 * @return array
	 */
	function getSupportedLocaleNames() {
		$supportedLocales =& $this->getData('supportedLocaleNames');

		if (!isset($supportedLocales)) {
			$supportedLocales = array();
			$localeNames =& AppLocale::getAllLocales();

			$locales = $this->getSupportedLocales();
			if (!isset($locales) || !is_array($locales)) {
				$locales = array();
			}

			foreach ($locales as $localeKey) {
				$supportedLocales[$localeKey] = $localeNames[$localeKey];
			}
		}

		return $supportedLocales;
	}

	/**
	 * Get the association type for this context.
	 * @return int
	 */
	function getAssocType() {
		assert(false); // Must be overridden by subclasses
	}

	/**
	 * Get the settings DAO for this context object.
	 * @return DAO
	 */
	static function getSettingsDAO() {
		assert(false); // Must be implemented by subclasses
	}

	/**
	 * Retrieve array of settings.
	 * @return array
	 */
	function &getSettings() {
		$settingsDao = $this->getSettingsDAO();
		$settings =& $settingsDao->getSettings($this->getId());
		return $settings;
	}

	/**
	 * Retrieve a context setting value.
	 * @param $name string
	 * @param $locale string
	 * @return mixed
	 */
	function &getSetting($name, $locale = null) {
		$settingsDao = $this->getSettingsDAO();
		$setting =& $settingsDao->getSetting($this->getId(), $name, $locale);
		return $setting;
	}

	/**
	 * Update a context setting value.
	 * @param $name string
	 * @param $value mixed
	 * @param $type string optional
	 * @param $isLocalized boolean optional
	 */
	function updateSetting($name, $value, $type = null, $isLocalized = false) {
		$settingsDao = $this->getSettingsDAO();
		return $settingsDao->updateSetting($this->getId(), $name, $value, $type, $isLocalized);
	}

	/**
	 * Get a localized context setting by name.
	 * @param $name string
	 * @return mixed
	 */
	function &getLocalizedSetting($name) {
		$returner = $this->getSetting($name, AppLocale::getLocale());
		if ($returner === null) {
			$returner = $this->getSetting($name, AppLocale::getPrimaryLocale());
		}
		return $returner;
	}

	/**
	 * Get context main page views.
	 * @return int
	 */
	function getViews() {
		$application = Application::getApplication();
		return $application->getPrimaryMetricByAssoc(Application::getContextAssocType(), $this->getId());
	}


	//
	// Statistics API
	//
	/**
	* Return all metric types supported by this context.
	*
	* @return array An array of strings of supported metric type identifiers.
	*/
	function getMetricTypes($withDisplayNames = false) {
		// Retrieve report plugins enabled for this journal.
		$reportPlugins =& PluginRegistry::loadCategory('reports', true, $this->getId());
		if (!is_array($reportPlugins)) return array();

		// Run through all report plugins and retrieve all supported metrics.
		$metricTypes = array();
		foreach ($reportPlugins as $reportPlugin) {
			$pluginMetricTypes = $reportPlugin->getMetricTypes();
			if ($withDisplayNames) {
				foreach ($pluginMetricTypes as $metricType) {
					$metricTypes[$metricType] = $reportPlugin->getMetricDisplayType($metricType);
				}
			} else {
				$metricTypes = array_merge($metricTypes, $pluginMetricTypes);
			}
		}

		return $metricTypes;
	}

	/**
	* Returns the currently configured default metric type for this context.
	* If no specific metric type has been set for this context then the
	* site-wide default metric type will be returned.
	*
	* @return null|string A metric type identifier or null if no default metric
	*   type could be identified.
	*/
	function getDefaultMetricType() {
		$defaultMetricType = $this->getSetting('defaultMetricType');

		// Check whether the selected metric type is valid.
		$availableMetrics = $this->getMetricTypes();
		if (empty($defaultMetricType)) {
			if (count($availableMetrics) === 1) {
				// If there is only a single available metric then use it.
				$defaultMetricType = $availableMetrics[0];
			} else {
				// Use the site-wide default metric.
				$application = PKPApplication::getApplication();
				$defaultMetricType = $application->getDefaultMetricType();
			}
		} else {
			if (!in_array($defaultMetricType, $availableMetrics)) return null;
		}

		return $defaultMetricType;
	}

	/**
	* Retrieve a statistics report pre-filtered on this context.
	*
	* @see <http://pkp.sfu.ca/wiki/index.php/OJSdeStatisticsConcept#Input_and_Output_Formats_.28Aggregation.2C_Filters.2C_Metrics_Data.29>
	* for a full specification of the input and output format of this method.
	*
	* @param $metricType null|integer|array metrics selection
	* @param $columns integer|array column (aggregation level) selection
	* @param $filters array report-level filter selection
	* @param $orderBy array order criteria
	* @param $range null|DBResultRange paging specification
	*
	* @return null|array The selected data as a simple tabular
	*  result set or null if metrics are not supported by this context.
	*/
	function getMetrics($metricType = null, $columns = array(), $filter = array(), $orderBy = array(), $range = null) {
		// Add a context filter and run the report.
		$filter[STATISTICS_DIMENSION_CONTEXT_ID] = $this->getId();
		$application = PKPApplication::getApplication();
		return $application->getMetrics($metricType, $columns, $filter, $orderBy, $range);
	}
}

?>
