{**
 * templates/editor/issues/issueData.tpl
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
	{rdelim});
</script>

<form class="pkp_form" id="issueForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.issues.IssueGridHandler" op="updateIssue" issueId=$issueId}">

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

	{fbvFormArea id="identificationSelectionArea" class="border" title="editor.issues.issueIdentification"}
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

<div id="issueId">
<table class="data">
	<tr>
		<td class="label">{translate key="common.status"}</td>
		<td class="value">
			{if $issue && $issue->getPublished()}
				{translate key="editor.issues.published"}&nbsp;&nbsp;
				{* Find good values for starting and ending year options *}
				{assign var=currentYear value=$smarty.now|date_format:"%Y"}
				{if $datePublished}
					{assign var=publishedYear value=$datePublished|date_format:"%Y"}
					{math|assign:"minYear" equation="min(x,y)-10" x=$publishedYear y=$currentYear}
					{math|assign:"maxYear" equation="max(x,y)+2" x=$publishedYear y=$currentYear}
				{else}
					{* No issue publication date info *}
					{math|assign:"minYear" equation="x-10" x=$currentYear}
					{math|assign:"maxYear" equation="x+2" x=$currentYear}
				{/if}
				{html_select_date prefix="datePublished" time=$datePublished|default:"---" all_extra="class=\"selectMenu\"" start_year=$minYear end_year=$maxYear year_empty="-" month_empty="-" day_empty="-"}
			{else}
				{translate key="editor.issues.unpublished"}
			{/if}

			{if $issue && $issue->getDateNotified()}
				<br/>
				{translate key="editor.usersNotified"}&nbsp;&nbsp;
				{$issue->getDateNotified()|date_format:$dateFormatShort}
			{/if}
		</td>
	</tr>
</table>
</div>

{if $currentJournal->getSetting('publishingMode') == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION}
<div class="separator"></div>
<div id="issueAccess">
<h3>{translate key="editor.issues.access"}</h3>
<table class="data">
	<tr>
		<td class="label">{fieldLabel name="accessStatus" key="editor.issues.accessStatus"}</td>
		<td class="value"><select name="accessStatus" id="accessStatus" class="selectMenu">{html_options options=$accessOptions selected=$accessStatus}</select></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="openAccessDate" key="editor.issues.accessDate"}</td>
		<td class="value">
			<input type="checkbox" id="enableOpenAccessDate" name="enableOpenAccessDate" {if $openAccessDate}checked="checked" {/if}onchange="document.getElementById('issue').openAccessDateMonth.disabled=this.checked?false:true;document.getElementById('issue').openAccessDateDay.disabled=this.checked?false:true;document.getElementById('issue').openAccessDateYear.disabled=this.checked?false:true;" />&nbsp;{fieldLabel name="enableOpenAccessDate" key="editor.issues.enableOpenAccessDate"}<br/>
			{if $openAccessDate}
				{html_select_date prefix=openAccessDate time=$openAccessDate end_year="+20" all_extra="class=\"selectMenu\""}
			{else}
				{html_select_date prefix=openAccessDate time=$openAccessDate end_year="+20" all_extra="class=\"selectMenu\" disabled=\"disabled\""}
			{/if}
		</td>
	</tr>
</table>
</div>
{/if}

<div class="separator"></div>

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
<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" onclick="document.location.href='{url op="issueData" path=$issueId escape=false}'" class="button" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>
