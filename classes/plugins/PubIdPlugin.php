<?php

/**
 * @file classes/plugins/PubIdPlugin.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PubIdPlugin
 *
 * @ingroup plugins
 *
 * @brief Public identifiers plugins common functions
 */

namespace APP\plugins;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Collector;
use APP\issue\Issue;
use APP\journal\Journal;
use APP\notification\NotificationManager;
use APP\submission\Submission;
use PKP\core\JSONMessage;
use PKP\core\PKPString;
use PKP\submission\Representation;
use PKP\submissionFile\SubmissionFile;

abstract class PubIdPlugin extends \PKP\plugins\PKPPubIdPlugin
{
    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request)
    {
        $user = $request->getUser();
        $router = $request->getRouter();
        $context = $router->getContext($request);

        $notificationManager = new NotificationManager();
        switch ($request->getUserVar('verb')) {
            case 'assignPubIds':
                if (!$request->checkCSRF()) {
                    return new JSONMessage(false);
                }
                return $this->assignPubIds($request, $context);
            default:
                return parent::manage($args, $request);
        }
    }

    /**
     * Handles pubId assignment for any publication, galley, or issue pubIds
     * (usen on the plugin setting page, called in the plugin manage function)
     */
    protected function assignPubIds($request, $context): JSONMessage
    {
        $suffixFieldName = $this->getSuffixFieldName();
        $suffixGenerationStrategy = $this->getSetting($context->getId(), $suffixFieldName);
        if ($suffixGenerationStrategy != 'customId') {
            $issueEnabled = $this->isObjectTypeEnabled('Issue', $context->getId());
            $publicationEnabled = $this->isObjectTypeEnabled('Publication', $context->getId());
            $representationEnabled = $this->isObjectTypeEnabled('Representation', $context->getId());
            if ($issueEnabled) {
                $issues = Repo::issue()->getCollector()
                    ->filterByContextIds([$context->getId()])
                    ->filterByPublished(true)
                    ->orderBy(Collector::ORDERBY_PUBLISHED_ISSUES)
                    ->getMany();
                foreach ($issues as $issue) {
                    $issuePubId = $issue->getStoredPubId($this->getPubIdType());
                    if (empty($issuePubId)) {
                        $issuePubId = $this->getPubId($issue);
                        Repo::issue()->dao->changePubId($issue->getId(), $this->getPubIdType(), $issuePubId);
                    }
                }
            }
            if ($publicationEnabled || $representationEnabled) {
                $representationDao = Application::getRepresentationDAO();
                $submissions = Repo::submission()->getCollector()
                    ->filterByContextIds([$context->getId()])
                    ->filterByStatus([Submission::STATUS_PUBLISHED])
                    ->getMany();

                foreach ($submissions as $submission) {
                    $publications = $submission->getData('publications');
                    if ($publicationEnabled) {
                        foreach ($publications as $publication) {
                            $publicationPubId = $publication->getStoredPubId($this->getPubIdType());
                            if (empty($publicationPubId)) {
                                $publicationPubId = $this->getPubId($publication);
                                Repo::publication()->dao->changePubId(
                                    $publication->getId(),
                                    $this->getPubIdType(),
                                    $publicationPubId
                                );
                            }
                        }
                    }
                    if ($representationEnabled) {
                        foreach ($publications as $publication) {
                            $representations = Repo::galley()->getCollector()
                                ->filterByPublicationIds([$publication->getId()])
                                ->getMany();

                            foreach ($representations as $representation) {
                                $representationPubId = $representation->getStoredPubId($this->getPubIdType());
                                if (empty($representationPubId)) {
                                    $representationPubId = $this->getPubId($representation);
                                    $representationDao->changePubId(
                                        $representation->getId(),
                                        $this->getPubIdType(),
                                        $representationPubId
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
        return new JSONMessage(true);
    }

    //
    // Protected template methods from PKPPlubIdPlugin
    //
    /**
     * @copydoc PKPPubIdPlugin::getPubObjectTypes()
     */
    public function getPubObjectTypes()
    {
        $pubObjectTypes = parent::getPubObjectTypes();
        $pubObjectTypes['Issue'] = 'APP\issue\Issue';
        return $pubObjectTypes;
    }

    /**
     * @copydoc PKPPubIdPlugin::checkDuplicate()
     */
    public function checkDuplicate($pubId, $pubObjectType, $excludeId, $contextId)
    {
        foreach ($this->getPubObjectTypes() as $type => $fqcn) {
            if ($type === 'Issue') {
                $excludeTypeId = $type === $pubObjectType ? $excludeId : null;
                if (Repo::issue()->dao->pubIdExists($this->getPubIdType(), $pubId, $excludeTypeId, $contextId)) {
                    return false;
                }
            }
        }

        return parent::checkDuplicate($pubId, $pubObjectType, $excludeId, $contextId);
    }

    /**
     * Get the public identifier.
     *
     * @param object $pubObject
     * 	Publication, Representation, SubmissionFile, Issue
     *
     * @return string
     */
    public function getPubId($pubObject)
    {
        // Get the pub id type
        $pubIdType = $this->getPubIdType();

        // If we already have an assigned pub id, use it.
        $storedPubId = $pubObject->getStoredPubId($pubIdType);
        if ($storedPubId) {
            return $storedPubId;
        }

        // Determine the type of the publishing object.
        $pubObjectType = $this->getPubObjectType($pubObject);

        // Initialize variables for publication objects.
        $issue = ($pubObjectType == 'Issue' ? $pubObject : null);
        $submission = null;
        // Publication is actually handled differently now, but keep it here however for now.
        $publication = ($pubObjectType == 'Publication' ? $pubObject : null);
        $representation = ($pubObjectType == 'Representation' ? $pubObject : null);
        $submissionFile = ($pubObjectType == 'SubmissionFile' ? $pubObject : null);

        // Get the context id.
        if ($pubObjectType === 'Issue') {
            $contextId = $pubObject->getJournalId();
        } elseif ($pubObjectType === 'Representation') {
            $publication = Repo::publication()->get($pubObject->getData('publicationId'));
            $submission = Repo::submission()->get($publication->getData('submissionId'));
            $contextId = $submission->getData('contextId');
        } elseif (in_array($pubObjectType, ['Publication', 'SubmissionFile'])) {
            $submission = Repo::submission()->get($pubObject->getData('submissionId'));
            $contextId = $submission->getData('contextId');
        }

        // Check the context
        $context = $this->getContext($contextId);
        if (!$context) {
            return null;
        }
        $contextId = $context->getId();

        // Check whether pub ids are enabled for the given object type.
        $objectTypeEnabled = $this->isObjectTypeEnabled($pubObjectType, $contextId);
        if (!$objectTypeEnabled) {
            return null;
        }

        // Retrieve the issue.
        if (!$pubObject instanceof Issue) {
            assert(!is_null($submission));
            $issue = Repo::issue()->getBySubmissionId($submission->getId());
            $issue = $issue->getJournalId() == $contextId ? $issue : null;
        }
        if ($issue && $contextId != $issue->getJournalId()) {
            return null;
        }

        // Retrieve the pub id prefix.
        $pubIdPrefix = $this->getSetting($contextId, $this->getPrefixFieldName());
        if (empty($pubIdPrefix)) {
            return null;
        }

        // Generate the pub id suffix.
        $suffixFieldName = $this->getSuffixFieldName();
        $suffixGenerationStrategy = $this->getSetting($contextId, $suffixFieldName);
        switch ($suffixGenerationStrategy) {
            case 'customId':
                $pubIdSuffix = $pubObject->getData($suffixFieldName);
                break;

            case 'pattern':
                $suffixPatternsFieldNames = $this->getSuffixPatternsFieldNames();
                $pubIdSuffix = $this->getSetting($contextId, $suffixPatternsFieldNames[$pubObjectType]);

                $pubIdSuffix = $this->generateCustomPattern($context, $pubIdSuffix, $pubObject, $issue, $submission, $representation, $submissionFile);

                break;

            default:
                $pubIdSuffix = $this::generateDefaultPattern($context, $issue, $submission, $representation, $submissionFile);
        }
        if (empty($pubIdSuffix)) {
            return null;
        }

        // Construct the pub id from prefix and suffix.
        $pubId = $this->constructPubId($pubIdPrefix, $pubIdSuffix, $contextId);

        return $pubId;
    }

    /**
     * Generate the default, semantic-based pub-id pattern suffix
     *
     * @param Journal $context
     * @param ?Issue $issue
     * @param Submission $submission
     * @param Representation $representation
     * @param SubmissionFile $submissionFile
     *
     */
    public static function generateDefaultPattern($context, $issue = null, $submission = null, $representation = null, $submissionFile = null): string
    {
        $pubIdSuffix = PKPString::regexp_replace('/[^-._;()\/A-Za-z0-9]/', '', PKPString::strtolower($context->getAcronym($context->getPrimaryLocale())));

        if ($issue) {
            $pubIdSuffix .= '.v' . $issue->getVolume() . 'i' . $issue->getNumber();
        } else {
            $pubIdSuffix .= '.v%vi%i';
        }

        if ($submission) {
            $pubIdSuffix .= '.' . $submission->getId();
        }

        if ($representation) {
            $pubIdSuffix .= '.g' . $representation->getId();
        }

        if ($submissionFile) {
            $pubIdSuffix .= '.f' . $submissionFile->getId();
        }

        return $pubIdSuffix;
    }

    /**
     * Generate the custom, user-defined pub-id pattern suffix
     *
     * @param Journal $context
     * @param string $pubIdSuffix
     * @param object $pubObject
     * @param Issue $issue
     * @param Submission $submission
     * @param Representation $representation
     * @param SubmissionFile $submissionFile
     *
     */
    public static function generateCustomPattern($context, $pubIdSuffix, $pubObject, $issue = null, $submission = null, $representation = null, $submissionFile = null): string
    {
        // %j - journal initials, remove special characters and uncapitalize
        $pubIdSuffix = PKPString::regexp_replace('/%j/', PKPString::regexp_replace('/[^-._;()\/A-Za-z0-9]/', '', PKPString::strtolower($context->getAcronym($context->getPrimaryLocale()))), $pubIdSuffix);

        // %x - custom identifier
        if ($pubObject->getStoredPubId('publisher-id')) {
            $pubIdSuffix = PKPString::regexp_replace('/%x/', $pubObject->getStoredPubId('publisher-id'), $pubIdSuffix);
        }

        if ($issue) {
            // %v - volume number
            $pubIdSuffix = PKPString::regexp_replace('/%v/', $issue->getVolume(), $pubIdSuffix);
            // %i - issue number
            $pubIdSuffix = PKPString::regexp_replace('/%i/', $issue->getNumber(), $pubIdSuffix);
            // %Y - year
            $pubIdSuffix = PKPString::regexp_replace('/%Y/', $issue->getYear(), $pubIdSuffix);
        }

        if ($submission) {
            // %a - article id
            $pubIdSuffix = PKPString::regexp_replace('/%a/', $submission->getId(), $pubIdSuffix);
            // %p - page number
            if ($submission->getPages()) {
                $pubIdSuffix = PKPString::regexp_replace('/%p/', $submission->getPages(), $pubIdSuffix);
            }
        }

        if ($representation) {
            // %g - galley id
            $pubIdSuffix = PKPString::regexp_replace('/%g/', $representation->getId(), $pubIdSuffix);
        }

        if ($submissionFile) {
            // %f - file id
            $pubIdSuffix = PKPString::regexp_replace('/%f/', $submissionFile->getId(), $pubIdSuffix);
        }

        return $pubIdSuffix;
    }

    //
    // Public API
    //
    /**
     * Clear pubIds of all issue objects.
     *
     * @param Issue $issue
     */
    public function clearIssueObjectsPubIds($issue)
    {
        $publicationPubIdEnabled = $this->isObjectTypeEnabled('Publication', $issue->getJournalId());
        $representationPubIdEnabled = $this->isObjectTypeEnabled('Representation', $issue->getJournalId());
        $filePubIdEnabled = $this->isObjectTypeEnabled('SubmissionFile', $issue->getJournalId());
        if (!$publicationPubIdEnabled && !$representationPubIdEnabled && !$filePubIdEnabled) {
            return false;
        }

        $pubIdType = $this->getPubIdType();

        $submissionIds = Repo::submission()
            ->getCollector()
            ->filterByContextIds([$issue->getJournalId()])
            ->filterByIssueIds([$issue->getId()])
            ->getIds();

        foreach ($submissionIds as $submissionId) {
            $submission = Repo::submission()->get($submissionId);
            if ($publicationPubIdEnabled) { // Does this option have to be enabled here for?
                foreach ($submission->getData('publications') as $publication) {
                    Repo::publication()->dao->deletePubId($publication->getId(), $pubIdType);
                }
            }
            if ($representationPubIdEnabled || $filePubIdEnabled) { // Does this option have to be enabled here for?
                foreach ($submission->getData('publications') as $publication) {
                    $representations = Application::getRepresentationDAO()->getByPublicationId($publication->getId());
                    foreach ($representations as $representation) {
                        if ($representationPubIdEnabled) { // Does this option have to be enabled here for?
                            Application::getRepresentationDAO()->deletePubId($representation->getId(), $pubIdType);
                        }
                        if ($filePubIdEnabled) { // Does this option have to be enabled here for?
                            $articleProofFileIds = Repo::submissionFile()
                                ->getCollector()
                                ->filterByAssoc(
                                    Application::ASSOC_TYPE_REPRESENTATION,
                                    [$representation->getId()]
                                )->filterByFileStages([SubmissionFile::SUBMISSION_FILE_PROOF])
                                ->getIds();

                            foreach ($articleProofFileIds as $articleProofFileId) {
                                Repo::submissionFile()->dao->deletePubId($articleProofFileId, $pubIdType);
                            }
                        }
                    }
                    unset($representations);
                }
            }
        }
    }

    /**
     * @copydoc PKPPubIdPlugin::getDAOs()
     */
    public function getDAOs()
    {
        return array_merge(parent::getDAOs(), [Repo::issue()->dao]);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\PubIdPlugin', '\PubIdPlugin');
}
