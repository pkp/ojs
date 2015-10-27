<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlArticleGalleyFilter.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeXmlArticleGalleyFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Native XML document to a set of publication formats.
 */

import('lib.pkp.plugins.importexport.native.filter.NativeXmlRepresentationFilter');

class NativeXmlArticleGalleyFilter extends NativeXmlRepresentationFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function NativeXmlArticleGalleyFilter($filterGroup) {
		parent::NativeXmlRepresentationFilter($filterGroup);
	}

	//
	// Implement template methods from NativeImportFilter
	//
	/**
	 * Return the plural element name
	 * @return string
	 */
	function getPluralElementName() {
		return 'article_galleys'; // defined if needed in the future.
	}

	/**
	 * Get the singular element name
	 * @return string
	 */
	function getSingularElementName() {
		return 'article_galley';
	}

	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.NativeXmlArticleGalleyFilter';
	}


	/**
	 * Handle a submission element
	 * @param $node DOMElement
	 * @return array Array of ArticleGalley objects
	 */
	function handleElement($node) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$submission = $deployment->getSubmission();
		assert(is_a($submission, 'Submission'));

		$representation = parent::handleElement($node);

		if ($node->getAttribute('approved') == 'true') $representation->setIsApproved(true);

		$galleyType = $node->getAttribute('galley_type');
		$representation->setGalleyType($galleyType);

		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) if (is_a($n, 'DOMElement')) switch($n->tagName) {
			case 'remote_url': $representation->setRemoteURL($n->textContent); break;
			case 'name':
				// Labels are not localized in OJS ArticleGalleys, but we use the <name locale="....">...</name> structure.
				$representation->setLabel($n->textContent);
				$representation->setLocale($n->getAttribute('locale'));
				break;

		}

		$representationDao = Application::getRepresentationDAO();
		$representationDao->insertObject($representation);

		// representation proof files
		return $representation;
	}
}

?>
