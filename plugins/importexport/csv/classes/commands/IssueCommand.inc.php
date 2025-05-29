<?php

/**
 * @file plugins/importexport/csv/classes/commands/IssueCommand.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueCommand
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Handles the issue import when the user uses the issue command
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Commands;

import('lib.pkp.classes.submission.SubmissionFile');
import('lib.pkp.classes.file.FileManager');

use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedDaos;
use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedEntities;
use PKP\Plugins\ImportExport\CSV\Classes\Handlers\CSVFileHandler;
use PKP\Plugins\ImportExport\CSV\Classes\Processors\AuthorsProcessor;
use PKP\Plugins\ImportExport\CSV\Classes\Processors\CategoriesProcessor;
use PKP\Plugins\ImportExport\CSV\Classes\Processors\GalleyProcessor;
use PKP\Plugins\ImportExport\CSV\Classes\Processors\IssueProcessor;
use PKP\Plugins\ImportExport\CSV\Classes\Processors\KeywordsProcessor;
use PKP\Plugins\ImportExport\CSV\Classes\Processors\PublicationProcessor;
use PKP\Plugins\ImportExport\CSV\Classes\Processors\SectionsProcessor;
use PKP\Plugins\ImportExport\CSV\Classes\Processors\SubjectsProcessor;
use PKP\Plugins\ImportExport\CSV\Classes\Processors\SubmissionFileProcessor;
use PKP\Plugins\ImportExport\CSV\Classes\Processors\SubmissionProcessor;
use PKP\Plugins\ImportExport\CSV\Classes\Validations\InvalidRowValidations;
use PKP\Plugins\ImportExport\CSV\Classes\Validations\RequiredIssueHeaders;

class IssueCommand
{
    /**
     * Expected row size for a CSV based on the command passed as argument
	 *
     * @var int
     */
    private $_expectedRowSize;

    /**
     * The folder containing all CSV files that the command must go through
	 *
     * @var string
     */
    private $_sourceDir;

    /** @var int */
    private $_processedRows;

    /** @var int */
    private $_failedRows;

    /** @var \PublicFileManager */
    private $_publicFileManager;

    /** @var \FileManager */
    private $_fileManager;

    /** @var \PKPFileService */
    private $_fileService;

    /** @var \User */
    private $_user;

    /**
	 * The file directory array map used by the application.
	 *
	 * @var string[]
	 */
	private $_dirNames;

    /** @var string */
    private $_format;

    /**
	 * @param string $sourceDir
	 * @param \User $user
	 */
    public function __construct($sourceDir, $user)
    {
		import('plugins.importexport.csv.classes.validations.RequiredIssueHeaders');
        $this->_expectedRowSize = count(RequiredIssueHeaders::$issueHeaders);
        $this->_sourceDir = $sourceDir;
        $this->_user = $user;
    }

    public function run()
    {
        foreach (new \DirectoryIterator($this->_sourceDir) as $fileInfo) {
            if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'csv') {
                continue;
            }

            $filePath = $fileInfo->getPathname();

            $file = CSVFileHandler::createReadableCSVFile($filePath);

            if (is_null($file)) {
                continue;
            }

            $basename = $fileInfo->getBasename();
            $invalidCsvFile = CSVFileHandler::createCSVFileInvalidRows($this->_sourceDir, "invalid_{$basename}", RequiredIssueHeaders::$issueHeaders);

            if (is_null($invalidCsvFile)) {
                continue;
            }

            $this->_processedRows = 0;
            $this->_failedRows = 0;

            foreach ($file as $index => $fields) {
                if (!$index || empty(array_filter($fields))) {
                    continue; // Skip headers or end of file
                }

                ++$this->_processedRows;

                $reason = InvalidRowValidations::validateRowContainAllFields($fields, $this->_expectedRowSize);

                if (!is_null($reason)) {
                    CSVFileHandler::processFailedRow($invalidCsvFile, $fields, $this->_expectedRowSize, $reason, $this->_failedRows);
                    continue;
                }

                $data = (object) array_combine(
                    RequiredIssueHeaders::$issueHeaders,
                    array_pad(array_map('trim', $fields), $this->_expectedRowSize, null)
                );

                $reason = InvalidRowValidations::validateRowHasAllRequiredFields($data, [RequiredIssueHeaders::class, 'validateRowHasAllRequiredFields']);

                if (!is_null($reason)) {
                    CSVFileHandler::processFailedRow($invalidCsvFile, $fields, $this->_expectedRowSize, $reason, $this->_failedRows);
                    continue;
                }

                $fieldsList = array_pad($fields, $this->_expectedRowSize, null);

                $reason = InvalidRowValidations::validateArticleFileIsValid($data->articleGalleyFilename, $this->_sourceDir);

                if (!is_null($reason)) {
                    CSVFileHandler::processFailedRow($invalidCsvFile, $fields, $this->_expectedRowSize, $reason, $this->_failedRows);
                    continue;
                }

                if ($data->suppFilenames) {
                    $reason = InvalidRowValidations::validateArticleGalleys(
                        $data->suppFilenames,
                        $data->suppLabels,
                        $this->_sourceDir
                    );

                    if (!is_null($reason)) {
                        CSVFileHandler::processFailedRow($invalidCsvFile, $fields, $this->_expectedRowSize, $reason, $this->_failedRows);
                        continue;
                    }
                }

                $journal = CachedEntities::getCachedJournal($data->journalPath);

                $reason = InvalidRowValidations::validateJournalIsValid($journal, $data->journalPath);
                if (!is_null($reason)) {
                    CSVFileHandler::processFailedRow($invalidCsvFile, $fields, $this->_expectedRowSize, $reason, $this->_failedRows);
                    continue;
                }

                $reason = InvalidRowValidations::validateJournalLocale($journal, $data->locale);

                if (!is_null($reason)) {
                    CSVFileHandler::processFailedRow($invalidCsvFile, $fields, $this->_expectedRowSize, $reason, $this->_failedRows);
                    continue;
                }

                // we need a Genre for the files.  Assume a key of SUBMISSION as a default.
			    $genreName = mb_strtoupper($data->genreName ?? 'SUBMISSION');
                $genreId = CachedEntities::getCachedGenreId($genreName, $journal->getId());
                $reason = InvalidRowValidations::validateGenreIdValid($genreId, $genreName);

                if (!is_null($reason)) {
                    CSVFileHandler::processFailedRow($invalidCsvFile, $fields, $this->_expectedRowSize, $reason, $this->_failedRows);
                    continue;
                }

                $userGroupId = CachedEntities::getCachedUserGroupId($data->journalPath, $journal->getId());
                $reason = InvalidRowValidations::validateUserGroupId($userGroupId, $data->journalPath);

                if (!is_null($reason)) {
                    CSVFileHandler::processFailedRow($invalidCsvFile, $fields, $this->_expectedRowSize, $reason, $this->_failedRows);
                    continue;
                }

                $this->_initializeStaticVariables();

                if ($data->coverImageFilename) {
                    $reason = InvalidRowValidations::validateCoverImageIsValid($data->coverImageFilename, $this->_sourceDir);

                    if (!is_null($reason)) {
                        CSVFileHandler::processFailedRow($invalidCsvFile, $fields, $this->_expectedRowSize, $reason, $this->_failedRows);
                        continue;
                    }

                    $sanitizedCoverImageName = str_replace([' ', '_', ':'], '-', mb_strtolower($data->coverImageFilename));
                    $sanitizedCoverImageName = \PKPString::regexp_replace('/[^a-z0-9\.\-]+/', '', $sanitizedCoverImageName);
                    $coverImageUploadName = uniqid() . '-' . basename($sanitizedCoverImageName);

                    $destFilePath = $this->_publicFileManager->getContextFilesPath($journal->getId()) . '/' . $coverImageUploadName;
                    $srcFilePath = "{$this->_sourceDir}/{$data->coverImageFilename}";
                    $bookCoverImageSaved = $this->_fileManager->copyFile($srcFilePath, $destFilePath);

                    if (!$bookCoverImageSaved) {
                        $reason = __('plugin.importexport.csv.erroWhileSavingBookCoverImage');
                        CSVFileHandler::processFailedRow($invalidCsvFile, $fields, $this->_expectedRowSize, $reason, $this->_failedRows);

                        continue;
                    }
                }

                // All requirements passed. Start processing from here.
				import('plugins.importexport.csv.classes.processors.SubmissionProcessor');
                $submission = SubmissionProcessor::process($journal->getId(), $data);

                // Copy Submission file. If an error occured, save this row as invalid,
                // delete the saved submission and continue the loop.
                $articleGalleyFilenameId = $this->_saveSubmissionFile(
                    $data->articleGalleyFilename,
                    $journal->getId(),
                    $submission->getId(),
                    $invalidCsvFile,
                    __('plugins.importexport.csv.errorWhileSavingSubmissionFile'),
                    $fieldsList
                );

                if (is_null($articleGalleyFilenameId)) {
                    continue;
                }

                $suppIds = [];
                foreach (array_map('trim', explode(';', $data->suppFilenames)) as $suppFile) {
                    $suppFileId = $this->_saveSubmissionFile(
                        $suppFile,
                        $journal->getId(),
                        $submission->getId(),
                        $invalidCsvFile,
                        __('plugins.importexport.csv.errorWhileSavingSubmissionGalley', ['supp' => $suppFile]),
                        $fieldsList
                    );

                    if (is_null($suppFileId)) {
                        $this->_fileService->delete($articleGalleyFilenameId);

                        foreach($suppIds as $suppItem) {
                            $this->_fileService->delete($suppItem['id']);
                        }

                        continue;
                    }

                    $suppIds[] = ['file' => $suppFile, 'id' => $suppFileId];
                }

				import('plugins.importexport.csv.classes.processors.PublicationProcessor');
                $publication = PublicationProcessor::process($submission, $data, $journal);

				import('plugins.importexport.csv.classes.processors.AuthorsProcessor');
                AuthorsProcessor::process($data, $journal->getContactEmail(), $submission->getId(), $publication, $userGroupId);

                // Process submission file data into the database
				import('plugins.importexport.csv.classes.processors.SubmissionFileProcessor');
                $articleFileCompletePath = "{$this->_sourceDir}/{$data->articleGalleyFilename}";
                SubmissionFileProcessor::process(
                    $data->locale,
                    $this->_user->getId(),
                    $submission->getId(),
                    $articleFileCompletePath,
                    $genreId,
                    $articleGalleyFilenameId
                );

                // Now, process the submission file for all article galleys
                $galleyLabelsArray = array_map('trim', explode(';', $data->galleyLabels));

                for($i = 0; $i < count($galleyLabelsArray); $i++) {
                    $galleyItem = $galleyIds[$i];
                    $galleyLabel = $galleyLabelsArray[$i];

                    $this->_handleArticleGalley(
                        $galleyItem,
                        $data,
                        $submission->getId(),
                        $genreId,
                        $galleyLabel,
                        $publication->getId()
                    );
                }

				import('plugins.importexport.csv.classes.processors.KeywordsProcessor');
                KeywordsProcessor::process($data, $publication->getId());

				import('plugins.importexport.csv.classes.processors.SubjectsProcessor');
                SubjectsProcessor::process($data, $publication->getId());

                if ($data->coverage) {
                    PublicationProcessor::updateCoverage($publication, $data->coverage, $data->locale);
                }

                if ($data->coverImageFilename) {
                    PublicationProcessor::updateCoverImage($publication, $data, $coverImageUploadName);
                }

                if ($data->categories) {
					import('plugins.importexport.csv.classes.processors.CategoriesProcessor');
                    CategoriesProcessor::process($data->categories, $data->locale, $journal->getId(), $publication->getId());
                }

				import('plugins.importexport.csv.classes.processors.IssueProcessor');
                $issue = IssueProcessor::process($journal->getId(), $data);
                PublicationProcessor::updateIssueId($publication, $issue->getId());

				import('plugins.importexport.csv.classes.processors.SectionsProcessor');
                $section = SectionsProcessor::process($data, $journal->getId());
                PublicationProcessor::updateSectionId($publication, $section->getId());
            }

            echo __('plugins.importexpot.csv.fileProcessFinished', [
                'filename' => $fileInfo->getFilename(),
                'processedRows' => $this->_processedRows,
                'failedRows' => $this->_failedRows,
            ]) . "\n";

            if (!$this->_failedRows) {
                unlink($this->_sourceDir . '/' . "invalid_{$basename}");
            }
        }
    }

    /**
	 * Insert static data that will be used for the submission processing
	 */
	private function _initializeStaticVariables(): void
    {
		$this->_dirNames ??= \Application::getFileDirectories();
		$this->_format ??= trim($this->_dirNames['context'], '/') . '/%d/' . trim($this->_dirNames['submission'], '/') . '/%d';
		$this->_fileManager ??= new \FileManager();
		$this->_publicFileManager ??= new \PublicFileManager();
		$this->_fileService ??= \Services::get('file');
	}

    /**
     * Save a submission file. If an error occurred, the method will delete the submission already saved
     * and return null.
     *
     * @param string $filePath
     * @param int $journalId
     * @param int $submissionId
     * @param \SplFileObject $invalidCsvFile
     * @param string $reason
     * @param array $fieldsList
     *
     * @return int|null
     */
    private function _saveSubmissionFile($filePath, $journalId, $submissionId, &$invalidCsvFile, $reason, $fieldsList)
    {
        try {
            $extension = $this->_fileManager->parseFileExtension($filePath);
            $submissionDir = sprintf($this->_format, $journalId, $submissionId);
            $completePath = "{$this->_sourceDir}/{$filePath}";
            return $this->_fileService->add($completePath, $submissionDir . '/' . uniqid() . '.' . $extension);
        } catch (\Exception $e) {
            CSVFileHandler::processFailedRow($invalidCsvFile, $fieldsList, $this->_expectedRowSize, $reason, $this->_failedRows);

            $submissionDao = CachedDaos::getSubmissionDao();
            $submissionDao->deleteById($submissionId);

            return null;
        }
    }

    /**
     * Process data for the galley submission file and galley into the database.
     *
     * @param array $galleyItem
     * @param object $data
     * @param int $submissionId
     * @param int $genreId
     * @param string $galleyLabel
     * @param int $publicationId
	 *
	 * @return void
     */
    private function _handleArticleGalley($galleyItem, $data, $submissionId, $genreId, $galleyLabel, $publicationId)
    {
        $galleyCompletePath = "{$this->_sourceDir}/{$galleyItem['file']}";
        $galleyExtension = $this->_fileManager->parseFileExtension($galleyCompletePath);

        $submissionFile = SubmissionFileProcessor::process(
            $data->locale,
            $this->_user->getId(),
            $submissionId,
            $galleyCompletePath,
            $genreId,
            $galleyItem['id'],
        );

        // Now that we have the submission file ID, it's time to process the galley itself.
		import('plugins.importexport.csv.classes.processors.GalleyProcessor');
        $galleyId = GalleyProcessor::process($submissionFile->getId(), $data, $galleyLabel, $publicationId, $galleyExtension);
        SubmissionFileProcessor::updateAssocInfo($submissionFile, $galleyId);
    }
}
