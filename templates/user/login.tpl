{**
 * login.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User login form.
 *
 * $Id$
 *}

{assign var="pageTitle" value="user.login"}
{include file="common/header.tpl"}

<form name="login" action="{$pageUrl}/login/signIn" method="post">
<div class="form">
{if $error}
	<span class="formError">{translate key="$error"}</span>
	<br /><br />
{/if}

<table class="form">
<tr>
	<td class="formLabel">{translate key="user.username"}:</td>
	<td class="formField"><input type="text" name="username" value="{$username|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{translate key="user.password"}:</td>
	<td class="formField"><input type="password" name="password" value="{$password|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
{if $showRemember}
<tr valign="middle">
	<td class="formLabel"><input type="checkbox" name="remember" value="1"{if $remember} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="user.login.rememberusernameAndPassword"}</td>
</tr>
{/if}
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="user.login"}" class="formButton" /></td>
</tr>
</table>

&#187; <a href="{$pageUrl}/user/register">{translate key="user.login.registerNewAccount}</a>
</div>

<script type="text/javascript">document.login.{if $username}password{else}username{/if}.focus();</script>
</form>

{include file="common/footer.tpl"}
