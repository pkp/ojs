{**
 * issueData.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for creation and modification of an issue
 *
 * $Id$
 *}

<ul id="tabnav" style="border-bottom: none;">
	<li><a href="{$requestPageUrl}/issueManagement/issueToc/{$issueId}">{translate key="editor.issues.toc"}</a></li>
	<li><a href="{$requestPageUrl}/issueManagement/issueData/{$issueId}" class="active">{translate key="editor.issues.issueData"}</a></li>
</ul>

<div id="content">

	<div id="contentMain">

	<div id="contentHeader">
		<table>
			<tr>
				<td>&nbsp;</td>
				<td align="right">{translate key="form.required"}</td>
			</tr>
		</table>
	</div>

	{if $isError}
	<div id="formError">
		{include file="common/formErrors.tpl"}
	</div>
	{/if}

	<form method="post" action="{$pageUrl}/editor/editIssue/{$issueId}" enctype="multipart/form-data">
	<input type="hidden" name="journalId" value="{$journalId}" />

	<div id="form">
		<table>
			<tr>
				<td class="formFieldLabelRequired">{formLabel name="volume" required="true"}{translate key="issue.volume"}{/formLabel}</td>
				<td class="formField"><input type="text" name="volume" value="{$volume|escape}" size="5" maxlength="5" class="textField" /></td>
			</tr>
			<tr>
				<td class="formFieldLabelRequired">{formLabel name="number" required="true"}{translate key="issue.number"}{/formLabel}</td>
				<td class="formField"><input type="text" name="number" value="{$number|escape}" size="5" maxlength="5" class="textField" /></td>
			</tr>
			<tr>
				<td class="formFieldLabelRequired">{formLabel name="year" required="true"}{translate key="editor.issues.year"}{/formLabel}</td>
				<td class="formField"><input type="text" name="year" value="{$year|escape}" size="5" maxlength="4" class="textField" /></td>
			</tr>
			<tr>
				<td class="formFieldLabel">{formLabel name="labelFormat"}{translate key="editor.issues.issueIdentification"}{/formLabel}</td>
				<td class="formField"><select name="labelFormat" size="1" class="selectMenu">{html_options options=$labelOptions selected=$labelFormat}</select></td>
			</tr>
			{if $enablePublicIssueId}
			<tr>
				<td class="formFieldLabel">{formLabel name="publicIssueId"}{translate key="editor.issues.publicIssueIdentifier"}{/formLabel}</td>
				<td class="formField"><input type="text" name="publicIssueId" value="{$publicIssueId|escape}" size="20" maxlength="60" class="textField" /></td>
			</tr>
			{/if}
			<tr>
				<td class="formFieldLabel">{formLabel name="title"}{translate key="issue.title"}{/formLabel}</td>
				<td class="formField"><input type="text" name="title" value="{$title|escape}" size="40" maxlength="120" class="textField" /></td>
			</tr>
			<tr>
				<td class="formFieldLabel">{formLabel name="description"}{translate key="editor.issues.description"}{/formLabel}</td>
				<td class="formField"><textarea name="description" rows="1" cols="50" class="textArea">{$description|escape}</textarea></td>
			</tr>
			{if $enableSubscriptions}
			<tr>
				<td class="formFieldLabel">{formLabel name="accessStatus"}{translate key="editor.issues.accessStatus"}{/formLabel}</td>
				<td class="formField"><select name="accessStatus" size="1" class="selectMenu">{html_options options=$accessOptions selected=$accessStatus}</select></td>
			</tr>
			<tr>
				<td class="formFieldLabel">{formLabel name="openAccessDate"}{translate key="editor.issues.accessDate"}{/formLabel}</td>
				{if ($Date_Year && $Date_Month && $Date_Day && $Time_Hour && $Time_Minute)} 
					<td class="formField">{html_select_date time="$Date_Year-$Date_Month-$Date_Day" end_year="+20"}&nbsp;at&nbsp;{html_select_time time="$Time_Hour:$Time_Minute" display_seconds=false}</td>
				{else}
					<td class="formField">{html_select_date end_year="+20"}&nbsp;at&nbsp;{html_select_time display_seconds=false}</td>
				{/if}
			</tr>
			{/if}
			<tr>
				<td class="formFieldLabel">{formLabel name="coverPage"}{translate key="editor.issues.coverPage"}{/formLabel}</td>
				<td class="formField">
					<input type="file" name="coverPage" class="textField" /><br />{translate key="editor.issues.uploaded"}:&nbsp;{if $fileName}<a href="{$pageUrl}/editor/download/{$issueId}/{$fileName}" class="file">{$originalFileName}</a>&nbsp;<a href="{$pageUrl}/editor/removeCoverPage/{$issueId}" onclick="return confirm('{translate|escape:"javascript" key="editor.issues.removeCoverPage"}')">[{translate key="editor.issues.remove"}]</a>{else}&mdash;{/if}
				</td>
			</tr>
		</table>
	</div>

	<div id="formFooter" align="right">
		<input type="button" value="{translate key="common.cancel"}" onclick="document.location.href='{$pageUrl}/editor/issueManagement/issueData/{$issueId}'" />&nbsp;
		<input type="button" value="{translate key="common.delete"}" onclick="confirmAction('{$pageUrl}/editor/removeIssue/{$issueId}', '{translate|escape:"javascript" key="editor.issues.confirmIssueDelete"}')" />&nbsp;
		<input type="submit" value="{translate key="common.saveChanges"}" />
	</div>
	</form>

	</div>

</div>
