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

<form method="post" action="{$formActionUrl}">
{if $hiddenFormParams}
	{foreach from=$hiddenFormParams item=hiddenFormParam key=key}
		<input type="hidden" name="{$key}" value="{$hiddenFormParam}" />
	{/foreach}
{/if}

<div class="form">
{include file="common/formErrors.tpl"}

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="subject"}{translate key="manager.emails.subject"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="subject" value="{$subject|escape}" size="40" maxlength="120" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="body"}{translate key="manager.emails.body"}:{/formLabel}</td>
	<td class="formField"><textarea name="body" cols="40" rows="5" class="textArea">{$body|escape}</textarea></td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="email.send"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/admin/journals'" /></td>
</tr>
</table>
</div>
</form>

{include file="common/footer.tpl"}
