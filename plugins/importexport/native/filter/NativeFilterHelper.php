<?php
/**
 * @file plugins/importexport/native/filter/NativeFilterHelper.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NativeFilterHelper
 *
 * @ingroup plugins_importexport_native
 *
 * @brief Class that provides native import/export filter-related helper methods.
 */

namespace APP\plugins\importexport\native\filter;

use APP\core\Application;
use APP\file\PublicFileManager;
use APP\issue\Issue;
use DOMDocument;
use DOMElement;
use PKP\plugins\importexport\native\filter\NativeExportFilter;
use PKP\plugins\importexport\native\filter\NativeImportFilter;

class NativeFilterHelper extends \PKP\plugins\importexport\native\filter\PKPNativeFilterHelper
{
    /**
     * Create and return an issue identification node.
     *
     * @param NativeExportFilter $filter
     * @param \DOMDocument $doc
     * @param Issue $issue
     *
     * @return DOMElement
     */
    public function createIssueIdentificationNode($filter, $doc, $issue)
    {
        $deployment = $filter->getDeployment();
        $vol = $issue->getVolume();
        $num = $issue->getNumber();
        $year = $issue->getYear();
        $title = $issue->getTitle(null);
        assert($issue->getShowVolume() || $issue->getShowNumber() || $issue->getShowYear() || $issue->getShowTitle());
        $issueIdentificationNode = $doc->createElementNS($deployment->getNamespace(), 'issue_identification');
        if ($issue->getShowVolume()) {
            assert(!empty($vol));
            $issueIdentificationNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'volume', htmlspecialchars($vol, ENT_COMPAT, 'UTF-8')));
        }
        if ($issue->getShowNumber()) {
            assert(!empty($num));
            $issueIdentificationNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'number', htmlspecialchars($num, ENT_COMPAT, 'UTF-8')));
        }
        if ($issue->getShowYear()) {
            assert(!empty($year));
            $issueIdentificationNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'year', $year));
        }
        if ($issue->getShowTitle()) {
            assert(!empty($title));
            $filter->createLocalizedNodes($doc, $issueIdentificationNode, 'title', $title);
        }
        return $issueIdentificationNode;
    }

    /**
     * Create and return an object covers node.
     */
    public function createIssueCoversNode(NativeExportFilter $filter, DOMDocument $doc, Issue $object): ?DOMElement
    {
        $coverImages = $object->getCoverImage(null);
        if (empty($coverImages)) {
            return null;
        }

        $deployment = $filter->getDeployment();
        $publicFileManager = new PublicFileManager();
        $coversNode = $doc->createElementNS($deployment->getNamespace(), 'covers');
        foreach ($coverImages as $locale => $coverImage) {
            $coverNode = $doc->createElementNS($deployment->getNamespace(), 'cover');
            $filePath = $publicFileManager->getContextFilesPath($object->getJournalId()) . '/' . $coverImage;
            if (!file_exists($filePath)) {
                $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $object->getId(), __('plugins.importexport.common.error.issueCoverImageMissing', ['id' => $object->getId(), 'path' => $filePath]));
                continue;
            }

            $coverNode->setAttribute('locale', $locale);
            $coverNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'cover_image', htmlspecialchars($coverImage, ENT_COMPAT, 'UTF-8')));
            $coverNode->appendChild($doc->createElementNS($deployment->getNamespace(), 'cover_image_alt_text', htmlspecialchars($object->getCoverImageAltText($locale), ENT_COMPAT, 'UTF-8')));

            $embedNode = $doc->createElementNS($deployment->getNamespace(), 'embed', base64_encode(file_get_contents($filePath)));
            $embedNode->setAttribute('encoding', 'base64');
            $coverNode->appendChild($embedNode);
            $coversNode->appendChild($coverNode);
        }

        return $coversNode->firstChild?->parentNode;
    }

    /**
     * Parse out the object covers.
     *
     * @param NativeImportFilter $filter
     * @param DOMElement $node
     * @param Issue $object
     */
    public function parseIssueCovers($filter, $node, $object)
    {
        $deployment = $filter->getDeployment();
        for ($n = $node->firstChild; $n !== null; $n = $n->nextSibling) {
            if ($n instanceof DOMElement) {
                switch ($n->tagName) {
                    case 'cover':
                        $this->parseIssueCover($filter, $n, $object);
                        break;
                    default:
                        $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $object->getId(), __('plugins.importexport.common.error.unknownElement', ['param' => $n->tagName]));
                }
            }
        }
    }

    /**
     * Parse out the cover and store it in the object.
     *
     * @param NativeImportFilter $filter
     * @param DOMElement $node
     * @param Issue $object
     */
    public function parseIssueCover($filter, $node, $object)
    {
        $deployment = $filter->getDeployment();
        $context = $deployment->getContext();
        $locale = $node->getAttribute('locale');
        if (empty($locale)) {
            $locale = $context->getPrimaryLocale();
        }
        for ($n = $node->firstChild; $n !== null; $n = $n->nextSibling) {
            if ($n instanceof DOMElement) {
                switch ($n->tagName) {
                    case 'cover_image':
                        $object->setCoverImage(
                            preg_replace(
                                "/[^a-z0-9\.\-]+/",
                                '',
                                str_replace(
                                    [' ', '_', ':'],
                                    '-',
                                    strtolower($n->textContent)
                                )
                            ),
                            $locale
                        );
                        break;
                    case 'cover_image_alt_text':
                        $object->setCoverImageAltText($n->textContent, $locale);
                        break;
                    case 'embed':
                        if (!$object->getCoverImage($locale)) {
                            $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $object->getId(), __('plugins.importexport.common.error.coverImageNameUnspecified'));
                            break;
                        }
                        $publicFileManager = new PublicFileManager();
                        $filePath = $publicFileManager->getContextFilesPath($context->getId()) . '/' . $object->getCoverImage($locale);
                        $allowedFileTypes = ['gif', 'jpg', 'png', 'webp'];
                        $extension = pathinfo(strtolower($filePath), PATHINFO_EXTENSION);
                        if (!in_array($extension, $allowedFileTypes)) {
                            $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $object->getId(), __('plugins.importexport.common.error.invalidFileExtension'));
                            break;
                        }
                        file_put_contents($filePath, base64_decode($n->textContent));
                        break;
                    default:
                        $deployment->addWarning(Application::ASSOC_TYPE_ISSUE, $object->getId(), __('plugins.importexport.common.error.unknownElement', ['param' => $n->tagName]));
                }
            }
        }
    }
}
