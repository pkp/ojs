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
{assign var="pageId" value="editor.issues.backIssues"}
{include file="common/header.tpl"}

<form id="backIssues" method="post" action="{$requestPageUrl}/updateBackIssues">

<table width="100%" class="listing">
	<tr class="heading" valign="bottom">
		<td width="12%">{translate key="editor.issues.published"}</td>
		<td width="20%">{translate key="issue.issue"}</td>
		<td width="60%">{translate key="article.authors"}</td>
		<td width="8%">{translate key="common.remove"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator"></td>
	</tr>
	
	{foreach from=$issues item=issue name="issues"}
	<tr valign="top">
		{assign var="issueId" value=$issue->getIssueId()}
		<td>{$issue->getDatePublished()|date_format:"$dateFormatShort"}</td>
		<td><a href="{$requestPageUrl}/issueToc/{$issueId}">{$issue->getIssueIdentification()}</a></td>
		<td>{$issue->getAuthorString(true)|truncate:60:"..."}</td>
		<td><input name="select[]" type="checkbox" value="{$issueId}" /></td>
	</tr>
	<tr>
		<td colspan="4" class="{if $smarty.foreach.issues.last}end{/if}separator"></td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="4" class="nodata">{translate key="issue.noIssues"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator"></td>
	</tr>
	{/foreach}
</table>

<p><input type="submit" value="{translate key="common.saveChanges"}" class="button defaultButton" /></p>

</form>

{include file="common/footer.tpl"}
