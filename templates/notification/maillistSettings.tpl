{**
 * index.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Displays the notification settings page and unchecks  
 *
 *}
{strip}
{assign var="pageTitle" value="notification.mailList"}
{include file="common/header.tpl"}
{/strip}

<p><span class="instruct">{translate key="notification.unsubscribeDescription"}</span></p>
<br />

{if $error}
	<p><span class="formError">{translate key="$error"}</span></p>
{/if}

{if $success}
	<p>{translate key="$success"}</p>
{/if}

<form id="notificationSettings" method="post" action="{url op="unsubscribeMailList"}">
<table class="data" width="100%">
	<tr valign="top">
		<td class="label" width="5%">{translate key="email.email"}</td>
		<td class="value" width="45%"><input type="text" name="email" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label" width="5%">{translate key="user.password"}</td>
		<td class="value" width="45%"><input type="text" name="password" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="5%">&nbsp;</td>
		<td><p><input type="submit" value="{translate key="form.submit"}" class="button defaultButton" /></p></td>
	</tr>
</table>

</form>

{include file="common/footer.tpl"}

