{**
 * loginChangePassword.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to change a user's password in order to login.
 *
 * $Id$
 *}

{assign var="pageTitle" value="user.changePassword"}
{url|assign:"currentUrl" page="login" op="changePassword"}
{include file="common/header.tpl"}

<form method="post" action="{url page="login" op="savePassword"}">

{include file="common/formErrors.tpl"}

<p><span class="instruct">{translate key="user.login.changePasswordInstructions"}</span></p>

<table class="data" width="100%">
<tr valign="top">
	<td class="label">{fieldLabel name="username" required="true" key="user.username"}</td>
	<td class="value"><input type="text" name="username" value="{$username|escape}" id="username" size="20" maxlength="32" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="oldPassword" required="true" key="user.profile.oldPassword"}</td>
	<td class="value"><input type="password" name="oldPassword" value="{$oldPassword|escape}" id="oldPassword" size="20" maxlength="32" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="password" required="true" key="user.profile.newPassword"}</td>
	<td class="value"><input type="password" id="password" name="password" value="{$password|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
<tr valign="top">
	<td></td>
	<td class="value"><span class="instruct">{translate key="user.register.passwordLengthRestriction" length=$minPasswordLength}</span></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="password2" required="true" key="user.profile.repeatNewPassword"}</td>
	<td class="value"><input type="password" name="password2" value="{$password2|escape}" id="password2" size="20" maxlength="32" class="textField" /></td>
</tr>
</table>
<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="login" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>


{include file="common/footer.tpl"}
