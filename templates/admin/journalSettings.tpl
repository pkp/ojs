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

<form method="post" action="{$pageUrl}/admin/updateJournal">
{if $journalId}
<input type="hidden" name="journalId" value="{$journalId}" />
{/if}

<div class="form">
{include file="common/formErrors.tpl"}

{if not $journalId}
{translate key="admin.journals.createInstructions"}
<br /><br />
{/if}

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="title"}{translate key="manager.setup.journalTitle"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="title" value="{$title|escape}" size="40" maxlength="120" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="title"}{translate key="admin.journals.path"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="path" value="{$path|escape}" size="16" maxlength="32" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="admin.journals.urlWillBe" path="$indexUrl"}</td>
</tr>
<tr>
	<td class="formLabel"><input type="checkbox" name="enabled" value="1"{if $enabled} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="admin.journals.enableJournalInstructions}</td>
</tr>

<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/admin/journals'" /></td>
</tr>
</table>

</div>
</form>

{include file="common/footer.tpl"}
