{**
 * templates/frontend/pages/article.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
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

<div class="page page_article">
	{if $section}
		{include file="frontend/components/breadcrumbs_article.tpl" currentTitle=$section->getLocalizedTitle()}
	{else}
		{include file="frontend/components/breadcrumbs_article.tpl" currentTitleKey="article.article"}
	{/if}

	{* Show article overview *}
	{include file="frontend/objects/article_details.tpl"}

	{* Display article versions *}
	{if $versioningEnabled}
		{include file="frontend/objects/article_versions.tpl"}
	{/if}

	{call_hook name="Templates::Article::Footer::PageFooter"}

</div><!-- .page -->

{include file="frontend/components/footer.tpl"}
