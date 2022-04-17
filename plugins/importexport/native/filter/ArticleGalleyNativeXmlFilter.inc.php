<?php

/**
 * @file plugins/importexport/native/filter/ArticleGalleyNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts an Galley to a Native XML document.
 */

use APP\facades\Repo;

import('lib.pkp.plugins.importexport.native.filter.RepresentationNativeXmlFilter');

class ArticleGalleyNativeXmlFilter extends RepresentationNativeXmlFilter
{
    //
    // Implement template methods from PersistableFilter
    //
    /**
     * @copydoc PersistableFilter::getClassName()
     */
    public function getClassName()
    {
        return 'plugins.importexport.native.filter.ArticleGalleyNativeXmlFilter';
    }

    //
    // Extend functions in RepresentationNativeXmlFilter
    //
    /**
     * Create and return a representation node. Extend the parent class
     * with publication format specific data.
     *
     * @param DOMDocument $doc
     * @param Representation $representation
     *
     * @return DOMElement
     */
    public function createRepresentationNode($doc, $representation)
    {
        $representationNode = parent::createRepresentationNode($doc, $representation);
        $representationNode->setAttribute('approved', $representation->getIsApproved() ? 'true' : 'false');

        return $representationNode;
    }

    /**
     * Get the available submission files for a representation
     *
     * @param Representation $representation
     *
     * @return array
     */
    public function getFiles($representation)
    {
        $galleyFiles = [];
        if ($representation->getData('submissionFileId')) {
            $galleyFiles = [Repo::submissionFile()->get($representation->getData('submissionFileId'))];
        }
        return $galleyFiles;
    }
}
