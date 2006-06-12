{**
 * issues.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of issues to potentially export
 *
 * $Id$
 *}

{assign var="pageTitle" value="plugins.importexport.crossref.export.selectIssue"}
{assign var="pageCrumbTitle" value="plugins.importexport.crossref.export.selectIssue"}
{include file="common/header.tpl"}

<script type="text/javascript">
{literal}
<!--
function toggleChecked() {
	var elements = document.issues.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].name = 'issueId[]') {
			elements[i].checked = !elements[i].checked;
		}
	}
}
// -->
{/literal}
</script>

<br/>

<form action="{plugin_url path="exportIssues"}" method="post" name="issues">
<table width="100%" class="listing">
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">&nbsp;</td>
		<td width="60%">{translate key="issue.issue"}</td>
		<td width="15%">{translate key="editor.issues.published"}</td>
		<td width="15%">{translate key="editor.issues.numArticles"}</td>
		<td width="5%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td colspan="5" class="headseparator">&nbsp;</td>
	</tr>
	
	{iterate from=issues item=issue}
	<tr valign="top">
		<td><input type="checkbox" name="issueId[]" value="{$issue->getIssueId()}"/></td>
		<td><a href="{url page="issue" op="issueToc" path=$issue->getIssueId()}" class="action">{$issue->getIssueIdentification()|escape}</a></td>
		<td>{$issue->getDatePublished()|date_format:"$dateFormatShort"}</td>
		<td>{$issue->getNumArticles()}</td>
		<td align="right"><a href="{plugin_url path="exportIssue"|to_array:$issue->getIssueId()}" class="action">{translate key="common.export"}</a></td>
	</tr>
	<tr>
		<td colspan="5" class="{if $issues->eof()}end{/if}separator">&nbsp;</td>
	</tr>
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
		<td colspan="3" align="right">{page_links name="issues" iterator=$issues}</td>
	</tr>
{/if}
</table>
<p><input type="submit" value="{translate key="common.export"}" class="button defaultButton"/>&nbsp;<input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" /></p>
</form>

{include file="common/footer.tpl"}
