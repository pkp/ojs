{**
 * lostPassword.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Password reset form.
 *
 * $Id$
 *}
{assign var="pageTitle" value="user.login.resetPassword"}
{include file="common/header.tpl"}

<form name="reset" action="{url page="login" op="requestResetPassword"}" method="post">
<p><span class="instruct">{translate key="user.login.resetPasswordInstructions"}</span></p>

{if $error}
	<p><span class="formError">{translate key="$error"}</span></p>
{/if}

<table class="data" width="100%">
<tr valign="top">
	<td class="label" width="25%">{translate key="user.login.registeredEmail"}</td>
	<td class="value" width="75%"><input type="text" name="email" value="{$username|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
</table>

<p><input type="submit" value="{translate key="user.login.resetPassword"}" class="button defaultButton" /></p>

&#187; <a href="{url page="user" op="register"}">{translate key="user.login.registerNewAccount"}</a>

<script type="text/javascript">
<!--
	document.reset.email.focus();
// -->
</script>
</form>

{include file="common/footer.tpl"}
