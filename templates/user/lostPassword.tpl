{**
 * lostPassword.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Password reset form.
 *
 * $Id$
 *}

{assign var="pageTitle" value="user.login.resetPassword"}
{include file="common/header.tpl"}

<form name="reset" action="{$pageUrl}/login/requestResetPassword" method="post">
<div class="form">
{translate key="user.login.resetPasswordInstructions"}<br /><br />

{if $error}
	<span class="formError">{translate key="$error"}</span>
	<br /><br />
{/if}

<table class="form">
<tr>
	<td class="formLabel">{translate key="user.email"}:</td>
	<td class="formField"><input type="text" name="email" value="{$username|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="user.login.resetPassword"}" class="formButton" /></td>
</tr>
</table>

&#187; <a href="{$pageUrl}/user/register">{translate key="user.login.registerNewAccount}</a><br />
&#187; <a href="{$pageUrl}/user/lostPassword">{translate key="user.login.forgotPassword}</a>
</div>

<script type="text/javascript">document.reset.email.focus();</script>
</form>

{include file="common/footer.tpl"}
