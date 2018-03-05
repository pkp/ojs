{**
 * plugins/generic/browse/templates/searchDetails.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display published articles by browse object (section or identify type)
 *
 *}
{if $enableBrowseBySections}
{assign var=pageTitle value="plugins.generic.browse.search.sectionDetails"}
{else if $enableBrowseByIdentifyTypes}
{assign var=pageTitle value="plugins.generic.browse.search.identifyTypeDetails"}
{/if}
{include file="common/header.tpl"}

<br />

{if $currentJournal}
	{assign var=numCols value=3}
{else}
	{assign var=numCols value=4}
{/if}

<div id="results">
<h3>{$title|escape}</h3>
<table class="listing">
<tr><td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td></tr>
<tr class="heading" valign="bottom">
	{if !$currentJournal}<td>{translate key="context.context"}</td>{/if}
	<td>{translate key="issue.issue"}</td>
	<td width="{if !$currentJournal}60%{else}80%{/if}" colspan="2">{translate key="article.title"}</td>
</tr>
<tr><td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td></tr>

{iterate from=results item=result}
{assign var=publishedArticle value=$result.publishedArticle}
{assign var=article value=$result.article}
{assign var=issue value=$result.issue}
{assign var=issueAvailable value=$result.issueAvailable}
{assign var=journal value=$result.journal}
<tr>
	{if !$currentJournal}<td><a href="{url journal=$journal->getPath()}">{$journal->getLocalizedName()|escape}</a></td>{/if}
	<td>{if $issueAvailable}<a href="{url journal=$journal->getPath() page="issue" op="view" path=$issue->getBestIssueId()}">{/if}{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}{if $issueAvailable}</a>{/if}</td>
	<td>{$article->getLocalizedTitle()|strip_unsafe_html}</td>
	<td align="right">
			<a href="{url journal=$journal->getPath() page="article" op="view" path=$publishedArticle->getBestArticleId()}" class="file">{if $article->getLocalizedAbstract()}{translate key="article.abstract"}{else}{translate key="article.details"}{/if}</a>
		{if $issueAvailable}
		{foreach from=$publishedArticle->getGalleys() item=galley name=galleyList}
			&nbsp;
			<a href="{url journal=$journal->getPath() page="article" op="view" path=$publishedArticle->getBestArticleId()|to_array:$galley->getBestGalleyId()}" class="file">{$galley->getGalleyLabel()|escape}</a>
		{/foreach}
		{/if}
	</td>
</tr>
<tr>
	<td colspan="{$numCols|escape}" style="padding-left: 30px;font-style: italic;">
		{foreach from=$article->getAuthors() item=author name=authorList}
			{$author->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
		{/foreach}
	</td>
</tr>
<tr><td colspan="{$numCols|escape}" class="{if $results->eof()}end{/if}separator">&nbsp;</td></tr>
{/iterate}
{if $results->wasEmpty()}
<tr>
<td colspan="{$numCols|escape}" class="nodata">{translate key="search.noResults"}</td>
</tr>
<tr><td colspan="{$numCols|escape}" class="endseparator">&nbsp;</td></tr>
{else}
	<tr>
		<td {if !$currentJournal}colspan="2" {/if}align="left">{page_info iterator=$results}</td>
		<td colspan="2" align="right">
		{if $enableBrowseBySections}{page_links anchor="results" iterator=$results name="search" sectionId=$sectionId}
		{else if $enableBrowseByIdentifyTypes}{page_links anchor="results" iterator=$results name="search" identifyType=$title}
		{/if}
		</td>
	</tr>
{/if}
</table>
</div>
{include file="common/footer.tpl"}
