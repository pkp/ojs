{**
 * mailTemplate.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Basic journal settings under site administration.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.emails"}
{assign var="currentUrl" value="$pageUrl/manager/emails"}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/manager/updateEmail">
<input type="hidden" name="emailId" value="{$emailId}" />
<input type="hidden" name="journalId" value="{$journalId}" />
<input type="hidden" name="emailKey" value="{$emailKey}" />

<div class="form">
{include file="common/formErrors.tpl"}

{foreach from=$supportedLocales item=localeName key=localeKey}
<div class="formSubSectionTitle">{translate key="manager.emails.emailTemplate"} ({$localeName})</div>
<table class="form">
<tr>
	<td class="formLabel">{formLabel name="subject"}{translate key="email.subject"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="subject[{$localeKey}]" value="{$subject.$localeKey|escape}" size="75" maxlength="120" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="body"}{translate key="email.body"}:{/formLabel}</td>
	<td class="formField"><textarea name="body[{$localeKey}]" cols="75" rows="20" class="textArea">{$body.$localeKey|escape}</textarea></td>
</tr>
</table>
{/foreach}

<table class="form">
{if $canDisable}
<tr>
	<td></td>
	<td class="formField"><input type="checkbox" name="enabled" value="1"{if $enabled} checked="checked"{/if} />{formLabel name="enabled"}{translate key="manager.emails.enabled"}{/formLabel}</td>
</tr>
{/if}
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/manager/emails'" /></td>
</tr>
</table>
</div>
</form>

{include file="common/footer.tpl"}
