{**
 * register.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User registration form.
 *
 * $Id$
 *}

{assign var="pageTitle" value="user.register"}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/user/registerUser">
<div class="form">
{if !$existingUser}
{translate key="user.register.alreadyRegisteredOtherJournal" registerUrl="$pageUrl/user/register?existingUser=1"}
{else}
{translate key="user.register.notAlreadyRegisteredOtherJournal" registerUrl="$pageUrl/user/register"}
<input type="hidden" name="existingUser" value="1"/>
{/if}
<br /><br />

<div class="subTitle">{translate key="user.profile"}</div>
<br />
{include file="common/formErrors.tpl"}

<span class="formRequired">{translate key="form.required"}</span>
<br /><br />

{if $existingUser}
{translate key="user.register.loginToRegister"}
<br /><br />
{/if}
	
<table class="form">
<tr>	
	<td class="formLabel">{formLabel name="username" required="true"}{translate key="user.username"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="username" value="{$username|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
{if !$existingUser}
<tr>
	<td></td>
	<td class="formInstructions">{translate key="user.register.usernameRestriction"}</td>
</tr>
{/if}
	
<tr>
	<td class="formLabel">{formLabel name="password" required="true"}{translate key="user.password"}:{/formLabel}</td>
	<td class="formField"><input type="password" name="password" value="{$password|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>

{if !$existingUser}
<tr>
	<td></td>
	<td class="formInstructions">{translate key="user.register.passwordLengthRestriction" length=$minPasswordLength}</td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="password2" required="true"}{translate key="user.register.repeatPassword"}:{/formLabel}</td>
	<td class="formField"><input type="password" name="password2" value="{$password2|escape}" size="20" maxlength="32" class="textField" /></td>
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
	<td class="formField"><input type="text" name="lastName" value="{$lastName|escape}" size="20" maxlength="90" class="textField" /></td>
</tr>

<tr>
	<td class="formLabel">{formLabel name="initials"}{translate key="user.initials"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="initials" value="{$initials|escape}" size="5" maxlength="5" class="textField" /></td>
</tr>
	
<tr>
	<td class="formLabel">{formLabel name="affiliation"}{translate key="user.affiliation"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="affiliation" value="{$affiliation|escape}" size="30" maxlength="255" class="textField" /></td>
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
<tr>
	<td class="formLabel">{translate key="user.workingLanguages"}:</td>
	<td>{foreach from=$availableLocales key=localeKey item=localeName}
		<input type="checkbox" name="userLocales[]" value="{$localeKey}"{if in_array($localeKey, $userLocales)} checked="checked"{/if} /> {$localeName}<br />
	{/foreach}</td>
</tr>
{/if}
{/if}
	
<tr>
	<td class="formLabel">{formLabel name="registerAs"}{translate key="user.register.registerAs"}:{/formLabel}</td>
	<td class="formField">{if $allowRegReader || $allowRegReader === null}<input type="checkbox" name="registerAsReader" value="1"{if $registerAsReader} checked="checked"{/if} /> {translate key="user.role.reader"}: {translate key="user.register.readerDescription"}<br />{/if}
	{if $allowRegAuthor || $allowRegAuthor === null}<input type="checkbox" name="registerAsAuthor" value="1"{if $registerAsAuthor} checked="checked"{/if} /> {translate key="user.role.author"}: {translate key="user.register.authorDescription"}<br />{/if}
	{if $allowRegReviewer || $allowRegReviewer === null}<input type="checkbox" name="registerAsReviewer" value="1"{if $registerAsReviewer} checked="checked"{/if} /> {translate key="user.role.reviewer"}: {translate key="user.register.reviewerDescription"} <input type="text" name="interests" value="{$fax|escape}" size="20" maxlength="255" class="textField" />{/if}</td>
</tr>

<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="user.register"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}'" /></td>
</tr>
</table>

{if $privacyStatement}
<br />
<div class="subTitle">{translate key="user.register.privacyStatement"}</div>
<br />
{$privacyStatement}
{/if}
</div>
</form>

{include file="common/footer.tpl"}
