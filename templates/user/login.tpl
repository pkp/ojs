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
{assign var="helpTopicId" value="user.registerAndProfile"}
{include file="common/header.tpl"}

{if $error}
	<span class="formError">{translate key="$error" reason=$reason}</span>
	<br />
	<br />
{/if}

<form name="login" action="{$pageUrl}/login/signIn" method="post">
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
	<td><input type="submit" value="{translate key="user.signIn"}" class="button" /></td>
</tr>
</table>
</form>

<p>
&#187; <a href="{$pageUrl}/user/register">{translate key="user.login.registerNewAccount"}</a><br />
&#187; <a href="{$pageUrl}/login/lostPassword">{translate key="user.login.forgotPassword"}</a>
</p>

<script type="text/javascript">
<!--
	document.login.{if $username}password{else}username{/if}.focus();
// -->
</script>
</form>

{include file="common/footer.tpl"}
