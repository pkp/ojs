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

{assign var="pageTitleTranslated" value=$issue->getIssueIdentification()}
{assign var="pageCrumbTitleTranslated" value=$issue->getIssueIdentification(false,true)}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$pageUrl}/editor/createIssue">{translate key="editor.navigation.createIssue"}</a></li>
	<li><a href="{$pageUrl}/editor/schedulingQueue">{translate key="editor.navigation.submissionsInScheduling"}</a></li>
	<li{if $unpublished} class="current"{/if}><a href="{$pageUrl}/editor/futureIssues">{translate key="editor.navigation.futureIssues"}</a></li>
	<li{if !$unpublished} class="current"{/if}><a href="{$pageUrl}/editor/backIssues">{translate key="editor.navigation.issueArchive"}</a></li>
</ul>

<br />

<form>
{translate key="issue.issue"}: <select name="issue" class="selectMenu" onchange="if(this.options[this.selectedIndex].value > 0) location.href='{$requestPageUrl}/issueData/'+this.options[this.selectedIndex].value" size="1">{html_options options=$issueOptions selected=$issueId}</select>
</form>

<div class="separator"></div>

<ul class="menu">
	<li><a href="{$requestPageUrl}/issueToc/{$issueId}">{translate key="issue.toc"}</a></li>
	<li class="current"><a href="{$requestPageUrl}/issueData/{$issueId}">{translate key="editor.issues.issueData"}</a></li>
</ul>

<form method="post" action="{$pageUrl}/editor/editIssue/{$issueId}" enctype="multipart/form-data">
<input type="hidden" name="journalId" value="{$journalId}" />
<input type="hidden" name="fileName" value="{$fileName}" />
<input type="hidden" name="originalFileName" value="{$originalFileName}" />
{include file="common/formErrors.tpl"}

<h3>{translate key="editor.issues.identification"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="volume" required="true" key="issue.volume"}</td>
		<td width="80%" class="value"><input type="text" name="volume" id="volume" value="{$volume|escape}" size="5" maxlength="5" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="number" required="true" key="issue.number"}</td>
		<td class="value"><input type="text" name="number" id="number" value="{$number|escape}" size="5" maxlength="5" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="year" required="true" key="issue.year"}</td>
		<td class="value"><input type="text" name="year" id="year" value="{$year|escape}" size="5" maxlength="4" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="labelFormat" key="editor.issues.issueIdentification"}</td>
		<td class="value"><select name="labelFormat" id="labelFormat" class="selectMenu">{html_options options=$labelOptions selected=$labelFormat}</select></td>
	</tr>
	{if $enablePublicIssueId}
	<tr valign="top">
		<td class="label">{fieldLabel name="publicIssueId" key="editor.issues.publicIssueIdentifier"}</td>
		<td class="value"><input type="text" name="publicIssueId" id="publicIssueId" value="{$publicIssueId|escape}" size="20" maxlength="60" class="textField" /></td>
	</tr>
	{/if}
	<tr valign="top">
		<td class="label">{fieldLabel name="title" key="issue.title"}</td>
		<td class="value"><input type="text" name="title" id="title" value="{$title|escape}" size="40" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="description" key="editor.issues.description"}</td>
		<td class="value"><textarea name="description" id="description" cols="40" rows="5" class="textArea">{$description|escape}</textarea></td>
	</tr>
</table>

{if $enableSubscriptions}
<div class="separator"></div>
<h3>{translate key="editor.issues.access"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="accessStatus" key="editor.issues.accessStatus"}</td>
		<td width="80%" class="value"><select name="accessStatus" id="accessStatus" class="selectMenu">{html_options options=$accessOptions selected=$accessStatus}</select></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="openAccessDate" key="editor.issues.accessDate"}</td>
		{if ($Date_Year && $Date_Month && $Date_Day && $Time_Hour && $Time_Minute)} 
			<td class="value">{html_select_date time="$Date_Year-$Date_Month-$Date_Day" end_year="+20" all_extra="class=\"selectMenu\""}&nbsp;at&nbsp;{html_select_time time="$Time_Hour:$Time_Minute" display_seconds=false all_extra="class=\"selectMenu\""}</td>
		{else}
			<td class="value">{html_select_date end_year="+20" all_extra="class=\"selectMenu\""}&nbsp;at&nbsp;{html_select_time display_seconds=false all_extra="class=\"selectMenu\""}</td>
		{/if}
	</tr>
</table>
{/if}

<div class="separator"></div>

<h3>{translate key="editor.issues.cover"}</h3>
<table width="100%" class="data">
	<tr valign="top">
		<td class="label" colspan="2"><input type="checkbox" name="showCoverPage" id="showCoverPage" value="1" {if $showCoverPage} checked="checked"{/if} /> <label for="showCoverPage">{translate key="editor.issues.showCoverPage"}</label></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="coverPage" key="editor.issues.coverPage"}</td>
		<td width="80%" class="value"><input type="file" name="coverPage" id="coverPage" class="uploadField" />&nbsp;{translate key="editor.issues.coverPageInstructions"}<br />{translate key="editor.issues.uploaded"}:&nbsp;{if $fileName}<a href="javascript:openWindow('{$publicFilesDir}/{$fileName}');" class="file">{$originalFileName}</a>&nbsp;<a href="{$pageUrl}/editor/removeCoverPage/{$issueId}" onclick="return confirm('{translate|escape:"javascript" key="editor.issues.removeCoverPage"}')">{translate key="editor.issues.remove"}</a>{else}&mdash;{/if}</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="coverPageDescription" key="editor.issues.coverPageCaption"}</td>
		<td class="value"><textarea name="coverPageDescription" id="coverPageDescription" cols="40" rows="5" class="textArea">{$coverPageDescription|escape}</textarea></td>
	</tr>
</table>

<p><input type="submit" value="{translate key="common.saveChanges"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" onclick="document.location.href='{$pageUrl}/editor/issueManagement/issueData/{$issueId}'" class="button" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
