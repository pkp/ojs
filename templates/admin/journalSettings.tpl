{**
 * journalSettings.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Basic journal settings under site administration.
 *
 * $Id$
 *}

{assign var="pageTitle" value="admin.journals.journalSettings"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{$pageUrl}/admin/settings">{translate key="admin.siteSettings"}</a></li>
	<li><a href="{$pageUrl}/admin/journals">{translate key="admin.hostedJournals"}</a></li>
	<li><a href="{$pageUrl}/admin/languages">{translate key="common.languages"}</a></li>
</ul>

<ul class="menu">
	<li><a href="{$pageUrl}/admin/systemInfo">{translate key="admin.systemInformation"}</a></li>
	<li><a href="{$pageUrl}/admin/expireSessions" onclick="return confirm('{translate|escape:"javascript" key="admin.confirmExpireSessions"}')">{translate key="admin.expireSessions"}</a></li>
	<li><a href="{$pageUrl}/admin/clearTemplateCache" onclick="return confirm('{translate|escape:"javascript" key="admin.confirmClearTemplateCache"}')">{translate key="admin.clearTemplateCache"}</a></li>
</ul>

<br/>

<form method="post" action="{$pageUrl}/admin/updateJournal">
{if $journalId}
<input type="hidden" name="journalId" value="{$journalId}" />
{/if}

{include file="common/formErrors.tpl"}

{if not $journalId}
<p><span class="instruct">{translate key="admin.journals.createInstructions"}</span></p>
{/if}

<table class="data" width="100%">
<tr valign="top">
	<td class="label">{fieldLabel name="title" key="manager.setup.journalTitle"}</td>
	<td class="value"><input type="text" id="title" name="title" value="{$title|escape}" size="40" maxlength="120" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="title" key="journal.path"}</td>
	<td class="value"><input type="text" id="path" name="path" value="{$path|escape}" size="16" maxlength="32" class="textField" /></td>
</tr>
<tr valign="top">
	<td></td>
	<td class="value"><span class="instruct">{translate key="admin.journals.urlWillBe" path="$indexUrl"}</span></td>
</tr>
<tr valign="top">
	<td></td>
	<td class="value">
		<input type="checkbox" name="enabled" value="1"{if $enabled} checked="checked"{/if} />&nbsp;&nbsp;
		<span class="instruct">{translate key="admin.journals.enableJournalInstructions"}</span>
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/admin/journals'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
