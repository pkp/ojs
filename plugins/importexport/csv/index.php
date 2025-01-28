<?php

/**
 * @file plugins/importexport/csv/index.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Wrapper for CSV import/export plugin.
 *
 */

namespace PKP\Plugins\ImportExport\CSV;

require_once 'CSVImportExportPlugin.inc.php';

return new CSVImportExportPlugin();
