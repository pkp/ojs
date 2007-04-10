{**
 * changePassword.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to change a user's password.
 *
 * $Id$
 *}

{assign var="pageTitle" value="user.changePassword"}
{url|assign:"currentUrl" page="user" op="changePassword"}
{include file="common/header.tpl"}

<form method="post" action="{url op="savePassword"}">

{include file="common/formErrors.tpl"}

<p><span class="instruct">{translate key="user.profile.changePasswordInstructions"}</span></p>

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="oldPassword" key="user.profile.oldPassword"}</td>
	<td width="80%" class="value"><input type="password" name="oldPassword" id="oldPassword" value="{$oldPassword|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="password" key="user.profile.newPassword"}</td>
	<td class="value"><input type="password" name="password" value="{$password|escape}" id="password" size="20" maxlength="32" class="textField" /></td>
</tr>
<tr valign="top">
	<td></td>
	<td><span class="instruct">{translate key="user.register.passwordLengthRestriction" length=$minPasswordLength}</span></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="password2" key="user.profile.repeatNewPassword"}</td>
	<td class="value"><input type="password" name="password2" id="password2" value="{$password2|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="user" escape=false}'" /></p>
</form>

{include file="common/footer.tpl"}
