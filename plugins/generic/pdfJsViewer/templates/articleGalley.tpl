{**
 * plugins/generic/pdfJsViewer/articleGalley.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Embedded viewing of a PDF galley.
 *}
{url|assign:"pdfUrl" op="download" path=$article->getBestArticleId($currentJournal)|to_array:$galley->getBestGalleyId($currentJournal):$galleyFile->getId() escape=false}
{url|assign:"parentUrl" page="article" op="view" path=$article->getBestArticleId($currentJournal)}
{include file="$pluginTemplatePath/display.tpl" title=$article->getLocalizedTitle() parentUrl=$parentUrl pdfUrl=$pdfUrl}
