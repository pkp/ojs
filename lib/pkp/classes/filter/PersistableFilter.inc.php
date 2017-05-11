<?php
/**
 * @file classes/filter/PersistableFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PersistableFilter
 * @ingroup filter
 *
 * @see FilterGroup
 * @see FilterSetting
 *
 * @brief A filter that can be persisted to the database.
 *
 * Persisted filters are attributed to a filter group so that all filters
 * of the same kind can be discovered from the database.
 *
 * Persisted filters also can provide a list of FilterSetting objects which
 * represent persistable filter configuration parameters.
 *
 * Persisted filters are templatable. This means that a non-parameterized
 * copy of the filter can be persisted as a template for actual filter
 * instances. The end user can discover such templates and use them to
 * configure personalized transformations.
 *
 * The filter template can provide default settings for all filter
 * settings.
 *
 * Persistable filters can be accessed via the FilterDAO which acts as a
 * filter registry.
 *
 * Filters can be organized hierarchically into filter networks or
 * filter pipelines. The hierarchical relation is represented via parent-
 * child relationships. See CompositeFilter for more details.
 */

import('lib.pkp.classes.filter.Filter');
import('lib.pkp.classes.filter.FilterGroup');

define('FILTER_GROUP_TEMPORARY_ONLY', '$$$temporary$$$');

class PersistableFilter extends Filter {
	/** @var FilterGroup */
	var $_filterGroup;

	/** @var array a list of FilterSetting objects */
	var $_settings = array();

	/**
	 * Constructor
	 *
	 * NB: Sub-classes of this class must not add additional
	 * mandatory constructor arguments. Sub-classes that implement
	 * additional optional constructor arguments must make these
	 * also accessible via setters if they are required to fully
	 * parameterize the transformation. Filter parameters must be
	 * stored as data in the underlying DataObject.
	 *
	 * This is necessary as the FilterDAO does not support
	 * constructor configuration. Filter parameters will be
	 * configured via DataObject::setData(). Only parameters
	 * that are available in the DataObject will be persisted.
	 *
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		// Check and set the filter group.
		assert(is_a($filterGroup, 'FilterGroup'));
		$this->_filterGroup = $filterGroup;

		// Initialize the filter.
		$this->setParentFilterId(0);
		$this->setIsTemplate(false);
		parent::__construct($filterGroup->getInputType(), $filterGroup->getOutputType());
	}


	//
	// Setters and Getters
	//
	/**
	 * Get the filter group
	 * @return FilterGroup
	 */
	function getFilterGroup() {
		return $this->_filterGroup;
	}

	/**
	 * Set whether this is a transformation template
	 * rather than an actual transformation.
	 *
	 * Transformation templates are saved to the database
	 * when the filter is first registered. They are
	 * configured with default settings and will be used
	 * to let users identify available transformation
	 * types.
	 *
	 * There must be exactly one transformation template
	 * for each supported filter group.
	 *
	 * @param $isTemplate boolean
	 */
	function setIsTemplate($isTemplate) {
		$this->setData('isTemplate', (boolean)$isTemplate);
	}

	/**
	 * Is this a transformation template rather than
	 * an actual transformation?
	 * @return boolean
	 */
	function getIsTemplate() {
		return $this->getData('isTemplate');
	}

	/**
	 * Set the parent filter id
	 * @param $parentFilterId integer
	 */
	function setParentFilterId($parentFilterId) {
		$this->setData('parentFilterId', $parentFilterId);
	}

	/**
	 * Get the parent filter id
	 * @return integer
	 */
	function getParentFilterId() {
		return $this->getData('parentFilterId');
	}

	/**
	 * Add a filter setting
	 * @param $setting FilterSetting
	 */
	function addSetting($setting) {
		assert(is_a($setting, 'FilterSetting'));
		$settingName = $setting->getName();

		// Check that the setting name does not
		// collide with one of the internal settings.
		if (in_array($settingName, $this->getInternalSettings())) fatalError('Trying to override an internal filter setting!');

		assert(!isset($this->_settings[$settingName]));
		$this->_settings[$settingName] = $setting;
	}

