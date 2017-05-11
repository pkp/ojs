{**
 * plugins/generic/lensGalley/articleGalley.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Embedded viewing of a PDF galley.
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$article->getLocalizedTitle()|escape}

<div class="page">
	{url|assign:"xmlUrl" op="download" path=$article->getBestArticleId($currentJournal)|to_array:$galley->getBestGalleyId($currentJournal) escape=false}
	{include file="$pluginTemplatePath/display.tpl" xmlUrl=$xmlUrl}
</div>

{include file="frontend/components/footer.tpl"}
