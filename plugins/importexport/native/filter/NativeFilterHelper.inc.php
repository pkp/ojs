<?php
/**
 * @file plugins/importexport/native/filter/NativeFilterHelper.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class NativeFilterHelper
 * @ingroup plugins_importexport_native
 *
 * @brief Class that provides native import/export filter-related helper methods.
 */

class NativeFilterHelper {

	/**
	 * Create and return an issue identification node.
	 * @param $filter NativeExportFilter
	 * @param $doc DOMDocument
	 * @param $issue Issue
	 * @return DOMElement
	 */
	function createIssueIdentificationNode($filter, $doc, $issue) {
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
	 * @param $filter NativeExportFilter
	 * @param $doc DOMDocument
	 * @param $object Publication
	 * @return DOMElement?
	 */
	function createPublicationCoversNode($filter, $doc, $object) {
		$deployment = $filter->getDeployment();

		$context = $deployment->getContext();

		$coversNode = null;
		$coverImages = $object->getData('coverImage');
		if (!empty($coverImages)) {
			$coversNode = $doc->createElementNS($deployment->getNamespace(), 'covers');
			foreach ($coverImages as $locale => $coverImage) {
				$coverImageName = $coverImage['uploadName'];

				import('classes.file.PublicFileManager');
				$publicFileManager = new PublicFileManager();
				$contextId = $context->getId();
				$filePath = $publicFileManager->getContextFilesPath($contextId) . '/' . $coverImageName;

				if (!file_exists($filePath)) {
					$deployment->addWarning(ASSOC_TYPE_PUBLICATION, $object->getId(), __('plugins.importexport.common.error.publicationCoverImageMissing', ['id' => $object->getId(), 'path' => $filePath]));
					continue;
				}
				$coverNode = $doc->createElementNS($deployment->getNamespace(), 'cover');
				$coverNode->setAttribute('locale', $locale);
				$coverNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'cover_image', htmlspecialchars($coverImageName, ENT_COMPAT, 'UTF-8')));
				$coverNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'cover_image_alt_text', htmlspecialchars($coverImage['altText'], ENT_COMPAT, 'UTF-8')));

