{**
 * templates/frontend/pages/searchAuthorDetails.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Index of published submissions by author.
 *
 *}
{strip}
{assign var="pageTitle" value="search.authorDetails"}
{include file="frontend/components/header.tpl"}
{/strip}
<div id="authorDetails">
<h3>{$authorName|escape}{if $affiliation}, {$affiliation|escape}{/if}{if $country}, {$country|escape}{/if}</h3>
<ul>
{foreach from=$submissions item=article}
	{assign var=issueId value=$article->getCurrentPublication()->getData('issueId')}
	{assign var=issue value=$issues[$issueId]}
	{assign var=issueUnavailable value=$issuesUnavailable.$issueId}
	{assign var=sectionId value=$article->getCurrentPublication()->getData('sectionId')}
	{assign var=journalId value=$article->getData('contextId')}
	{assign var=journal value=$journals[$journalId]}
	{assign var=section value=$sections[$sectionId]}
	{if $issue->getPublished() && $section && $journal}
	<li>

		<em><a href="{url journal=$journal->getPath() page="issue" op="view" path=$issue->getBestIssueId()}">{$journal->getLocalizedName()|escape} {$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a> - {$section->getLocalizedTitle()|escape}</em><br />
		{$article->getLocalizedTitle()|strip_unsafe_html}<br/>
		<a href="{url journal=$journal->getPath() page="article" op="view" path=$article->getBestId()}" class="file">{if $article->getCurrentPublication()->getData('abstract')}{translate key="article.abstract"}{else}{translate key="article.details"}{/if}</a>
		{if (!$issueUnavailable || $article->getCurrentPublication()->getData('accessStatus') == $smarty.const.ARTICLE_ACCESS_OPEN)}
		{foreach from=$article->getGalleys() item=galley name=galleyList}
			&nbsp;<a href="{url journal=$journal->getPath() page="article" op="view" path=$article->getBestId()|to_array:$galley->getBestGalleyId()}" class="file">{$galley->getGalleyLabel()|escape}</a>
		{/foreach}
		{/if}
	</li>
	{/if}
{/foreach}
</ul>
</div>
{include file="frontend/components/footer.tpl"}

