<?php

/**
 * @defgroup oai_format
 */

/**
 * @file plugins/oaiMetadataFormats/dc/OAIMetadataFormat_DC.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_DC
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- Dublin Core.
 */
import('lib.pkp.plugins.oaiMetadataFormats.dc.PKPOAIMetadataFormat_DC');

class OAIMetadataFormat_DC extends PKPOAIMetadataFormat_DC {

	/**
	 * @see lib/pkp/plugins/oaiMetadataFormats/dc/PKPOAIMetadataFormat_DC::toXml()
	 */
	function toXml(&$record, $format = null) {
		$article =& $record->getData('article');
		return parent::toXml($article, $format);
	}
}

?>
