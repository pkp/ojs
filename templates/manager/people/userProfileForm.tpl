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

{if not $userId}
{assign var="passwordRequired" value="true"}
{/if}


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

<span class="formRequired">{translate key="form.required"}</span>
<br /><br />

<table class="form">
{if not $userId}
<tr>	
	<td class="formLabel">{formLabel name="enrollAs"}{translate key="manager.people.enrollUserAs"}:{/formLabel}</td>
	<td class="formField"><select name="enrollAs[]" multiple="multiple" size="9">
		{html_options_translate options=$roleOptions selected=$enrollAs}
	</select></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="username" required="true"}{translate key="user.username"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="username" value="{$username|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
{else}
<tr>
	<td class="formLabel">{formLabel name="username"}{translate key="user.username"}:{/formLabel}</td>
	<td class="formField">{$username|escape}</td>
</tr>
{/if}
<tr>
	<td class="formLabel">{formLabel name="password" required=$passwordRequired}{translate key="user.password"}:{/formLabel}</td>
	<td class="formField"><input type="password" name="password" value="{$password|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="password2" required=$passwordRequired}{translate key="user.register.repeatPassword"}:{/formLabel}</td>
	<td class="formField"><input type="password" name="password2" value="{$password2|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
{if $userId}
<tr>
	<td></td>
	<td class="formInstructions">{translate key="user.register.passwordLengthRestriction" length=$minPasswordLength}<br />{translate key="user.profile.leavePasswordBlank"}</td>
</tr>
{else}
<tr>
	<td class="formLabel"><input type="checkbox" name="sendNotify" value="1"{if $sendNotify} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.people.createUserSendNotify"}</td>
</tr>
{/if}
<tr>
	<td class="formLabel"><input type="checkbox" name="mustChangePassword" value="1"{if $mustChangePassword} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="manager.people.userMustChangePassword"}</td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="firstName" required="true"}{translate key="user.firstName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="firstName" value="{$firstName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="middleName"}{translate key="user.middleName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="middleName" value="{$middleName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="lastName" required="true"}{translate key="user.lastName"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="lastName" value="{$lastName|escape}" size="20" maxlength="60" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="affiliation"}{translate key="user.affiliation"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="affiliation" value="{$affiliation|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="email" required="true"}{translate key="user.email"}:{/formLabel}</td>
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
{if $profileLocalesEnabled && count($availableLocales) > 1}
<tr valign="top">
	<td class="formLabel">{translate key="user.workingLanguages"}:</td>
	<td>{foreach from=$availableLocales key=localeKey item=localeName}
		<input type="checkbox" name="userLocales[]" value="{$localeKey}"{if $userLocales && in_array($localeKey, $userLocales)} checked="checked"{/if}>{$localeName}<br />
	{/foreach}</td>
</tr>
{/if}
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> {if not $userId}<input type="submit" name="createAnother" value="{translate key="manager.people.saveAndCreateAnotherUser"}" class="formButton" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/manager/people/all'" /></td>
</tr>
</table>
</div>
</form>

{include file="common/footer.tpl"}
