<?php

/**
 * @file plugins/importexport/native/filter/PublicationNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Publication to a Native XML document.
 */

use APP\facades\Repo;

import('lib.pkp.plugins.importexport.native.filter.PKPPublicationNativeXmlFilter');

class PublicationNativeXmlFilter extends PKPPublicationNativeXmlFilter
{
    //
    // Implement template methods from PersistableFilter
    //
    /**
     * @copydoc PersistableFilter::getClassName()
     */
    public function getClassName()
    {
        return 'plugins.importexport.native.filter.PublicationNativeXmlFilter';
    }


    //
    // Implement abstract methods from SubmissionNativeXmlFilter
    //
    /**
     * Get the representation export filter group name
     *
     * @return string
     */
    public function getRepresentationExportFilterGroupName()
    {
        return 'article-galley=>native-xml';
    }

    //
    // Publication conversion functions
    //
    /**
     * Create and return a publication node.
     *
     * @param DOMDocument $doc
     * @param Publication $entity
     *
     * @return DOMElement
     */
    public function createEntityNode($doc, $entity)
    {
        $deployment = $this->getDeployment();
        $entityNode = parent::createEntityNode($doc, $entity);

        // Add the series, if one is designated.
        if ($sectionId = $entity->getData('sectionId')) {
            $sectionDao = DAORegistry::getDAO('SectionDAO'); /** @var SectionDAO $sectionDao */
            $section = $sectionDao->getById($sectionId);
            assert(isset($section));
            $entityNode->setAttribute('section_ref', $section->getLocalizedAbbrev());
        }

        // if this is a published submission and not part/subelement of an issue element
        // add issue identification element
        if ($entity->getData('issueId') && !$deployment->getIssue()) {
            $issue = Repo::issue()->get($entity->getData('issueId'));
            import('plugins.importexport.native.filter.NativeFilterHelper');
            $nativeFilterHelper = new NativeFilterHelper();
            $entityNode->appendChild($nativeFilterHelper->createIssueIdentificationNode($this, $doc, $issue));
        }

        $pages = $entity->getData('pages');
        if (!empty($pages)) {
            $entityNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'pages', htmlspecialchars($pages, ENT_COMPAT, 'UTF-8')));
        }

        // cover images
        import('plugins.importexport.native.filter.NativeFilterHelper');
        $nativeFilterHelper = new NativeFilterHelper();
        $coversNode = $nativeFilterHelper->createPublicationCoversNode($this, $doc, $entity);
        if ($coversNode) {
            $entityNode->appendChild($coversNode);
        }

        return $entityNode;
    }
}
