{**
 * loginChangePassword.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to change a user's password in order to login.
 *
 * $Id$
 *}

{assign var="pageTitle" value="user.changePassword"}
{assign var="currentUrl" value="$pageUrl/login/changePassword"}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/login/savePassword">

<div class="form">
{include file="common/formErrors.tpl"}

<span class="formRequired">{translate key="form.required"}</span>
<br /><br />

<div class="formSectionDesc">{translate key="user.login.changePasswordInstructions"}</div>

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="username" required="true"}{translate key="user.username"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="username" value="{$username|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="oldPassword" required="true"}{translate key="user.profile.oldPassword"}:{/formLabel}</td>
	<td class="formField"><input type="password" name="oldPassword" value="{$oldPassword|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="password" required="true"}{translate key="user.profile.newPassword"}:{/formLabel}</td>
	<td class="formField"><input type="password" name="password" value="{$password|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="user.register.passwordLengthRestriction" length=$minPasswordLength}</td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="password2" required="true"}{translate key="user.profile.repeatNewPassword"}:{/formLabel}</td>
	<td class="formField"><input type="password" name="password2" value="{$password2|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/login'" /></td>
</tr>
</table>
</div>
</form>


{include file="common/footer.tpl"}
