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

{if !$isNewTemplate}
	{assign var="pageTitle" value="manager.emails.editEmail"}
{else}
	{assign var="pageTitle" value="manager.emails.createEmail"}
{/if}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/manager/updateEmail">
<input type="hidden" name="emailId" value="{$emailId|escape}" />
<input type="hidden" name="journalId" value="{$journalId|escape}" />
{if !$isNewTemplate}
	<input type="hidden" name="emailKey" value="{$emailKey|escape}" />
{/if}

{if $description}
	<p>{$description|escape}</p>
{/if}

<br/>

{include file="common/formErrors.tpl"}

<table class="data" width="100%">

{if $isNewTemplate}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="emailKey" key="manager.emails.emailKey"}</td>
		<td width="80%" class="value"><input type="text" name="emailKey" value="{$emailKey|escape}" id="emailKey" size="20" maxlength="120" class="textField" /><br/>&nbsp;</td>
	</tr>
{/if}

{foreach from=$supportedLocales item=localeName key=localeKey}
	<tr valign="top"><td colspan="2">
		<h3>{translate key="manager.emails.emailTemplate"} ({$localeName|escape})</h3>
	</td></tr>

	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="subject-{$localeKey}" key="email.subject"}</td>
		<td width="80%" class="value"><input type="text" name="subject[{$localeKey}]" id="subject-{$localeKey}" value="{$subject.$localeKey|escape}" size="70" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="body-{$localeKey}" key="email.body"}</td>
		<td class="value"><textarea name="body[{$localeKey}]" id="body-{$localeKey}" cols="70" rows="20" class="textArea">{$body.$localeKey|escape}</textarea></td>
	</tr>
{foreachelse}
<tr valign="top"><td colspan="2">
	<h3>{translate key="manager.emails.emailTemplate"}</h3>
</td></tr>

	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="subject-{$currentLocale}" key="email.subject"}</td>
		<td width="80%" class="value"><input type="text" name="subject[{$currentLocale}]" id="subject-{$currentLocale}" value="{$subject.$currentLocale|escape}" size="70" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="body-{$currentLocale}" key="email.body"}</td>
		<td class="value"><textarea name="body[{$currentLocale}]" id="body-{$currentLocale}" cols="70" rows="20" class="textArea">{$body.$currentLocale|escape}</textarea></td>
	</tr>
{/foreach}
</table>

{if $canDisable}
<p><input type="checkbox" name="enabled" id="emailEnabled" value="1"{if $enabled} checked="checked"{/if} /> <label for="emailEnabled">{translate key="manager.emails.enabled"}</label></p>
{/if}

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/manager/emails'" /></p>
</form>

{include file="common/footer.tpl"}
