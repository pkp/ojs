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
	<li>
		{assign var=issueId value=$article->getIssueId()}
		{assign var=issue value=$issues[$issueId]}
		{assign var=issueUnavailable value=$issuesUnavailable[$issueId]}
		{assign var=sectionId value=$article->getSectionId()}
		{assign var=section value=$sections[$sectionId]}
		<i>{if $issue->getAccessStatus()}<a href="{$pageUrl}/issue/view/{$issue->getIssueId()}">{/if}{$issue->getIssueIdentification()}{if $issue->getAccessStatus()}</a>{/if} - {$section->getTitle()}</i><br />
		{$article->getArticleTitle()}<br/>
		<a href="{$pageUrl}/article/view/{$article->getArticleId()}" class="file">{translate key="issue.abstract"}</a>
		{if (!$issueUnavailable || $issue->getAccessStatus())}
		{foreach from=$article->getGalleys() item=galley name=galleyList}
			&nbsp;<a href="{$pageUrl}/article/{if not $galley->isHtmlGalley()}download/{$article->getArticleId()}/{$galley->getFileId()}{else}view/{$article->getArticleId()}/{$galley->getGalleyId()}{/if}" class="file">{$galley->getLabel()}</a>
		{/foreach}
		{/if}
	</li>
{/foreach}
</ul>

{include file="common/footer.tpl"}
