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

<div id="content">

<div id="contentMain">

	<form id="backIssues" method="post" onsubmit="return confirm('{translate|escape:"javascript" key="editor.issues.applySelectionChanges"}')">

	<div id="contentHeader">
		<table>
			<tr>
				<td>&nbsp;</td>
			</tr>
		</table>
	</div>

	<div id="hitlistHeader">
		<table>
			<tr>
				<td width="12%" align="center">{translate key="editor.issues.published}</td>
				<td width="22%" align="center">{translate key="editor.issues.issue"}</td>
				<td width="57%">{translate key="editor.issues.authors"}</td>
				<td width="8%" align="center">{translate key="common.select"}</td>
			</tr>
		</table>
	</div>

	<div id="hitlist">
		{foreach from=$issues item=issue}
		<div id="record">
			<table>
				{assign var="issueId" value=$issue->getIssueId()}
				{assign var="onclick" value="onclick=\"javascript:loadUrl('$requestPageUrl/issueManagement/issueToc/$issueId');\""}
				<tr class="{cycle name="cycle1" values="row,rowAlt"}">
					<td width="12%" align="center" {$onclick}>{$issue->getDatePublished()|date_format:"$dateFormatShort"}</td>
					<td width="22%" align="center" {$onclick}>{translate key="editor.issues.vol"}&nbsp;{$issue->getVolume()},&nbsp;{translate key="editor.issues.no"}&nbsp;{$issue->getNumber()}&nbsp;({$issue->getYear()})</td>
					<td width="57%" {$onclick}>
						<div>
						{foreach from=$issueAuthors[$issueId] item=author name=issueAuthorList}
							{$author}{if !$smarty.foreach.issueAuthorList.last},{/if}
						{/foreach}
						</div>									
					</td>
					<td width="8%" align="center"><input name="select[]" type="checkbox" value="{$issue->getIssueId()}" class="optionCheckBox" onclick="javascript:markRow(this,'selectedRow','{cycle name="cycle2" values="row,rowAlt"}');" /></td>
				</tr>
			</table>
		</div>
		{foreachelse}
		<div id="record">
			<table>
				<tr class="row">
					<td align="center"><span class="boldText">{translate key="$noResults"}</span></td>
				</tr>
			</table>
		</div>
		{/foreach}
	</div>

	<div id="hitlistFooter">
		<table>
			<tr>
				<td width="100%" align="right"><a href="javascript:checkAll('backIssues', 'optionCheckBox', true, 'selectedRow', 'selectedRow');">{translate key="common.selectAll"}</a>&nbsp;|&nbsp;<a href="javascript:checkAll('backIssues', 'optionCheckBox', false, 'row', 'rowAlt');">{translate key="common.selectNone"}</a>&nbsp;|&nbsp;<select name="selectOptions" onchange="javascript:changeActionAndSubmit(this.form, '{$requestPageUrl}/updateBackIssues/' + this.options[this.selectedIndex].value, this.options[this.selectedIndex].value);" size="1">{html_options options=$selectOptions selected=0}</select></td>
			</tr>
		</table>
	</div>

	</form>

</div>

</div>

{include file="common/footer.tpl"}
