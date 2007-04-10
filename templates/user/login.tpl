{**
 * login.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User login form.
 *
 * $Id$
 *}

{assign var="pageTitle" value="user.login"}
{assign var="helpTopicId" value="user.registerAndProfile"}
{include file="common/header.tpl"}

{if $loginMessage}
	<span class="instruct">{translate key="$loginMessage"}</span>
	<br />
	<br />
{/if}

{if $error}
	<span class="formError">{translate key="$error" reason=$reason}</span>
	<br />
	<br />
{/if}

<form name="login" action="{url page="login" op="signIn"}" method="post">
<input type="hidden" name="source" value="{$source|escape}" />
<table class="data">
<tr>
	<td class="label"><label for="loginUsername">{translate key="user.username"}</label></td>
	<td class="value"><input type="text" id="loginUsername" name="username" value="{$username|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
<tr>
	<td class="label"><label for="loginPassword">{translate key="user.password"}</label></td>
	<td class="value"><input type="password" id="loginPassword" name="password" value="{$password|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
{if $showRemember}
<tr valign="middle">
	<td></td>
	<td class="value"><input type="checkbox" id="loginRemember" name="remember" value="1"{if $remember} checked="checked"{/if} /> <label for="loginRemember">{translate key="user.login.rememberUsernameAndPassword"}</label></td>
</tr>
{/if}
<tr>
	<td></td>
	<td><input type="submit" value="{translate key="user.login"}" class="button" /></td>
</tr>
</table>

<p>
&#187; <a href="{url page="user" op="register" source=$source|escape}">{translate key="user.login.registerNewAccount"}</a><br />
&#187; <a href="{url page="login" op="lostPassword"}">{translate key="user.login.forgotPassword"}</a>
</p>

<script type="text/javascript">
<!--
	document.login.{if $username}password{else}username{/if}.focus();
// -->
</script>
</form>

{include file="common/footer.tpl"}
