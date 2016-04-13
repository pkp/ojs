{**
 * templates/frontend/pages/article.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to view an article with all of it's details.
 *
 * @uses $article Article This article
 * @uses $issue Issue The issue this article is assigned to
 * @uses $section Section The journal section this article is assigned to
 * @uses $ccLicenseBadge @todo
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$article->getLocalizedTitle()|escape}

<div class="page page_article">
	{if $section}
		{include file="frontend/components/breadcrumbs_article.tpl" currentTitle=$section->getLocalizedTitle()}
	{else}
		{include file="frontend/components/breadcrumbs_article.tpl" currentTitleKey="article.article"}
	{/if}

	{if $galley}
		<h1 class="page_title">{$article->getLocalizedTitle()|escape}</h1>

		{translate key="article.view.interstitial" galleyUrl=$fileUrl}
		<ul class="galleys_links">
			{foreach from=$galley->getLatestGalleyFiles() item=galleyFile}
				<li>
                    <a class="obj_galley_link" href="{url op="download" path=$article->getBestArticleId($currentJournal)|to_array:$galley->getBestGalleyId($currentJournal):$galleyFile->getFileId() escape=false}">{$galleyFile->getLocalizedName()|escape}</a>
                </li>
			{/foreach}
		</ul>
	{else}
		{* Show article overview *}
		{include file="frontend/objects/article_details.tpl"}

		{* Display a legend describing the open/restricted access icons *}
		{if $article->getGalleys()}
			{include file="frontend/components/accessLegend.tpl"}
		{/if}
	{/if}

	{* Copyright and licensing *}
	{* @todo has not been tested *}
	{if $currentJournal->getSetting('includeCopyrightStatement')}
		<div class="article_copyright">
			{translate key="submission.copyrightStatement" copyrightYear=$article->getCopyrightYear()|escape copyrightHolder=$article->getLocalizedCopyrightHolder()|escape}
		</div>
	{/if}

	{if $currentJournal->getSetting('includeLicense') && $ccLicenseBadge}
		<div class="article_license">
			{$ccLicenseBadge}
		</div>
	{/if}

	{call_hook name="Templates::Article::Footer::PageFooter"}
	{if $pageFooter}
		{$pageFooter}
	{/if}

</div><!-- .page -->

{include file="common/frontend/footer.tpl"}
