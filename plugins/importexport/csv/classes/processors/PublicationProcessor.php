<?php

/**
 * @file plugins/importexport/csv/classes/processors/PublicationProcessor.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Processes the publication data into the database.
 */

namespace APP\plugins\importexport\csv\classes\processors;

use APP\facades\Repo;
use APP\journal\Journal;
use APP\publication\Publication;
use APP\submission\Submission;
use PKP\core\PKPString;

class PublicationProcessor
{
    /**
     * Processes initial data for Publication
     */
    public static function process(Submission $submission, object $data, Journal $journal): Publication
    {
        $publicationDao = Repo::publication()->dao;
        $sanitizedAbstract = PKPString::stripUnsafeHtml($data->articleAbstract);
        $locale = $data->locale;

        $publication = $publicationDao->newDataObject();
        $publication->stampModified();
        $publication->setData('submissionId', $submission->getId());
        $publication->setData('version', 1);
        $publication->setData('status', Submission::STATUS_PUBLISHED);
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

        $publicationDao->insert($publication);

        SubmissionProcessor::updateCurrentPublicationId($submission, $publication->getId());

        return $publication;
    }

    /**
     * Updates the primary contact ID for the publication
     */
    public static function updatePrimaryContactId(Publication $publication, int $authorId): void
    {
        self::updatePublicationAttribute($publication, 'primaryContactId', $authorId);
    }

    /**
     * Updates the coverage for the publication
     */
    public static function updateCoverage(Publication $publication, string $coverage, string $locale): void
    {
        self::updatePublicationAttribute($publication, 'coverage', $coverage, $locale);
    }

    /**
     * Updates the cover image for the publication
     */
    public static function updateCoverImage(Publication $publication, object $data, string $uploadName): void
    {
        $coverImage = [];

        $coverImage['uploadName'] = $uploadName;
        $coverImage['altText'] = $data->coverImageAltText ?? '';

        self::updatePublicationAttribute($publication, 'coverImage', [$data->locale => $coverImage]);
    }

    /**
     * Updates the issue ID for the publication
     */
    public static function updateIssueId(Publication $publication, int $issueId): void
    {
        self::updatePublicationAttribute($publication, 'issueId', $issueId);
    }

    /**
     * Updates the section ID for the publication
     */
    public static function updateSectionId(Publication $publication, int $sectionId): void
    {
        self::updatePublicationAttribute($publication, 'sectionId', $sectionId);
    }

    /**
     * Updates a specific attribute of the publication
     */
    public static function updatePublicationAttribute(Publication $publication, string $attribute, mixed $data, ?string $locale = null): void
    {
        $publication->setData($attribute, $data, $locale);

        $publicationDao = Repo::publication()->dao;
        $publicationDao->update($publication);
    }
}
