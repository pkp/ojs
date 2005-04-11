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

<p>{translate key="user.register.completeForm"}</p>

{if !$existingUser}
	<p>{translate key="user.register.alreadyRegisteredOtherJournal" registerUrl="$pageUrl/user/register?existingUser=1"}</p>
{else}
	<p>{translate key="user.register.notAlreadyRegisteredOtherJournal" registerUrl="$pageUrl/user/register"}</p>
	<input type="hidden" name="existingUser" value="1"/>
{/if}

<br />

<h3>{translate key="user.profile"}</h3>
{include file="common/formErrors.tpl"}

{if $existingUser}
<p>{translate key="user.register.loginToRegister"}</p>
{/if}
	
<table class="data" width="100%">
<tr valign="top">	
	<td width="20%" class="label">{fieldLabel name="username" required="true" key="user.username"}</td>
	<td width="80%" class="value"><input type="text" name="username" value="{$username|escape}" id="username" size="20" maxlength="32" class="textField" /></td>
</tr>
{if !$existingUser}
<tr valign="top">
	<td></td>
	<td class="instruct">{translate key="user.register.usernameRestriction"}</td>
</tr>
{/if}
	
<tr valign="top">
	<td class="label">{fieldLabel name="password" required="true" key="user.password"}</td>
	<td class="value"><input type="password" name="password" value="{$password|escape}" id="password" size="20" maxlength="32" class="textField" /></td>
</tr>

{if !$existingUser}
<tr valign="top">
	<td></td>
	<td class="instruct">{translate key="user.register.passwordLengthRestriction" length=$minPasswordLength}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="password2" required="true" key="user.register.repeatPassword"}</td>
	<td class="value"><input type="password" name="password2" id="password2" value="{$password2|escape}" size="20" maxlength="32" class="textField" /></td>
</tr>
	
<tr valign="top">
	<td class="label">{fieldLabel name="firstName" required="true" key="user.firstName"}</td>
	<td class="value"><input type="text" id="firstName" name="firstName" value="{$firstName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
	
<tr valign="top">
	<td class="label">{fieldLabel name="middleName" key="user.middleName"}</td>
	<td class="value"><input type="text" id="middleName" name="middleName" value="{$middleName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
	
<tr valign="top">
	<td class="label">{fieldLabel name="lastName" required="true" key="user.lastName"}</td>
	<td class="value"><input type="text" id="lastName" name="lastName" value="{$lastName|escape}" size="20" maxlength="90" class="textField" /></td>
</tr>

<tr valign="top">
	<td class="label">{fieldLabel name="initials" key="user.initials"}&nbsp;&nbsp;{translate key="user.initialsExample"}</td>
	<td class="value"><input type="text" id="initials" name="initials" value="{$initials|escape}" size="5" maxlength="5" class="textField" /></td>
</tr>
	
<tr valign="top">
	<td class="label">{fieldLabel name="affiliation" key="user.affiliation"}</td>
	<td class="value"><input type="text" id="affiliation" name="affiliation" value="{$affiliation|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
	
<tr valign="top">
	<td class="label">{fieldLabel name="email" required="true" key="user.email"}</td>
	<td class="value"><input type="text" id="email" name="email" value="{$email|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
	
<tr valign="top">
	<td class="label">{fieldLabel name="phone" key="user.phone"}</td>
	<td class="value"><input type="text" name="phone" id="phone" value="{$phone|escape}" size="15" maxlength="24" class="textField" /></td>
</tr>
	
<tr valign="top">
	<td class="label">{fieldLabel name="fax" key="user.fax"}</td>
	<td class="value"><input type="text" name="fax" id="fax" value="{$fax|escape}" size="15" maxlength="24" class="textField" /></td>
</tr>
	
<tr valign="top">
	<td class="label">{fieldLabel name="mailingAddress" key="common.mailingAddress"}</td>
	<td class="value"><textarea name="mailingAddress" id="mailingAddress" rows="3" cols="40" class="textArea">{$mailingAddress|escape}</textarea></td>
</tr>
	
<tr valign="top">
	<td class="label">{fieldLabel name="biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
	<td class="value"><textarea name="biography" id="biography" rows="5" cols="40" class="textArea">{$biography|escape}</textarea></td>
</tr>

{if $profileLocalesEnabled && count($availableLocales) > 1}
<tr valign="top">
	<td class="label">{translate key="user.workingLanguages"}</td>
	<td class="value">{foreach from=$availableLocales key=localeKey item=localeName}
		<input type="checkbox" name="userLocales[]" id="userLocales[{$localeKey}]" value="{$localeKey}"{if in_array($localeKey, $userLocales)} checked="checked"{/if} /> <label for="userLocales[{$localeKey}]">{$localeName}</label><br />
	{/foreach}</td>
</tr>
{/if}
{/if}
	
<tr valign="top">
	<td class="label">{fieldLabel name="registerAs" key="user.register.registerAs"}</td>
	<td class="value">{if $allowRegReader || $allowRegReader === null}<input type="checkbox" name="registerAsReader" id="registerAsReader" value="1"{if $registerAsReader} checked="checked"{/if} /> <label for="registerAsReader">{translate key="user.role.reader"}</label>: {translate key="user.register.readerDescription"}<br />{/if}
	{if $allowRegAuthor || $allowRegAuthor === null}<input type="checkbox" name="registerAsAuthor" id="registerAsAuthor" value="1"{if $registerAsAuthor} checked="checked"{/if} /> <label for="registerAsAuthor">{translate key="user.role.author"}</label>: {translate key="user.register.authorDescription"}<br />{/if}
	{if $allowRegReviewer || $allowRegReviewer === null}<input type="checkbox" name="registerAsReviewer" id="registerAsReviewer" value="1"{if $registerAsReviewer} checked="checked"{/if} /> <label for="registerAsReviewer">{translate key="user.role.reviewer"}</label>: {translate key="user.register.reviewerDescription"} <input type="text" name="interests" value="{$fax|escape}" size="20" maxlength="255" class="textField" />{/if}</td>
</tr>
</table>

<p><input type="submit" value="{translate key="user.register"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{if $privacyStatement}
<br />
<h3>{translate key="user.register.privacyStatement"}</h3>
<p>{$privacyStatement}</p>
{/if}
</form>

{include file="common/footer.tpl"}
