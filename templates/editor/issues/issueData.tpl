{**
 * issueData.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for creation and modification of an issue
 *
 * $Id$
 *}
{assign var="pageTitleTranslated" value=$issue->getIssueIdentification()}
{assign var="pageCrumbTitleTranslated" value=$issue->getIssueIdentification(false,true)}
{include file="common/header.tpl"}

{if !$isLayoutEditor}{* Layout Editors can also access this page. *}
	<ul class="menu">
		<li><a href="{url op="createIssue"}">{translate key="editor.navigation.createIssue"}</a></li>
		<li{if $unpublished} class="current"{/if}><a href="{url op="futureIssues"}">{translate key="editor.navigation.futureIssues"}</a></li>
		<li{if !$unpublished} class="current"{/if}><a href="{url op="backIssues"}">{translate key="editor.navigation.issueArchive"}</a></li>
	</ul>
{/if}
<br />

<form action="#">
{translate key="issue.issue"}: <select name="issue" class="selectMenu" onchange="if(this.options[this.selectedIndex].value > 0) location.href='{url op="issueToc" path="ISSUE_ID" escape=false}'.replace('ISSUE_ID', this.options[this.selectedIndex].value)" size="1">{html_options options=$issueOptions selected=$issueId}</select>
</form>

<div class="separator"></div>

<ul class="menu">
	<li><a href="{url op="issueToc" path=$issueId}">{translate key="issue.toc"}</a></li>
	<li class="current"><a href="{url op="issueData" path=$issueId}">{translate key="editor.issues.issueData"}</a></li>
	{if $unpublished}<li><a href="{url page="issue" op="view" path=$issue->getBestIssueId()}">{translate key="editor.issues.previewIssue"}</a></li>{/if}
</ul>

<form name="issue" method="post" action="{url op="editIssue" path=$issueId}" enctype="multipart/form-data">
<input type="hidden" name="fileName[{$formLocale|escape}]" value="{$fileName[$formLocale]|escape}" />
<input type="hidden" name="originalFileName[{$formLocale|escape}]" value="{$originalFileName[$formLocale]|escape}" />
{if $styleFileName}
<input type="hidden" name="styleFileName" value="{$styleFileName|escape}" />
<input type="hidden" name="originalStyleFileName" value="{$originalStyleFileName|escape}" />
{/if}
{include file="common/formErrors.tpl"}

<h3>{translate key="editor.issues.identification"}</h3>
<table width="100%" class="data">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"issueUrl" op="issueData" path=$issueId}
			{form_language_chooser form="issue" url=$issueUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="volume" key="issue.volume"}</td>
		<td width="80%" class="value"><input type="text" name="volume" id="volume" value="{$volume|escape}" size="5" maxlength="5" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="number" key="issue.number"}</td>
		<td class="value"><input type="text" name="number" id="number" value="{$number|escape}" size="5" maxlength="5" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="year" key="issue.year"}</td>
		<td class="value"><input type="text" name="year" id="year" value="{$year|escape}" size="5" maxlength="4" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="labelFormat" key="editor.issues.issueIdentification"}</td>
		<td class="value"><input type="checkbox" name="showVolume" id="showVolume" value="1"{if $showVolume} checked="checked"{/if} /><label for="showVolume"> {translate key="issue.volume"}</label><br /><input type="checkbox" name="showNumber" id="showNumber" value="1"{if $showNumber} checked="checked"{/if} /><label for="showNumber"> {translate key="issue.number"}</label><br /><input type="checkbox" name="showYear" id="showYear" value="1"{if $showYear} checked="checked"{/if} /><label for="showYear"> {translate key="issue.year"}</label><br /><input type="checkbox" name="showTitle" id="showTitle" value="1"{if $showTitle} checked="checked"{/if} /><label for="showTitle"> {translate key="issue.title"}</label></td>
	</tr>
	{if $enablePublicIssueId}
	<tr valign="top">
		<td class="label">{fieldLabel name="publicIssueId" key="editor.issues.publicIssueIdentifier"}</td>
		<td class="value"><input type="text" name="publicIssueId" id="publicIssueId" value="{$publicIssueId|escape}" size="20" maxlength="255" class="textField" /></td>
	</tr>
	{/if}
	<tr valign="top">
		<td class="label">{fieldLabel name="title" key="issue.title"}</td>
		<td class="value"><input type="text" name="title[{$formLocale|escape}]" id="title" value="{$title[$formLocale]|escape}" size="40" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="description" key="editor.issues.description"}</td>
		<td class="value"><textarea name="description[{$formLocale|escape}]" id="description" cols="40" rows="5" class="textArea">{$description[$formLocale]|escape}</textarea></td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.status"}</td>
		<td class="value">
			{if $issue->getDatePublished()}
				{translate key="editor.issues.published"}&nbsp;&nbsp;
				{* Find good values for starting and ending year options *}
				{assign var=publishedYear value=$issue->getDatePublished()|date_format:"%Y"}
				{assign var=currentYear value=$smarty.now|date_format:"%Y"}
				{math|assign:"minYear" equation="min(x,y)-10" x=$publishedYear y=$currentYear}
				{math|assign:"maxYear" equation="max(x,y)+2" x=$publishedYear y=$currentYear}
				{html_select_date prefix="datePublished" time=$datePublished all_extra="class=\"selectMenu\"" start_year=$minYear end_year=$maxYear}
			{else}
				{translate key="editor.issues.unpublished"}
			{/if}

			{if $issue->getDateNotified()}
				<br/>
				{translate key="editor.usersNotified"}&nbsp;&nbsp;
				{$issue->getDateNotified()|date_format:$dateFormatShort}
			{/if}
		</td>
	</tr>
