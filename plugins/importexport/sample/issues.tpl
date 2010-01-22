{**
 * issues.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of issues to potentially export
 *
 * $Id$
 *}
{assign var="pageTitle" value="plugins.importexport.sample.selectIssue.long"}
{assign var="pageCrumbTitle" value="plugins.importexport.sample.selectIssue.short"}
{include file="common/header.tpl"}

<br/>

<a name="issues"></a>

<table width="100%" class="listing">
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="65%">{translate key="issue.issue"}</td>
		<td width="15%">{translate key="editor.issues.published"}</td>
		<td width="15%">{translate key="editor.issues.numArticles"}</td>
		<td width="5%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="4" class="headseparator">&nbsp;</td>
	</tr>
	
	{iterate from=issues item=issue}
	<tr valign="top">
		<td><a href="{url page="issue" op="issueToc" path=$issue->getIssueId()}" class="action">{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a></td>
		<td>{$issue->getDatePublished()|date_format:"$dateFormatShort"}</td>
		<td>{$issue->getNumArticles()}</td>
		<td align="right"><a href="{plugin_url path="exportIssue"|to_array:$issue->getIssueId()}" class="action">{translate key="common.export"}</a></td>
	</tr>
	<tr>
		<td colspan="4" class="{if $issues->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $issues->wasEmpty()}
	<tr>
		<td colspan="4" class="nodata">{translate key="issue.noIssues"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="1" align="left">{page_info iterator=$issues}</td>
		<td colspan="3" align="right">{page_links anchor="issues" name="issues" iterator=$issues}</td>
	</tr>
{/if}
</table>

{include file="common/footer.tpl"}
