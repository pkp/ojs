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

{assign var="pageTitle" value="manager.emails.editEmail"}
{include file="common/header.tpl"}

<br/>
<form method="post" action="{$pageUrl}/manager/updateEmail">
<input type="hidden" name="emailId" value="{$emailId|escape}" />
<input type="hidden" name="journalId" value="{$journalId|escape}" />
<input type="hidden" name="emailKey" value="{$emailKey|escape}" />

{include file="common/formErrors.tpl"}
{foreach from=$supportedLocales item=localeName key=localeKey}
<h3>{translate key="manager.emails.emailTemplate"} ({$localeName|escape})</h3>
<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="subject" key="email.subject"}</td>
		<td width="80%" class="value"><input type="text" name="subject[{$localeKey}]" id="subject[{$localeKey}]" value="{$subject.$localeKey|escape}" size="75" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="body" key="email.body"}</td>
		<td class="value"><textarea name="body[{$localeKey}]" id="body[{$localeKey}]" cols="75" rows="20" class="textArea">{$body.$localeKey|escape}</textarea></td>
	</tr>
</table>
{foreachelse}
<h3>{translate key="manager.emails.emailTemplate"}</h3>
<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="subject" key="email.subject"}</td>
		<td width="80%" class="value"><input type="text" name="subject[{$currentLocale}]" id="subject[{$currentLocale}]" value="{$subject.$currentLocale|escape}" size="75" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="body" key="email.body"}</td>
		<td class="value"><textarea name="body[{$currentLocale}]" id="body[{$currentLocale}]" cols="75" rows="20" class="textArea">{$body.$currentLocale|escape}</textarea></td>
	</tr>
</table>
{/foreach}

{if $canDisable}
<p><input type="checkbox" name="enabled" id="emailEnabled" value="1"{if $enabled} checked="checked"{/if} /> <label for="emailEnabled">{translate key="manager.emails.enabled"}</label></p>
{/if}

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/manager/emails'" /></p>
</form>

{include file="common/footer.tpl"}
