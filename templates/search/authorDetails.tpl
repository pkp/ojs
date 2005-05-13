{**
 * authorDetails.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Index of published articles by author.
 *
 * $Id$
 *}

{assign var="pageTitle" value="search.authorDetails"}
{include file="common/header.tpl"}

<h3>{$lastName}, {$firstName}{if $middleName} {$middleName}{/if}{if $affiliation} {$affiliation}{/if}</h3>
<ul>
{foreach from=$publishedArticles item=article}
	{assign var=issueId value=$article->getIssueId()}
	{assign var=issue value=$issues[$issueId]}
	{assign var=bestIssueId value=$issue->getBestIssueId()}
	{assign var=issueUnavailable value=$issuesUnavailable.$issueId}
	{assign var=sectionId value=$article->getSectionId()}
	{assign var=section value=$sections[$sectionId]}
	{if $issue->getPublished()}
	<li>

		<i><a href="{$pageUrl}/issue/view/{$bestIssueId}">{$issue->getIssueIdentification()}</a> - {$section->getTitle()}</i><br />
		{$article->getArticleTitle()}<br/>
		<a href="{$pageUrl}/article/view/{$article->getBestArticleId()}" class="file">{translate key="issue.abstract"}</a>
		{if (!$issueUnavailable || $article->getAccessStatus())}
		{foreach from=$article->getGalleys() item=galley name=galleyList}
			&nbsp;<a href="{$pageUrl}/article/view/{$article->getBestArticleId()}/{$galley->getGalleyId()}" class="file">{$galley->getLabel()}</a>
		{/foreach}
		{/if}
	</li>
	{/if}
{/foreach}
</ul>

{include file="common/footer.tpl"}
