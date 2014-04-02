{**
 * plugins/importexport/duracloud/issues.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of issues to potentially import
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.duracloud.selectIssue"}
{assign var="pageCrumbTitle" value="plugins.importexport.duracloud.selectIssue"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
<!--
function toggleChecked() {
	var elements = document.getElementsByName("issueId[]");
	for (var i=0; i < elements.length; i++) {
			elements[i].checked = !elements[i].checked;
	}
}
// -->
{/literal}
</script>

<br/>

<div id="issues">
<form action="{plugin_url path="importIssues"}" method="post" id="issues">
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

{foreach name=issues from=$issues key=key item=issue}
	<tr valign="top">
		<td><input type="checkbox" name="contentId[]" value="{$key|escape}"/></td>
		<td>{$issue.identification|strip_unsafe_html|nl2br}</a></td>
		<td>{$issue.date_published|date_format:"$dateFormatShort"|default:"&mdash;"}</td>
		<td>{$issue.num_articles|escape}</td>
		<td align="right"><a href="{plugin_url path="importIssue"|to_array:$key}" class="action">{translate key="common.import"}</a></td>
	</tr>
	<tr>
		<td colspan="5" class="{if $smarty.foreach.issues.last}end{/if}separator">&nbsp;</td>
	</tr>
{foreachelse}
	<tr>
		<td colspan="5" class="nodata">{translate key="issue.noIssues"}</td>
	</tr>
	<tr>
		<td colspan="5" class="endseparator">&nbsp;</td>
	</tr>
{/foreach}
</table>
<p><input type="submit" value="{translate key="common.import"}" class="button defaultButton"/>&nbsp;<input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" /></p>
</form>
</div>
{include file="common/footer.tpl"}
