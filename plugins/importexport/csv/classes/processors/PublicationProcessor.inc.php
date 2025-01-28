<?php

/**
 * @file plugins/importexport/csv/classes/processors/PublicationProcessor.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Processes the publication data into the database.
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Processors;

use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedDaos;

class PublicationProcessor
{
    /**
     * Processes initial data for Publication
	 *
	 * @param \Submission $submission
	 * @param object $data
	 * @param \Journal $journal
	 *
	 * @return \Publication
	 */
    public static function process($submission, $data, $journal)
    {
		$publicationDao = CachedDaos::getPublicationDao();
		$sanitizedAbstract = \PKPString::stripUnsafeHtml($data->articleAbstract);
		$locale = $data->locale;

		/** @var \Publication $publication */
		$publication = $publicationDao->newDataObject();
        $publication->stampModified();
		$publication->setData('submissionId', $submission->getId());
		$publication->setData('version', 1);
		$publication->setData('status', STATUS_PUBLISHED);
		$publication->setData('datePublished', $data->datePublished);
		$publication->setData('abstract', $sanitizedAbstract, $locale);
		$publication->setData('title', $data->articleTitle, $locale);
		$publication->setData('copyrightNotice', $journal->getLocalizedData('copyrightNotice', $locale), $locale);

        if ($data->articleSubtitle) {
            $publication->setData('subtitle', $data->articleSubtitle, $locale);
        }

        if ($data->articlePrefix) {
            $publication->setData('prefix', $data->articlePrefix, $locale);
        }

        if ($data->startPage && $data->endPage) {
            $publication->setData('pages', "{$data->startPage}-{$data->endPage}");
        }

        $publicationDao->insertObject($publication);

        SubmissionProcessor::updateCurrentPublicationId($submission, $publication->getId());

        return $publication;
    }

    /**
     * Updates the primary contact ID for the publication
	 *
	 * @param \Publication $publication
	 * @param int $authorId
	 *
	 * @return void
     */
    public static function updatePrimaryContactId($publication, $authorId)
    {
        self::updatePublicationAttribute($publication, 'primaryContactId', $authorId);
    }

    /**
     * Updates the coverage for the publication
	 *
	 * @param \Publication $publication
	 * @param string $coverage
	 * @param string $locale
	 *
	 * @return void
     */
    public static function updateCoverage($publication, $coverage, $locale)
    {
        self::updatePublicationAttribute($publication, 'coverage', $coverage, $locale);
    }

    /**
     * Updates the cover image for the publication
	 *
	 * @param \Publication $publication
	 * @param object $data
	 * @param string $uploadName
	 *
	 * @return void
     */
    public static function updateCoverImage($publication, $data, $uploadName)
    {
        $coverImage = [
			'uploadName' => $uploadName,
			'altText' => $data->coverImageAltText ?? '',
		];

        self::updatePublicationAttribute($publication, 'coverImage', [$data->locale => $coverImage]);
    }

    /**
     * Updates the issue ID for the publication
	 *
	 * @param \Publication $publication
	 * @param int $issueId
	 *
	 * @return void
     */
    public static function updateIssueId($publication, $issueId)
    {
        self::updatePublicationAttribute($publication, 'issueId', $issueId);
    }

    /**
     * Updates the section ID for the publication
	 *
	 * @param \Publication $publication
	 * @param int $sectionId
	 *
	 * @return void
     */
    public static function updateSectionId($publication, $sectionId)
    {
        self::updatePublicationAttribute($publication, 'sectionId', $sectionId);
    }

    /**
     * Updates a specific attribute of the publication
	 *
	 * @param \Publication $publication
	 * @param string $attribute
	 * @param mixed $data
	 * @param string $locale
	 *
	 * @return void
     */
    static function updatePublicationAttribute($publication, $attribute, $data, $locale = null)
    {
        $publication->setData($attribute, $data, $locale);

        $publicationDao = CachedDaos::getPublicationDao();
        $publicationDao->updateObject($publication);
    }
}
