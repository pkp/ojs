{**
 * issueToc.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the issue's table of contents
 *
 * $Id$
 *}
{strip}
{if not $noIssue}
	{assign var="pageTitleTranslated" value=$issue->getIssueIdentification()|escape}
	{assign var="pageCrumbTitleTranslated" value=$issue->getIssueIdentification(false,true)|escape}
{else}
	{assign var="pageTitle" value="editor.issues.noLiveIssues"}
	{assign var="pageCrumbTitle" value="editor.issues.noLiveIssues"}
{/if}
{include file="common/header.tpl"}
{/strip}

{if !$isLayoutEditor}{* Layout Editors can also access this page. *}
	<ul class="menu">
		<li><a href="{url op="createIssue"}">{translate key="editor.navigation.createIssue"}</a></li>
		<li{if $unpublished} class="current"{/if}><a href="{url op="futureIssues"}">{translate key="editor.navigation.futureIssues"}</a></li>
		<li{if !$unpublished} class="current"{/if}><a href="{url op="backIssues"}">{translate key="editor.navigation.issueArchive"}</a></li>
	</ul>
{/if}

{if not $noIssue}
<br />

<form action="#">
{translate key="issue.issue"}: <select name="issue" class="selectMenu" onchange="if(this.options[this.selectedIndex].value > 0) location.href='{url op="issueToc" path="ISSUE_ID" escape=false}'.replace('ISSUE_ID', this.options[this.selectedIndex].value)" size="1">{html_options options=$issueOptions|truncate:40:"..." selected=$issueId}</select>
</form>

<div class="separator"></div>

<ul class="menu">
	<li class="current"><a href="{url op="issueToc" path=$issueId}">{translate key="issue.toc"}</a></li>
	<li><a href="{url op="issueData" path=$issueId}">{translate key="editor.issues.issueData"}</a></li>
	{if $unpublished}<li><a href="{url page="issue" op="view" path=$issue->getBestIssueId()}">{translate key="editor.issues.previewIssue"}</a></li>{/if}
</ul>

<h3>{translate key="issue.toc"}</h3>
{url|assign:"url" op="resetSectionOrder" path=$issueId}
{if $customSectionOrderingExists}{translate key="editor.issues.resetSectionOrder" url=$url}<br/>{/if}
<form method="post" action="{url op="updateIssueToc" path=$issueId}" onsubmit="return confirm('{translate|escape:"jsparam" key="editor.issues.saveChanges"}')">

{assign var=numCols value=6}
{if ($issueAccess == 2 && $enableSubscriptions)}{assign var=numCols value=$numCols+1}{/if}
{if $enablePublicArticleId}{assign var=numCols value=$numCols+1}{/if}
{if $enablePageNumber}{assign var=numCols value=$numCols+1}{/if}

{foreach from=$sections item=section}
<h4>{$section[1]}{if $section[4]}<a href="{url op="moveSectionToc" path=$issueId d=u newPos=$section[4] sectionId=$section[0]}" class="plain">&uarr;</a>{else}&uarr;{/if} {if $section[5]}<a href="{url op="moveSectionToc" path=$issueId d=d newPos=$section[5] sectionId=$section[0]}" class="plain">&darr;</a>{else}&darr;{/if}</h4>

<table width="100%" class="listing">
	<tr>
		<td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="10%" colspan="2">{translate key="editor.issues.order"}</td>
		<td width="15%">{translate key="article.authors"}</td>
		<td>{translate key="article.title"}</td>
		{if ($issueAccess == 2 && $enableSubscriptions)}<td width="10%">{translate key="editor.issues.access"}</td>{/if}
		{if $enablePublicArticleId}<td width="7%">{translate key="editor.issues.publicId"}</td>{/if}
		{if $enablePageNumber}<td width="7%">{translate key="editor.issues.pages"}</td>{/if}
		<td width="5%">{translate key="common.remove"}</td>
		<td width="5%">{translate key="editor.issues.proofed"}</td>
	</tr>
	<tr>
		<td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td>
	</tr>

	{assign var="articleSeq" value=0}
	{foreach from=$section[2] item=article name="currSection"}

	{assign var="articleSeq" value=$articleSeq+1}
	{assign var="articleId" value=$article->getArticleID()}
	<tr>
		<td>{$articleSeq|escape}.</td>
		<td><a href="{url op="moveArticleToc" path=$issueId d=u sectionId=$section[0] pubId=$article->getPubId()}" class="plain">&uarr;</a>&nbsp;<a href="{url op="moveArticleToc" path=$issueId d=d sectionId=$section[0] pubId=$article->getPubId()}" class="plain">&darr;</a></td>
		<td>
			{foreach from=$article->getAuthors() item=author name=authorList}
				{$author->getLastName()|escape}{if !$smarty.foreach.authorList.last},{/if}
			{/foreach}
		</td>
		<td>{if !$isLayoutEditor}<a href="{url op="submission" path=$articleId}" class="action">{/if}{$article->getArticleTitle()|strip_unsafe_html|truncate:60:"..."}{if !$isLayoutEditor}</a>{/if}</td>
		{if (($issueAccess == 2) && $enableSubscriptions)}
		<td><select name="accessStatus[{$article->getPubId()}]" size="1" class="selectMenu">{html_options options=$accessOptions selected=$article->getAccessStatus()}</select></td>
		{/if}
		{if $enablePublicArticleId}
		<td><input type="text" name="publishedArticles[{$article->getArticleId()}]" value="{$article->getPublicArticleId()|escape}" size="7" maxlength="255" class="textField" /></td>
		{/if}
		{if $enablePageNumber}<td><input type="text" name="pages[{$article->getArticleId()}]" value="{$article->getPages()|escape}" size="7" maxlength="255" class="textField" /></td>{/if}
		<td><input type="checkbox" name="remove[{$article->getArticleId()}]" value="{$article->getPubId()}" /></td>
		<td>
			{if in_array($article->getArticleId(), $proofedArticleIds)}
				{icon name="checked"}
			{else}
				{icon name="unchecked"}
			{/if}
		</td>
	</tr>
	<tr>
		<td colspan="{$numCols|escape}" class="{if $smarty.foreach.currSection.last}end{/if}separator">&nbsp;</td>
	</tr>

	{/foreach}
</table>
{foreachelse}
<p><em>{translate key="editor.issues.noArticles"}</em></p>

<div class="separator"></div>
{/foreach}

<input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if $unpublished && !$isLayoutEditor}<input type="button" value="{translate key="editor.issues.publishIssue"}" onclick="confirmAction('{url op="publishIssue" path=$issueId}', '{translate|escape:"jsparam" key="editor.issues.confirmPublish"}')" class="button" />{/if}

</form>

{/if}

{include file="common/footer.tpl"}
