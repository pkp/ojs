{**
 * userProfileForm.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User profile form under journal management.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.people"}
{include file="common/header.tpl"}

{if $userCreated}
{translate key="manager.people.userCreatedSuccessfully"}<br /><br />
{/if}

<form method="post" action="{$pageUrl}/manager/updateUser">
{if $userId}
<input type="hidden" name="userId" value="{$userId}" />
{/if}

<div class="form">
<div class="subTitle">{if $userId}{translate key="manager.people.editUser"}{else}{translate key="manager.people.createUser"}{/if}</div>
<br />
{include file="common/formErrors.tpl"}

<table class="form">
{if not $userId}
<tr>	
	<td class="formLabel">{formLabel name="enrollAs"}{translate key="manager.people.enrollUserAs"}:{/formLabel}</td>
	<td class="formField"><select name="enrollAs">
		{html_options_translate options=$roleOptions selected=$enrollAs}
	</select></td>
</tr>
{/if}
<tr>
	<td class="formLabel">{formLabel name="username"}{translate key="user.username"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="username" value="{$username|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="password"}{translate key="user.password"}:{/formLabel}</td>
	<td class="formField"><input type="password" name="password" value="{$password|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="password2"}{translate key="user.register.repeatPassword"}:{/formLabel}</td>
	<td class="formField"><input type="password" name="password2" value="{$password2|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="user.profile.leavePasswordBlank"}</td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="firstName"}{translate key="user.firstName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="firstName" value="{$firstName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="middleName"}{translate key="user.middleName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="middleName" value="{$middleName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="lastName"}{translate key="user.lastName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="lastName" value="{$lastName|escape}" size="20" maxlength="60" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="affiliation"}{translate key="user.affiliation"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="affiliation" value="{$affiliation|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="email"}{translate key="user.email"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="email" value="{$email|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="phone"}{translate key="user.phone"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="phone" value="{$phone|escape}" size="15" maxlength="24" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="fax"}{translate key="user.fax"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="fax" value="{$fax|escape}" size="15" maxlength="24" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="mailingAddress"}{translate key="user.mailingAddress"}:{/formLabel}</td>
	<td class="formField"><textarea name="mailingAddress" rows="3" cols="40" class="textArea">{$mailingAddress|escape}</textarea></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="biography"}{translate key="user.biography"}:{/formLabel}</td>
	<td class="formField"><textarea name="biography" rows="5" cols="40" class="textArea">{$biography|escape}</textarea></td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> {if not $userId}<input type="submit" name="createAnother" value="{translate key="manager.people.saveAndCreateAnotherUser"}" class="formButton" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/manager/people/all'" /></td>
</tr>
</table>
</div>
</form>

{include file="common/footer.tpl"}