</table>

{if ($enableSubscriptions && !$enableDelayedOpenAccess) || ($enableSubscriptions && $enableDelayedOpenAccess && $issue->getPublished())}
<div class="separator"></div>
<h3>{translate key="editor.issues.access"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="accessStatus" key="editor.issues.accessStatus"}</td>
		<td width="80%" class="value"><select name="accessStatus" id="accessStatus" class="selectMenu">{html_options options=$accessOptions selected=$accessStatus}</select></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="openAccessDate" key="editor.issues.accessDate"}</td>
		{if ($Date_Year && $Date_Month && $Date_Day)} 
			<td class="value">{html_select_date time="$Date_Year-$Date_Month-$Date_Day" end_year="+20" all_extra="class=\"selectMenu\""}</td>
		{else}
			<td class="value">{html_select_date end_year="+20" all_extra="class=\"selectMenu\""}</td>
		{/if}
	</tr>
</table>
{/if}

<div class="separator"></div>

<h3>{translate key="editor.issues.cover"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td class="label" colspan="2"><input type="checkbox" name="showCoverPage[{$formLocale|escape}]" id="showCoverPage" value="1" {if $showCoverPage[$formLocale]} checked="checked"{/if} /> <label for="showCoverPage">{translate key="editor.issues.showCoverPage"}</label></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="coverPage" key="editor.issues.coverPage"}</td>
		<td width="80%" class="value"><input type="file" name="coverPage" id="coverPage" class="uploadField" />&nbsp;&nbsp;{translate key="form.saveToUpload"}<br />{translate key="editor.issues.coverPageInstructions"}<br />{translate key="editor.issues.uploaded"}:&nbsp;{if $fileName[$formLocale] }<a href="javascript:openWindow('{$publicFilesDir}/{$fileName[$formLocale]|escape:"url"}');" class="file">{$originalFileName[$formLocale]}</a>&nbsp;<a href="{url op="removeIssueCoverPage" path=$issueId|to_array:$formLocale}" onclick="return confirm('{translate|escape:"jsparam" key="editor.issues.removeCoverPage"}')">{translate key="editor.issues.remove"}</a>{else}&mdash;{/if}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="coverPageAltText" key="common.altText"}</td>
		<td width="80%" class="value"><input type="text" name="coverPageAltText[{$formLocale|escape}]" value="{$coverPageAltText[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value"><span class="instruct">{translate key="common.altTextInstructions"}</span></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="styleFile" key="editor.issues.styleFile"}</td>
		<td width="80%" class="value"><input type="file" name="styleFile" id="styleFile" class="uploadField" />&nbsp;&nbsp;{translate key="form.saveToUpload"}<br />{translate key="editor.issues.uploaded"}:&nbsp;{if $styleFileName}<a href="javascript:openWindow('{$publicFilesDir}/{$styleFileName|escape}');" class="file">{$originalStyleFileName|escape}</a>&nbsp;<a href="{url op="removeStyleFile" path=$issueId}" onclick="return confirm('{translate|escape:"jsparam" key="editor.issues.removeStyleFile"}')">{translate key="editor.issues.remove"}</a>{else}&mdash;{/if}</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="coverPageDescription" key="editor.issues.coverPageCaption"}</td>
		<td class="value"><textarea name="coverPageDescription[{$formLocale|escape}]" id="coverPageDescription" cols="40" rows="5" class="textArea">{$coverPageDescription[$formLocale]|escape}</textarea></td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" onclick="document.location.href='{url op="issueData" path=$issueId escape=false}'" class="button" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