				if (!empty($filter->opts['use-file-urls'])) {
					import('classes.file.PublicFileManager');
					$publicFileManager = new PublicFileManager();
					$request = Application::get()->getRequest();

					if (!empty($filter->opts['use-absolute-urls'])) {
						$fileUrl = $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($object->getJournalId()) . '/' . $coverImageName;
					} else {
						$fileUrl = $request->getBasePath() . '/' . $publicFileManager->getContextFilesPath($object->getJournalId()) . '/' . $coverImageName;
					}

					$hrefNode = $doc->createElementNS($deployment->getNamespace(), 'href');
					$hrefNode->setAttribute('src', htmlspecialchars($fileUrl, ENT_COMPAT, 'UTF-8'));
					$hrefNode->setAttribute('mime_type', PKPString::mime_content_type($coverImageName));
					$coverNode->appendChild($hrefNode);
				} else if (empty($filter->opts['no-embed'])) {
					$embedNode = $doc->createElementNS($deployment->getNamespace(), 'embed', base64_encode(file_get_contents($filePath)));
					$embedNode->setAttribute('encoding', 'base64');
					$coverNode->appendChild($embedNode);
				}
				$coversNode->appendChild($coverNode);
			}
		}
		return $coversNode && $coversNode->firstChild ? $coversNode : null;
	}

	/**
	 * Create and return an object covers node.
	 * @param $filter NativeExportFilter
	 * @param $doc DOMDocument
	 * @param $object Issue
	 * @return DOMElement
	 */
	function createIssueCoversNode($filter, $doc, $object) {
		$deployment = $filter->getDeployment();
		$coversNode = null;
		$coverImages = $object->getCoverImage(null);
		if (!empty($coverImages)) {
			$coversNode = $doc->createElementNS($deployment->getNamespace(), 'covers');
			foreach ($coverImages as $locale => $coverImage) {
				import('classes.file.PublicFileManager');
				$publicFileManager = new PublicFileManager();
				$filePath = $publicFileManager->getContextFilesPath($object->getJournalId()) . '/' . $coverImage;
				if (!file_exists($filePath)) {
					$deployment->addWarning(ASSOC_TYPE_ISSUE, $object->getId(), __('plugins.importexport.native.common.issueCoverImageMissing', ['id' => $object->getId(), 'path' => $filePath]));
					continue;
				}
				$coverNode = $doc->createElementNS($deployment->getNamespace(), 'cover');
				$coverNode->setAttribute('locale', $locale);
				$coverNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'cover_image', htmlspecialchars($coverImage, ENT_COMPAT, 'UTF-8')));
				$coverNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'cover_image_alt_text', htmlspecialchars($object->getCoverImageAltText($locale), ENT_COMPAT, 'UTF-8')));

				import('lib.pkp.plugins.importexport.native.filter.NativeExportFilter');
				if ($filter->opts['serializationMode'] !== NativeExportFilter::SERIALIZATION_MODE_EMBED) {
					import('classes.file.PublicFileManager');
					$publicFileManager = new PublicFileManager();
					$request = Application::get()->getRequest();

					$fileUrl = ($filter->opts['serializationMode'] === NativeExportFilter::SERIALIZATION_MODE_URL)
					? $filePath
					: "{$request->getBasePath()}/{$coverImage}";

					$fileUrl = ($filter->opts['serializationMode'] === NativeExportFilter::SERIALIZATION_MODE_URL)
						? $request->url(null, 'issue', 'view', array($object->getId(), $coverImage))
						: $request->getBasePath() . '/' . $publicFileManager->getContextFilesPath($object->getJournalId()) . '/' . $coverImage;


					$hrefNode = $doc->createElementNS($deployment->getNamespace(), 'href');
					$hrefNode->setAttribute('src', htmlspecialchars($fileUrl, ENT_COMPAT, 'UTF-8'));
					import('lib.pkp.classes.core.PKPString');
					$mimeType = PKPString::mime_content_type($filePath);
					$hrefNode->setAttribute('mime_type', $mimeType);
					$coverNode->appendChild($hrefNode);
				} else {
					$embedNode = $doc->createElementNS($deployment->getNamespace(), 'embed', base64_encode(file_get_contents($filePath)));
					$embedNode->setAttribute('encoding', 'base64');
					$coverNode->appendChild($embedNode);
				}
				$coversNode->appendChild($coverNode);
			}
		}
		return $coversNode && $coversNode->firstChild ? $coversNode : null;
	}

	/**
	 * Parse out the object covers.
	 * @param $filter NativeExportFilter
	 * @param $node DOMElement
	 * @param $object Publication
	 */
	function parsePublicationCovers($filter, $node, $object) {
		$deployment = $filter->getDeployment();

		$coverImages = array();

		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'cover':
						$coverImage = $this->parsePublicationCover($filter, $n, $object);
						$coverImages[key($coverImage)] = reset($coverImage);
						break;
					default:
						$deployment->addWarning(ASSOC_TYPE_PUBLICATION, $object->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $n->tagName)));
				}
			}
		}

		$object->setData('coverImage', $coverImages);
	}

	/**
	 * Parse out the object covers.
	 * @param $filter NativeExportFilter
	 * @param $node DOMElement
	 * @param $object Issue
	 */
	function parseIssueCovers($filter, $node, $object) {
		$deployment = $filter->getDeployment();
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'cover':
						$this->parseIssueCover($filter, $n, $object);
						break;
					default:
						$deployment->addWarning(ASSOC_TYPE_ISSUE, $object->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $n->tagName)));
				}
			}
		}
	}

	/**
	 * Parse out the cover and store it in the object.
	 * @param $filter NativeExportFilter
	 * @param $node DOMElement
	 * @param $object Publication
	 */
	function parsePublicationCover($filter, $node, $object) {
		$deployment = $filter->getDeployment();

		$context = $deployment->getContext();

		$locale = $node->getAttribute('locale');
		if (empty($locale)) $locale = $context->getPrimaryLocale();

		$coverImagelocale = array();
		$coverImage = array();

		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'cover_image':
						$coverImage['uploadName'] = preg_replace(
							"/[^a-z0-9\.\-]+/",
							'',
							str_replace(
								[' ', '_', ':'],
								'-',
								strtolower($n->textContent)
							)
						);
						break;
					case 'cover_image_alt_text':
						$coverImage['altText'] = $n->textContent;
						break;
					case 'href':
					case 'embed':
						if (!isset($coverImage['uploadName'])) {
							$deployment->addWarning(ASSOC_TYPE_PUBLICATION, $object->getId(), __('plugins.importexport.common.error.coverImageNameUnspecified'));
							break;
						}

						import('classes.file.PublicFileManager');
						$publicFileManager = new PublicFileManager();
						$filePath = $publicFileManager->getContextFilesPath($context->getId()) . '/' . $coverImage['uploadName'];
						$allowedFileTypes = ['gif', 'jpg', 'png', 'webp'];
						$extension = pathinfo(strtolower($filePath), PATHINFO_EXTENSION);

						if (!in_array($extension, $allowedFileTypes)) {
							$deployment->addWarning(ASSOC_TYPE_PUBLICATION, $object->getId(), __('plugins.importexport.common.error.invalidFileExtension'));
							break;
						}

						if ($n->tagName === 'embed') {
							file_put_contents($filePath, base64_decode($n->textContent));
							break;
						}

						// If it falls through to here, it's a href mode.
						$imageUrl = $n->getAttribute('src');
						if (empty($imageUrl)) {
							$deployment->addWarning(ASSOC_TYPE_PUBLICATION, $object->getId(), __('plugins.importexport.common.error.missingHref'));
							break;
						}

						$fileContents = file_get_contents($imageUrl);
						if ($fileContents === false) {
							$deployment->addWarning(ASSOC_TYPE_PUBLICATION, $object->getId(), __('plugins.importexport.common.error.failedDownloadingFile', array('url' => $imageUrl)));
							break;
						}

						file_put_contents($filePath, $fileContents);
						break;
					default:
						$deployment->addWarning(ASSOC_TYPE_PUBLICATION, $object->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $n->tagName)));
				}
			}
		}

		$coverImagelocale[$locale] = $coverImage;

		return $coverImagelocale;
	}

	/**
	 * Parse out the cover and store it in the object.
	 * @param $filter NativeExportFilter
	 * @param $node DOMElement
	 * @param $object Issue
	 */
	function parseIssueCover($filter, $node, $object) {
		$deployment = $filter->getDeployment();
		$context = $deployment->getContext();
		$locale = $node->getAttribute('locale');
		if (empty($locale)) $locale = $context->getPrimaryLocale();
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'cover_image':
						$object->setCoverImage(
							trim(
								preg_replace(
									"/[^a-z0-9\.\-]+/",
									"",
									str_replace(
										[' ', '_', ':'],
										'-',
										strtolower($n->textContent)
									)
								)
							),
							$locale
						);
						break;
					case 'cover_image_alt_text': $object->setCoverImageAltText($n->textContent, $locale); break;
					case 'embed':
					case 'href':
						if (!$object->getCoverImage($locale)) {
							$deployment->addWarning(ASSOC_TYPE_ISSUE, $object->getId(), __('plugins.importexport.common.error.coverImageNameUnspecified'));
							break;
						}

						import('classes.file.PublicFileManager');
						$publicFileManager = new PublicFileManager();
						$filePath = $publicFileManager->getContextFilesPath($context->getId()) . '/' . $object->getCoverImage($locale);
						$allowedFileTypes = ['gif', 'jpg', 'png', 'webp'];
						$extension = pathinfo(strtolower($filePath), PATHINFO_EXTENSION);
						if (!in_array($extension, $allowedFileTypes)) {
							$deployment->addWarning(ASSOC_TYPE_ISSUE, $object->getId(), __('plugins.importexport.common.error.invalidFileExtension'));
							break;
						}

						if ($n->tagName === 'embed') {
							file_put_contents($filePath, base64_decode($n->textContent));
							break;
						}

						// If it falls through to here, it's a href mode.
						$imageUrl = $n->getAttribute('src');
						if (empty($imageUrl)) {
							$deployment->addWarning(ASSOC_TYPE_PUBLICATION, $object->getId(), __('plugins.importexport.common.error.missingHref'));
							break;
						}

						$fileContents = file_get_contents($imageUrl);
						if ($fileContents === false) {
							$deployment->addWarning(ASSOC_TYPE_PUBLICATION, $object->getId(), __('plugins.importexport.common.error.failedDownloadingFile', array('url' => $imageUrl)));
							break;
						}

						file_put_contents($filePath, $fileContents);
						break;
					case 'file_url':
						if (!$object->getCoverImage($locale)) {
							$deployment->addWarning(ASSOC_TYPE_ISSUE, $object->getId(), __('plugins.importexport.common.error.coverImageNameUnspecified'));
							break;
						}

						import('classes.file.PublicFileManager');
						$publicFileManager = new PublicFileManager();
						$filePath = $publicFileManager->getContextFilesPath($context->getId()) . '/' . $object->getCoverImage($locale);
						$allowedFileTypes = ['gif', 'jpg', 'png', 'webp'];
						$extension = pathinfo(strtolower($filePath), PATHINFO_EXTENSION);
						if (!in_array($extension, $allowedFileTypes)) {
							$deployment->addWarning(ASSOC_TYPE_ISSUE, $object->getId(), __('plugins.importexport.common.error.invalidFileExtension'));
							break;
						}

						$imageUrl = $n->textContent;
						if (empty($imageUrl)) {
							$deployment->addWarning(ASSOC_TYPE_ISSUE, $object->getId(), __('plugins.importexport.common.error.missingFileUrl'));
							break;
						}

						$fileContents = file_get_contents($imageUrl);
						if ($fileContents === false) {
							$deployment->addWarning(ASSOC_TYPE_ISSUE, $object->getId(), __('plugins.importexport.common.error.failedDownloadingFile', array('url' => $imageUrl)));
							break;
						}

						file_put_contents($filePath, $fileContents);
						break;
					default:
						$deployment->addWarning(ASSOC_TYPE_ISSUE, $object->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $n->tagName)));
				}
			}
		}
	}
}
