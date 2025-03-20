{**
 * plugins/generic/htmlArticleGalley/display.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Embedded viewing of a HTML galley.
 *
 * @hook Templates::Common::Footer::PageFooter []
 *}
<!DOCTYPE html>
<html lang="{$currentLocale|replace:"_":"-"}" xml:lang="{$currentLocale|replace:"_":"-"}">
{capture assign="pageTitleTranslated"}{translate key="article.pageTitle" title=$article->getCurrentPublication()->getLocalizedFullTitle(null, 'html')|strip_unsafe_html}{/capture}
{include file="frontend/components/headerHead.tpl"}
<body class="pkp_page_{$requestedPage|escape} pkp_op_{$requestedOp|escape}">

	{* Header wrapper *}
	<header class="header_view">

		{capture assign="articleUrl"}{url page="articles" op="view" path=$article->getBestId()}{/capture}

		<a href="{$articleUrl}" class="return">
			<span class="pkp_screen_reader">
				{translate key="article.return"}
			</span>
		</a>

		<a href="{$articleUrl}" class="title">
			{$article->getCurrentPublication()->getLocalizedTitle(null, 'html')|strip_unsafe_html}
		</a>
	</header>

	<div id="htmlContainer" class="galley_view{if !$isLatestPublication} galley_view_with_notice{/if}" style="overflow:visible;-webkit-overflow-scrolling:touch">
		{if !$isLatestPublication}
			<div class="galley_view_notice">
				<div class="galley_view_notice_message" role="alert">
					{translate key="submission.outdatedVersion" datePublished=$galleyPublication->getData('datePublished')|date_format:$dateFormatLong urlRecentVersion=$articleUrl}
				</div>
			</div>
			{capture assign="htmlUrl"}
				{url page="articles" op="download" path=$article->getBestId()|to_array:'version':$galleyPublication->getId():$galley->getBestGalleyId():$submissionFile->getId() inline=true}
			{/capture}
		{else}
			{capture assign="htmlUrl"}
				{url page="articles" op="download" path=$article->getBestId()|to_array:$galley->getBestGalleyId():$submissionFile->getId() inline=true}
			{/capture}
		{/if}
		<iframe name="htmlFrame" src="{$htmlUrl}" title="{translate key="submission.representationOfTitle" representation=$galley->getLabel() title=$galleyPublication->getLocalizedFullTitle(null, 'html')|strip_unsafe_html}" allowfullscreen webkitallowfullscreen></iframe>
	</div>
	{call_hook name="Templates::Common::Footer::PageFooter"}
</body>
</html>
