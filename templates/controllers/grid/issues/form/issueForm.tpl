{**
 * templates/controllers/grid/issues/form/issueData.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for creation and modification of an issue
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#issueForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
		$('input[id^="datePublished"]').datepicker({ldelim} dateFormat: 'yy-mm-dd' {rdelim});
		$('input[id^="openAccessDate"]').datepicker({ldelim} dateFormat: 'yy-mm-dd' {rdelim});
	{rdelim});
</script>

<form class="pkp_form" id="issueForm" method="post" action="{url op="updateIssue" issueId=$issueId}">

	{fbvFormArea id="identificationArea" class="border" title="editor.issues.identification"}
		{fbvFormSection}
			{fbvElement type="text" label="issue.volume" id="volume" value=$volume maxlength="40" inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" label="issue.number" id="number" value=$number maxlength="40" inline=true size=$fbvStyles.size.SMALL}
			{fbvElement type="text" label="issue.year" id="year" value=$year maxlength="4" inline=true size=$fbvStyles.size.SMALL}
			{if $enablePublicIssueId}
				{fbvElement type="text" label="editor.issues.publicIssueIdentifier" id="publicIssueId" inline=true value=$publicIssueId size=$fbvStyles.size.SMALL}
			{/if}
		{/fbvFormSection}
		{fbvFormSection}
			{fbvElement type="text" label="issue.title" id="title" value=$title multilingual=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="identificationSelectionArea" class="border" title="editor.issues.issueIdentification" size=$fbvStyles.size.SMALL}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" label="issue.volume" id="showVolume" checked=$showVolume inline=true}
			{fbvElement type="checkbox" label="issue.number" id="showNumber" checked=$showNumber inline=true}
			{fbvElement type="checkbox" label="issue.year" id="showYear" checked=$showYear inline=true}
			{fbvElement type="checkbox" label="issue.title" id="showTitle" checked=$showTitle inline=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="description" title="editor.issues.description"}
		{fbvElement type="textarea" id="description" value=$description multilingual=true rich=true}
	{/fbvFormArea}

	{fbvFormArea id="issueStatus" title="common.status" class="border"}
		{if $issue && $issue->getPublished()}
			{translate key="editor.issues.published"}
			{fbvElement type="text" label="common.date" id="datePublished" value=$datePublished|date_format:"%y-%m-%d" size=$fbvStyles.size.SMALL}
		{else}
			{translate key="editor.issues.unpublished"}
		{/if}

		{if $issue && $issue->getDateNotified()}
			<br/>
			{translate key="editor.usersNotified"}&nbsp;&nbsp;
			{$issue->getDateNotified()|date_format:$dateFormatShort}
		{/if}
	{/fbvFormArea}

	{if $currentJournal->getSetting('publishingMode') == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION}
		{fbvFormArea id="issueAccessArea" title="editor.issues.access" class="border"}
			{fbvFormSection}
				{fbvElement type="select" id="accessStatus" label="editor.issues.accessStatus" from=$accessOptions selected=$accessStatus translate=false size=$fbvStyles.size.SMALL list=true}
			{/fbvFormSection}
			{fbvFormSection label="editor.issues.enableOpenAccessDate"}
				{fbvElement type="checkbox" id="enableOpenAccessDate" checked=$openAccessDate inline=true}
				{fbvElement type="text" label="editor.issues.accessDate" id="openAccessDate" value=$openAccessDate|date_format:"%y-%m-%d" size=$fbvStyles.size.SMALL inline=true}
			{/fbvFormSection}
		{/fbvFormArea}
	{/if}

{foreach from=$pubIdPlugins item=pubIdPlugin}
	{assign var=pubIdMetadataFile value=$pubIdPlugin->getPubIdMetadataFile()}
	{include file="$pubIdMetadataFile" pubObject=$issue}
{/foreach}

{call_hook name="Templates::Editor::Issues::IssueData::AdditionalMetadata"}

<div id="issueCover">

<input type="hidden" name="fileName[{$formLocale|escape}]" value="{$fileName[$formLocale]|escape}" />
<input type="hidden" name="originalFileName[{$formLocale|escape}]" value="{$originalFileName[$formLocale]|escape}" />
{if $styleFileName}
	<input type="hidden" name="styleFileName" value="{$styleFileName|escape}" />
	<input type="hidden" name="originalStyleFileName" value="{$originalStyleFileName|escape}" />
{/if}

<h3>{translate key="editor.issues.cover"}</h3>
<table class="data">
	<tr>
		<td class="label" colspan="2"><input type="checkbox" name="showCoverPage[{$formLocale|escape}]" id="showCoverPage" value="1" {if $showCoverPage[$formLocale]} checked="checked"{/if} /> <label for="showCoverPage">{translate key="editor.issues.showCoverPage"}</label></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="coverPage" key="editor.issues.coverPage"}</td>
		<td class="value"><input type="file" name="coverPage" id="coverPage" class="uploadField" />&nbsp;&nbsp;{translate key="form.saveToUpload"}<br />{translate key="editor.issues.coverPageInstructions"}<br />{translate key="editor.issues.uploaded"}:&nbsp;{if $fileName[$formLocale] }<a href="javascript:openWindow('{$publicFilesDir}/{$fileName[$formLocale]|escape:"url"}');" class="file">{$originalFileName[$formLocale]}</a>&nbsp;<a href="{url op="removeIssueCoverPage" path=$issueId|to_array:$formLocale}" onclick="return confirm('{translate|escape:"jsparam" key="editor.issues.removeCoverPage"}')">{translate key="editor.issues.remove"}</a>{else}&mdash;{/if}</td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="coverPageAltText" key="common.altText"}</td>
		<td class="value"><input type="text" name="coverPageAltText[{$formLocale|escape}]" value="{$coverPageAltText[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="value"><span class="instruct">{translate key="common.altTextInstructions"}</span></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="styleFile" key="editor.issues.styleFile"}</td>
		<td class="value"><input type="file" name="styleFile" id="styleFile" class="uploadField" />&nbsp;&nbsp;{translate key="form.saveToUpload"}<br />{translate key="editor.issues.uploaded"}:&nbsp;{if $styleFileName}<a href="javascript:openWindow('{$publicFilesDir}/{$styleFileName|escape}');" class="file">{$originalStyleFileName|escape}</a>&nbsp;<a href="{url op="removeStyleFile" path=$issueId}" onclick="return confirm('{translate|escape:"jsparam" key="editor.issues.removeStyleFile"}')">{translate key="editor.issues.remove"}</a>{else}&mdash;{/if}</td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="coverPageDescription" key="editor.issues.coverPageCaption"}</td>
		<td class="value"><textarea name="coverPageDescription[{$formLocale|escape}]" id="coverPageDescription" cols="40" rows="5" class="textArea richContent">{$coverPageDescription[$formLocale]|escape}</textarea></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="hideCoverPageArchives" key="editor.issues.coverPageDisplay"}</td>
		<td class="value"><input type="checkbox" name="hideCoverPageArchives[{$formLocale|escape}]" id="hideCoverPageArchives" value="1" {if $hideCoverPageArchives[$formLocale]} checked="checked"{/if} /> <label for="hideCoverPageArchives">{translate key="editor.issues.hideCoverPageArchives"}</label></td>
	</tr>
	<tr>
		<td class="label">&nbsp;</td>
		<td class="value"><input type="checkbox" name="hideCoverPageCover[{$formLocale|escape}]" id="hideCoverPageCover" value="1" {if $hideCoverPageCover[$formLocale]} checked="checked"{/if} /> <label for="hideCoverPageCover">{translate key="editor.issues.hideCoverPageCover"}</label></td>
	</tr>
</table>
</div>

{fbvFormButtons submitText="common.save"}

</form>
