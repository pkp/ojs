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
 * @uses $journal Journal The journal currently being viewed.
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
		{include file="frontend/components/breadcrumbs_article.tpl" currentTitle=$section->getLocalizedTitle()}
	{else}
		{include file="frontend/components/breadcrumbs_article.tpl" currentTitleKey="article.article"}
	{/if}

	{if $galley}
		<h1 class="page_title">{$article->getLocalizedTitle()|escape}</h1>
		{if $galley->hasFilesPerSubmissionRevision($submissionRevision)}
			{translate key="article.view.interstitial" galleyUrl=$fileUrl}
			<form id='fileDownloadForm'>
				<ul class="galleys_links">
					{foreach from=$galley->getLatestGalleyFiles() item=galleyFile}
						<li>
							{assign var=downloadLink value="file"|to_array:$article->getBestArticleId($currentJournal):$galley->getBestGalleyId($currentJournal):$submissionRevision:$galleyFile->getFileId()}
							<a class="obj_galley_link" href="{url op="download" path=$downloadLink escape=false}">{$galleyFile->getLocalizedName()|escape}</a>
							{assign var=otherRevisions value=$galley->getOtherRevisions($galleyFile->getFileId(), null, $galleyFile->getSubmissionId(), $submissionRevision)}
							{if $otherRevisions && !$galleyFile->getHideFileRevisions()}
							<div class="revisions">
								<span>{translate key="article.revisions"}:</span>
								{fbvElement type="select" from=$otherRevisions class="revisions" id="revisions" name="revisions" translate=false}
								<a href="{url op="download" path=$downloadLink escape=false}">{translate key="common.download"}</a>
							</div>
							{/if}
						</li>
					{/foreach}
				</ul>
			</form>
		{else}
			{translate key="submission.noGalleyFiles"}
		{/if}
	{else}
		{* Show article overview *}
		{include file="frontend/objects/article_details.tpl"}
		
		{if $isPreviousRevision}
		<div class='version_info'>
			<h2>{translate key="submission.linkToRecentRevision"}</h2>
			{assign var=newVersionLink value=$article->getBestArticleId($currentJournal)}
			<a href="{url op="view" path=$newVersionLink escape=false}">{$latestTitle}</a>
		</div>
		{else}
			{if $previousRevisions|@count > 0 && !$hideSubmissionRevisions}
			<div class='version_info'>
				<h2>{translate key="submission.linkToPreviousRevisions"}</h2>
				<ul>
				{foreach from=$previousRevisions item=title key=revision}
					<li><a href="{url op="view" path="article"|to_array:$article->getBestArticleId($currentJournal):$revision escape=false}">{$title}</a></li>
				{/foreach}
				</ul>
			</div>
			{/if}
		{/if}
	{/if}

	{call_hook name="Templates::Article::Footer::PageFooter"}

</div><!-- .page -->

{include file="common/frontend/footer.tpl"}
