<?php
/**
 * @defgroup plugins_viewableFiles_pdfArticleGalley PDF Article Galley Plugin
 */

/**
 * @file plugins/viewableFiles/pdfArticleGalley/index.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_viewableFiles_pdfArticleGalley
 * @brief Wrapper for pdf article galley plugin.
 *
 */

require_once('PdfArticleGalleyPlugin.inc.php');

return new PdfArticleGalleyPlugin();

?>
