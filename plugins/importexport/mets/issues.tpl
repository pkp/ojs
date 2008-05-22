{**
 * issues.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of issues to potentially export
 *
 * $Id$
 *}

{assign var="pageTitle" value="plugins.importexport.METSExport.export.selectIssue"}
{assign var="pageCrumbTitle" value="plugins.importexport.METSExport.export.selectIssue"}
{include file="common/header.tpl"}

<script type="text/javascript">
{literal}
<!--


function selectAll() {
        document.issues.selButton.value = "Unselect All";
        document.issues.selButton.attributes["onclick"].value = "javascript:unSelectAll();";
	var elements = document.issues.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].name == 'issueId[]') {
			elements[i].checked = true;
		}
	}
}
function unSelectAll() {
        document.issues.selButton.value = "Select All";
        document.issues.selButton.attributes["onclick"].value  = "javascript:selectAll();";
	var elements = document.issues.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].name == 'issueId[]') {
			elements[i].checked = false;
		}
	}
}
function SubmitIfAnyIsChecked() {
	var elements = document.issues.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].name == 'issueId[]') {
			if(elements[i].checked){ 
                            document.issues.submit();
                            return true;
                         }
		}
	}
        alert("No Issues selected");
        return false;
}
// -->
{/literal}
</script>

<form action="{plugin_url path="exportIssues"}" method="post" name="issues">

<h3>{translate key="plugins.importexport.METSExport.settings"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="30%" class="label" align="right">{translate key="plugins.importexport.METSExport.settings.FLocat"}</td>
		<td width="70%" class="value"><input type="radio" name="contentWrapper" id="contentWrapper" value="FLocat" checked /></td>
	</tr>
	<tr valign="top">
		<td width="30%" class="label" align="right">{translate key="plugins.importexport.METSExport.settings.FContent"}</td>
		<td width="70%" class="value"><input type="radio" name="contentWrapper" id="contentWrapper" value="FContent" /></td>
	</tr>
	<tr>
		<td colspan="2"><div class="separator">&nbsp;</div></td>
	</tr>
	<tr valign="top">
		<td width="30%" class="label" align="right">{translate key="plugins.importexport.METSExport.settings.organization"}</td>
		<td width="70%" class="value">
		<input type="text" name="organization" id="organization" value="{$organization|escape}" size="50" maxlength="50" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="30%" class="label" align="right">{translate key="plugins.importexport.METSExport.settings.preservationLevel"}</td>
		<td width="70%" class="value">
		<input type="text" name="preservationLevel" id="preservationLevel" value="1" size="2" maxlength="1" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="30%" class="label" align="right">{translate key="plugins.importexport.METSExport.settings.exportSuppFiles"}</td>
		<td width="70%" class="value"><input type="checkbox" name="exportSuppFiles" id="exportSuppFiles" value="on" /></td>
	</tr>
</table>

<br/>

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
		<td>{$issue->getIssueIdentification()|escape}</td>
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

<p><input type="button" value="{translate key="common.export"}" class="button defaultButton" onclick="SubmitIfAnyIsChecked();return false;"/>&nbsp;<input type="button" ID="selButton" value="Select All" class="button" onclick="javascript:selectAll();" /></p>
</form>

{include file="common/footer.tpl"}
