<?php

/**
 * @file classes/services/GalleyService.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GalleyService
 * @ingroup services
 *
 * @brief Helper class that encapsulates galley business logic
 */

namespace OJS\Services;

use \PKP\Services\EntityProperties\PKPBaseEntityPropertyService;

class GalleyService implements PKPBaseEntityPropertyService {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct($this);
	}
	
	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getProperties()
	 */
	public function getProperties($galley, $props, $args = null) {
		$values = array();
		foreach ($props as $prop) {
			switch ($prop) {
				case 'id':
					$values[$prop] = $galley->getId();
					break;
				case '_href':
					$values[$prop] = $galley->getId();
					break;
// 				case 'parent':
// 					$values[$prop] = $galley->getId();
// 					break;
				case 'locale':
					$values[$prop] = $galley->getId();
					break;
				case 'label':
					$values[$prop] = $galley->getName(null);
					break;
				case 'remoteUrl':
					$values[$prop] = $galley->getRemoteURL();
					break;
// 				case 'publishedUrl':
// 					$values[$prop] = $galley->getId();
// 					break;
				case 'seq':
				case 'sequence':
					$values[$prop] = $galley->getSequence();
					break;
				default:
					$this->getUnknownProperty($galley, $prop, $values);
			}
		}
	
		return $values;
	}
	
	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getSummaryProperties()
	 */
	public function getSummaryProperties($galley, $args = null) {
		$props = array (
			'id','_href','parent','locale','label','seq','remoteUrl','publishedUrl'
		);
		$props = $this->getSummaryPropertyList($galley, $props);
		return $this->getProperties($galley, $props);
	}
	
	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getFullProperties()
	 */
	public function getFullProperties($galley, $args = null) {
		$props = array (
			'id','_href','parent','locale','label','seq','remoteUrl','publishedUrl'
		);
		$props = $this->getFullPropertyList($galley, $props);
		return $this->getProperties($galley, $props);
	}
}