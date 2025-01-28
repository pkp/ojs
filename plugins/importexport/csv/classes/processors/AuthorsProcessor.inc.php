<?php

/**
 * @file plugins/importexport/csv/classes/processors/AuthorsProcessor.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AuthorsProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Process the authors data into the database.
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Processors;

use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedDaos;

class AuthorsProcessor
{
    /**
	 * Process data for Submission authors
	 *
	 * @param object $data
	 * @param string $contactEmail
	 * @param int $submissionId
	 * @param \Publication $publication
	 * @param int $userGroupId
	 *
	 * @return void
	 */
	public static function process($data, $contactEmail, $submissionId, $publication, $userGroupId)
    {
		$authorDao = CachedDaos::getAuthorDao();
		$authorsString = array_map('trim', explode(';', $data->authors));

        foreach ($authorsString as $index => $authorString) {
            /**
             * Examine the author string. The pattern is: "GivenName,FamilyName,email@email.com,affiliation".
             *
             * If the article has more than one author, it must separate the authors by a semicolon (;). Example:
             * "<AUTHOR_1_INFORMATION>;<AUTHOR_2_INFORMATION>".
             *
             * Fields familyName, email, and affiliation are optional and can be left as empty fields. E.g.:
             * "GivenName,,,".
             *
             * By default, if an author doesn't have an email, the primary contact email will be used in its place.
             */
			$givenName = $familyName = $emailAddress = null;
			[$givenName, $familyName, $emailAddress, $affiliation] = array_map('trim', explode(',', $authorString));

			if (empty($emailAddress)) {
				$emailAddress = $contactEmail;
			}

			/** @var \Author $author */
			$author = $authorDao->newDataObject();
			$author->setSubmissionId($submissionId);
			$author->setUserGroupId($userGroupId);
			$author->setGivenName($givenName, $data->locale);
			$author->setFamilyName($familyName, $data->locale);
			$author->setEmail($emailAddress);
            $author->setAffiliation($affiliation, $data->locale);
			$author->setData('publicationId', $publication->getId());
			$authorDao->insertObject($author);

			if (!$index) {
				$author->setPrimaryContact(true);
				$authorDao->updateObject($author);

                PublicationProcessor::updatePrimaryContactId($publication, $author->getId());
			}
		}
	}
}
