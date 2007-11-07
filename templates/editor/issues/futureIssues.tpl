{**
 * futureIssues.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Listings of future (unpublished) issues.
 *
 * $Id$
 *}
{assign var="pageTitle" value="editor.issues.futureIssues"}
{url|assign:"currentUrl" page="editor" op="issues"}{include file="common/header.tpl"}

<ul class="menu">
        <li><a href="{url op="createIssue"}">{translate key="editor.navigation.createIssue"}</a></li>
        <li class="current"><a href="{url op="futureIssues"}">{translate key="editor.navigation.futureIssues"}</a></li>
        <li><a href="{url op="backIssues"}">{translate key="editor.navigation.issueArchive"}</a></li>
</ul>

<br/>

<a name="issues"></a>

<table width="100%" class="listing">
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="80%">{translate key="issue.issue"}</td>
		<td width="15%">{translate key="editor.issues.numArticles"}</td>
		<td width="5%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="3" class="headseparator">&nbsp;</td>
	</tr>
	{iterate from=issues item=issue}
	<tr valign="top">
		<td><a href="{url op="issueToc" path=$issue->getIssueId()}" class="action">{$issue->getIssueIdentification()|escape}</a></td>
		<td>{$issue->getNumArticles()}</td>
		<td align="right"><a href="{url op="removeIssue" path=$issue->getIssueId()}" onclick="return confirm('{translate|escape:"jsparam" key="editor.issues.confirmDelete"}')" class="action">{translate key="common.delete"}</a></td>
	</tr>
	<tr>
		<td colspan="3" class="{if $issues->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $issues->wasEmpty()}
	<tr>
		<td colspan="3" class="nodata">{translate key="issue.noIssues"}</td>
	</tr>
	<tr>
		<td colspan="3" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td align="left">{page_info iterator=$issues}</td>
		<td colspan="2" align="right">{page_links anchor="issues" name="issues" iterator=$issues}</td>
	</tr>
{/if}
</table>

{include file="common/footer.tpl"}
