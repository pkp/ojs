<?php
/**
 * @defgroup plugins_articleGalleys_pdfArticleGalley PDF Article Galley Plugin
 */

/**
 * @file plugins/articleGalleys/pdfArticleGalley/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_articleGalleys_pdfArticleGalley
 * @brief Wrapper for pdf article galley plugin.
 *
 */

require_once('PdfArticleGalleyPlugin.inc.php');

return new PdfArticleGalleyPlugin();

?>
