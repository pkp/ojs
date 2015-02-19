<?php
/**
 * @defgroup plugins_viewableFiles_downloadableFileArticleGalley Downloadable File Article Galley Plugin
 */

/**
 * @file plugins/viewableFiles/downloadableFileArticleGalley/index.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_viewableFiles_downloadableFileArticleGalley
 * @brief Wrapper for downloadable file article galley plugin.
 *
 */

require_once('DownloadableFileArticleGalleyPlugin.inc.php');

return new DownloadableFileArticleGalleyPlugin();

?>
