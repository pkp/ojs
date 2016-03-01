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

<script type="text/javascript">
	$(function() {ldelim}
		$('#fileDownloadForm').pkpHandler(
			'$.pkp.pages.ArticleDownloadHandler'
		);
	{rdelim});
</script>

<div class="page page_article">
	{if $section}
		{include file="frontend/components/breadcrumbs_article.tpl" currentTitle=$section->getLocalizedTitle()|escape}
	{else}
		{include file="frontend/components/breadcrumbs_article.tpl" currentTitleKey="article.article"}
	{/if}

	{if $galley}
		<h1 class="page_title">{$article->getLocalizedTitle()|escape}</h1>

		{translate key="article.view.interstitial" galleyUrl=$fileUrl}
		<form id='fileDownloadForm'>
			<ul class="galleys_links">
				{foreach from=$galley->getLatestGalleyFiles() item=galleyFile}
					<li>
						{assign var=downloadLink value="file"|to_array:$article->getBestArticleId($currentJournal):$galley->getBestGalleyId($currentJournal):$submissionRevision:$galleyFile->getFileId()}
						<a class="obj_galley_link" href="{url op="download" path=$downloadLink escape=false}">{$galleyFile->getLocalizedName()|escape}</a>
						{assign var=otherRevisions value=$galley->getOtherRevisions($galleyFile->getFileId())}
						{if $otherRevisions && !$galleyFile->getData('hideRevisions')}
						<div class="revisions">
							<span>{translate key="article.revisions"}:</span>
							{fbvElement type="select" from=$otherRevisions class="revisions" name="revisions" translate=false}
							<a href={url op="download" path=$downloadLink escape=false}>{translate key="common.download"}</a>
						</div>
						{/if}
					</li>
				{/foreach}
			</ul>
		</form>
	{else}
		{* Show article overview *}
		{include file="frontend/objects/article_details.tpl"}
		
		{if $isPreviousRevision}
		<p>
			<em>
				{translate key="submission.linkToRecentRevision"}<br />
				{assign var=newVersionLink value=$article->getBestArticleId($currentJournal)}
				<a href={url op="view" path=$newVersionLink escape=false}>{url op="view" path=$newVersionLink escape=false}</a>
			</em>
		</p>
		{/if}

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
