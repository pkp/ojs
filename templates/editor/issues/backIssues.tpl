{**
 * backIssues.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Listings of back issues
 *
 * $Id$
 *}

{assign var="pageTitle" value="editor.issues.backIssues"}
{assign var="currentUrl" value="$pageUrl/editor/backIssues"}
{include file="common/header.tpl"}

<ul class="menu">
        <li><a href="{$pageUrl}/editor/createIssue">{translate key="editor.navigation.createIssue"}</a></li>
        <li><a href="{$pageUrl}/editor/schedulingQueue">{translate key="common.queue.short.submissionsInScheduling"}</a></li>
        <li><a href="{$pageUrl}/editor/futureIssues">{translate key="editor.navigation.futureIssues"}</a></li>
        <li class="current"><a href="{$pageUrl}/editor/backIssues">{translate key="editor.navigation.issueArchive"}</a></li>
</ul>
<br/>

<form method="post" action="{$requestPageUrl}/updateBackIssues">

<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="65%">{translate key="issue.issue"}</td>
		<td width="15%">{translate key="editor.issues.published"}</td>
		<td width="15%">{translate key="editor.issues.numArticles"}</td>
		<td width="5%">{translate key="common.remove"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	
	{foreach from=$issues item=issue name="issues"}
	<tr valign="top">
		<td><a href="{$requestPageUrl}/issueToc/{$issue->getIssueId()}" class="action">{$issue->getIssueIdentification()}</a></td>
		<td>{$issue->getDatePublished()|date_format:"$dateFormatShort"}</td>
		<td>{$issue->getNumArticles()}</td>
		<td><input name="select[]" type="checkbox" value="{$issue->getIssueId()}" /></td>
	</tr>
	<tr>
		<td colspan="4" class="{if $smarty.foreach.issues.last}end{/if}separator">&nbsp;</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="4" class="nodata">{translate key="issue.noIssues"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	</tr>
	{/foreach}
</table>

<input type="submit" value="{translate key="common.saveChanges"}" class="button defaultButton" />

</form>

{include file="common/footer.tpl"}