	/**
	 * Get a filter setting
	 * @param $settingName string
	 * @return FilterSetting
	 */
	function getSetting($settingName) {
		assert(isset($this->_settings[$settingName]));
		return $this->_settings[$settingName];
	}

	/**
	 * Get all filter settings
	 * @return array a list of FilterSetting objects
	 */
	function &getSettings() {
		return $this->_settings;
	}

	/**
	 * Check whether a given setting
	 * is present in this filter.
	 */
	function hasSetting($settingName) {
		return isset($this->_settings[$settingName]);
	}

	/**
	 * Can this filter be parameterized?
	 * @return boolean
	 */
	function hasSettings() {
		return (is_array($this->_settings) && count($this->_settings));
	}

	//
	// Abstract template methods to be implemented by subclasses
	//
	/**
	 * Return the fully qualified class name of the filter class. This
	 * information must be persisted when saving a filter so that the
	 * filter can later be reconstructed from the information in the
	 * database.
	 *
	 * (This must be hard coded by sub-classes for PHP4 compatibility.
	 * PHP4 always returns class names lowercase which we cannot
	 * tolerate as we need this path to find the class on case sensitive
	 * file systems.)
	 */
	function getClassName() {
		assert(false);
	}

	//
	// Public methods
	//
	/**
	 * Return an array with the names of non-localized
	 * filter settings.
	 *
	 * This will be used by the FilterDAO for filter
	 * setting persistence.
	 *
	 * @return array
	 */
	function getSettingNames() {
		$settingNames = array();
		foreach($this->getSettings() as $setting) { /* @var $setting FilterSetting */
			if (!$setting->getIsLocalized()) {
				$settingNames[] = $setting->getName();
			}
		}
		return $settingNames;
	}

	/**
	 * Return an array with the names of localized
	 * filter settings.
	 *
	 * This will be used by the FilterDAO for filter
	 * setting persistence.
	 *
	 * @return array
	 */
	function getLocalizedSettingNames() { /* @var $setting FilterSetting */
		$localizedSettingNames = array();
		foreach($this->getSettings() as $setting) {
			if ($setting->getIsLocalized()) {
				$localizedSettingNames[] = $setting->getName();
			}
		}
		return $localizedSettingNames;
	}


	//
	// Public static helper methods
	//
	/**
	 * There are certain generic filters (e.g. CompositeFilters)
	 * that sometimes need to be persisted and sometimes are
	 * instantiated and used in code only.
	 *
	 * As we don't have multiple inheritance in PHP we'll have
	 * to use the more general filter type (PersistableFilter) as
	 * the base class of these "hybrid" filters.
	 *
	 * This means that we carry around some structure (e.g. filter
	 * groups) that do only make sense when a filter is actually
	 * being persisted and otherwise create unnecessary code.
	 *
	 * We provide this helper function to instantiate a temporary
	 * filter group on the fly with only an input and an output type
	 * which takes away at least some of the cruft.
	 *
	 * @param $inputType string
	 * @param $outputType string
	 */
	static function tempGroup($inputType, $outputType) {
		$temporaryGroup = new FilterGroup();
		$temporaryGroup->setSymbolic(FILTER_GROUP_TEMPORARY_ONLY);
		$temporaryGroup->setInputType($inputType);
		$temporaryGroup->setOutputType($outputType);
		return $temporaryGroup;
	}


	//
	// Protected helper methods
	//
	/**
	 * Returns names of settings which are in use by the
	 * filter class and therefore cannot be set as filter
	 * settings.
	 * @return array
	 */
	function getInternalSettings() {
		return array('id', 'displayName', 'isTemplate', 'parentFilterId', 'seq');
	}
}
?>
