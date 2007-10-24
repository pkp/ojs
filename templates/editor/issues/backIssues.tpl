{**
 * backIssues.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Listings of back issues
 *
 * $Id$
 *}
{assign var="pageTitle" value="editor.issues.backIssues"}
{url|assign:"currentUrl" page="editor" op="backIssues"}
{include file="common/header.tpl"}

<ul class="menu">
        <li><a href="{url op="createIssue"}">{translate key="editor.navigation.createIssue"}</a></li>
        <li><a href="{url op="futureIssues"}">{translate key="editor.navigation.futureIssues"}</a></li>
        <li class="current"><a href="{url op="backIssues"}">{translate key="editor.navigation.issueArchive"}</a></li>
</ul>

<br/>

{if $usesCustomOrdering}
	{url|assign:"resetUrl" op="resetIssueOrder"}
	<p>{translate key="editor.issues.resetIssueOrder" url=$resetUrl}</p>
{/if}

<a name="issues"></a>

<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="60%">{translate key="issue.issue"}</td>
		<td width="15%">{translate key="editor.issues.published"}</td>
		<td width="15%">{translate key="editor.issues.numArticles"}</td>
		<td width="5%">{translate key="common.order"}</td>
		<td width="5%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>

	{assign var=pos value=1}
	{iterate from=issues item=issue}
	<tr valign="top">
		<td><a href="{url op="issueToc" path=$issue->getIssueId()}" class="action">{$issue->getIssueIdentification()|escape}</a></td>
		<td>{$issue->getDatePublished()|date_format:"$dateFormatShort"}</td>
		<td>{$issue->getNumArticles()}</td>
		<td>{if $pos != 1}<a href="{url op="moveIssue" path=$issue->getIssueId() d=u newPos=$pos-1}" class="plain">&uarr;</a>{else}&uarr;{/if} {if $pos != $issues->getCount()}<a href="{url op="moveIssue" path=$issue->getIssueId() d=d newPos=$pos+1}" class="plain">&darr;</a>{else}&darr;{/if}</td>
		<td align="right"><a href="{url op="removeIssue" path=$issue->getIssueId()}" onclick="return confirm('{translate|escape:"jsparam" key="editor.issues.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="5" class="{if $issues->eof()}end{/if}separator">&nbsp;</td>
	</tr>
	{assign var=pos value=$pos+1}
{/iterate}
{if $issues->wasEmpty()}
	<tr>
		<td colspan="5" class="nodata">{translate key="issue.noIssues"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$issues}</td>
		<td colspan="3" align="right">{page_links anchor="issues" name="issues" iterator=$issues}</td>
	</tr>
{/if}
</table>

<form action="{url op="setCurrentIssue"}" method="post">
	{translate key="journal.currentIssue"}&nbsp;&nbsp;
	<select name="issueId" class="selectMenu">
		<option value="">{translate key="common.none"}</option>
		{html_options options=$allIssues|truncate:40:"..." selected=$currentIssueId}
	</select>
	<input type="submit" value="{translate key="common.record"}" class="button defaultButton" />
</form>

{include file="common/footer.tpl"}
