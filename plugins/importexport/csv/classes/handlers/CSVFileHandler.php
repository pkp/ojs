<?php

/**
 * @file plugins/importexport/csv/classes/handlers/CSVFileHandler.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CSVFileHandler
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Handles the issue import when the user uses the issue command
 */

namespace APP\plugins\importexport\csv\classes\handlers;

use Exception;
use SplFileObject;

class CSVFileHandler
{
    /**
     * Create a new readable SplFileObject. Return null if an error occurred.
     */
    public static function createReadableCSVFile(string $filePath): ?SplFileObject
    {
        try {
            $file = new SplFileObject($filePath, 'r');
            $file->setFlags(SplFileObject::READ_CSV);
            return $file;
        } catch (Exception $e) {
            echo __('plugins.importexport.csv.couldNotOpenFile', [
                'filePath' => $filePath,
                'errorMessage' => $e->getMessage(),
            ]) . "\n";
            return null;
        }
    }

    /**
     * Create a new writable SplFileObject for invalid rows from a unique CSV file. Return null if an error occurred.
     */
    public static function createCSVFileInvalidRows(string $sourceDir, string $filename, array $requiredHeaders): ?SplFileObject
    {
        try {
            $invalidRowsFile = new SplFileObject($sourceDir . '/' . $filename, 'a+');
            $invalidRowsFile->fputcsv(array_merge($requiredHeaders, ['error']));

            return $invalidRowsFile;
        } catch (Exception $e) {
            echo $e->getMessage() . "\n\n";
            echo __('plugins.importexport.csv.couldNotCreateFile', ['filename' => $sourceDir . '/' . $filename]) . "\n";
            return null;
        }
    }

    /**
     * Add a new row on the invalid csv file
     */
    public static function processFailedRow(
        SplFileObject &$invalidRowsCsvFile,
        array $fields,
        int $rowSize,
        string $reason,
        int &$failedRows
    ): void {
        $invalidRowsCsvFile->fputcsv(array_merge(array_pad($fields, $rowSize, null), [$reason]));
        ++$failedRows;
    }
}
