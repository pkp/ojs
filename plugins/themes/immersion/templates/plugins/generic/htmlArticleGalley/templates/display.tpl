{**
 * plugins/generic/htmlArticleGalley/display.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Embedded viewing of a HTML galley.
 *}
<!DOCTYPE html>
<html lang="{$currentLocale|replace:"_":"-"}" xml:lang="{$currentLocale|replace:"_":"-"}">
{capture assign="pageTitleTranslated"}{translate key="article.pageTitle" title=$article->getLocalizedTitle()|escape}{/capture}
{include file="frontend/components/headerHead.tpl"}
<body class="pkp_page_{$requestedPage|escape} pkp_op_{$requestedOp|escape}">

	{* Header wrapper *}
	<header class="main__header html-galley__header">

		{capture assign="articleUrl"}{url page="article" op="view" path=$article->getBestId()}{/capture}

		<a href="{$articleUrl}" class="return">
			<span class="visually-hidden">
				{translate key="article.return"}
			</span>
		</a>
		{if !$isLatestPublication}
			<div class="title" role="alert">
				{translate key="submission.outdatedVersion"
					datePublished=$galleyPublication->getData('datePublished')|date_format:$dateFormatLong
					urlRecentVersion=$articleUrl
				}
			</div>
			{capture assign="htmlUrl"}
				{url page="article" op="download" path=$article->getBestId()|to_array:'version':$galleyPublication->getId():$galley->getBestGalleyId() inline=true}
			{/capture}
		{else}
			<a href="{url page="article" op="view" path=$article->getBestId()}" class="title">
				{$galleyPublication->getLocalizedTitle()|escape}
			</a>
			{capture assign="htmlUrl"}
				{url page="article" op="download" path=$article->getBestId()|to_array:$galley->getBestGalleyId() inline=true}
			{/capture}
		{/if}
	</header>

<div id="htmlContainer" class="galley_view">
	<iframe id="htmlGalleyFrame" name="htmlFrame" src="{$htmlUrl}" allowfullscreen webkitallowfullscreen></iframe>
</div>
{call_hook name="Templates::Common::Footer::PageFooter"}

</body>
</html>
