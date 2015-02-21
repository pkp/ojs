{**
 * templates/user/register.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User registration form.
 *
 *}
{strip}
{assign var="pageTitle" value="user.register"}
{include file="common/header.tpl"}
{/strip}

<form id="registerForm" method="post" action="{url op="registerUser"}">

<p>{translate key="user.register.completeForm"}</p>

{if !$implicitAuth}
	{if !$existingUser}
		{url|assign:"url" page="user" op="register" existingUser=1}
		<p>{translate key="user.register.alreadyRegisteredOtherJournal" registerUrl=$url}</p>
	{else}
		{url|assign:"url" page="user" op="register"}
		<p>{translate key="user.register.notAlreadyRegisteredOtherJournal" registerUrl=$url}</p>
		<input type="hidden" name="existingUser" value="1"/>
	{/if}

	<h3>{translate key="user.profile"}</h3>

	{include file="common/formErrors.tpl"}

	{if $existingUser}
		<p>{translate key="user.register.loginToRegister"}</p>
	{/if}
{/if}{* !$implicitAuth *}

{if $source}
	<input type="hidden" name="source" value="{$source|escape}" />
{/if}

<table class="data" width="100%">
{if count($formLocales) > 1 && !$existingUser}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"userRegisterUrl" page="user" op="register" escape=false}
			{form_language_chooser form="register" url=$userRegisterUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}{* count($formLocales) > 1 && !$existingUser *}

{if !$implicitAuth}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="username" required="true" key="user.username"}</td>
		<td width="80%" class="value"><input type="text" name="username" value="{$username|escape}" id="username" size="20" maxlength="32" class="textField" /></td>
	</tr>
	{if !$existingUser}
	<tr valign="top">
		<td></td>
		<td class="instruct">{translate key="user.register.usernameRestriction"}</td>
	</tr>
	{/if}{* !$existingUser *}

	<tr valign="top">
		<td class="label">{fieldLabel name="password" required="true" key="user.password"}</td>
		<td class="value"><input type="password" name="password" value="{$password|escape}" id="password" size="20" class="textField" /></td>
	</tr>

	{if !$existingUser}
		<tr valign="top">
			<td></td>
			<td class="instruct">{translate key="user.register.passwordLengthRestriction" length=$minPasswordLength}</td>
		</tr>
		<tr valign="top">
			<td class="label">{fieldLabel name="password2" required="true" key="user.repeatPassword"}</td>
			<td class="value"><input type="password" name="password2" id="password2" value="{$password2|escape}" size="20" class="textField" /></td>
		</tr>

		{if $captchaEnabled}
			<tr>
				{if $reCaptchaEnabled}
				<td class="label" valign="top">{fieldLabel name="recaptcha_challenge_field" required="true" key="common.captchaField"}</td>
				<td class="value">
					{$reCaptchaHtml}
				</td>
				{else}
				<td class="label" valign="top">{fieldLabel name="captcha" required="true" key="common.captchaField"}</td>
				<td class="value">
					<img src="{url page="user" op="viewCaptcha" path=$captchaId}" alt="{translate key="common.captchaField.altText"}" /><br />
					<span class="instruct">{translate key="common.captchaField.description"}</span><br />
					<input name="captcha" id="captcha" value="" size="20" maxlength="32" class="textField" />
					<input type="hidden" name="captchaId" value="{$captchaId|escape:"quoted"}" />
				</td>
				{/if}
			</tr>
		{/if}{* $captchaEnabled *}

		<tr valign="top">
			<td class="label">{fieldLabel name="salutation" key="user.salutation"}</td>
			<td class="value"><input type="text" name="salutation" id="salutation" value="{$salutation|escape}" size="20" maxlength="40" class="textField" /></td>
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
			<td class="label">{fieldLabel name="initials" key="user.initials"}</td>
			<td class="value"><input type="text" id="initials" name="initials" value="{$initials|escape}" size="5" maxlength="5" class="textField" />&nbsp;&nbsp;{translate key="user.initialsExample"}</td>
		</tr>

		<tr valign="top">
			<td class="label">{fieldLabel name="gender-m" key="user.gender"}</td>
			<td class="value">
				<select name="gender" id="gender" size="1" class="selectMenu">
					{html_options_translate options=$genderOptions selected=$gender}
				</select>
			</td>
		</tr>

		<tr valign="top">
			<td class="label">{fieldLabel name="affiliation" key="user.affiliation"}</td>
			<td class="value">
				<textarea id="affiliation" name="affiliation[{$formLocale|escape}]" rows="5" cols="40" class="textArea">{$affiliation[$formLocale]|escape}</textarea><br/>
				<span class="instruct">{translate key="user.affiliation.description"}</span>
			</td>
		</tr>

		<tr valign="top">
			<td class="label">{fieldLabel name="signature" key="user.signature"}</td>
			<td class="value"><textarea name="signature[{$formLocale|escape}]" id="signature" rows="5" cols="40" class="textArea">{$signature[$formLocale]|escape}</textarea></td>
		</tr>

		<tr valign="top">
			<td class="label">{fieldLabel name="email" required="true" key="user.email"}</td>
			<td class="value"><input type="text" id="email" name="email" value="{$email|escape}" size="30" maxlength="90" class="textField" /> {if $privacyStatement}<a class="action" href="#privacyStatement">{translate key="user.register.privacyStatement"}</a>{/if}</td>
		</tr>

		<tr valign="top">
			<td class="label">{fieldLabel name="confirmEmail" required="true" key="user.confirmEmail"}</td>
			<td class="value"><input type="text" id="confirmEmail" name="confirmEmail" value="{$confirmEmail|escape}" size="30" maxlength="90" class="textField" /></td>
		</tr>

		<tr valign="top">
			<td class="label">{fieldLabel name="orcid" key="user.orcid"}</td>
			<td class="value"><input type="text" id="orcid" name="orcid" value="{$orcid|escape}" size="40" maxlength="255" class="textField" /><br />{translate key="user.orcid.description"}</td>
		</tr>

		<tr valign="top">
			<td class="label">{fieldLabel name="userUrl" key="user.url"}</td>
			<td class="value"><input type="text" id="userUrl" name="userUrl" value="{$userUrl|escape}" size="30" maxlength="255" class="textField" /></td>
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
			<td class="label">{fieldLabel name="country" key="common.country"}</td>
			<td class="value">
				<select name="country" id="country" class="selectMenu">
					<option value=""></option>
					{html_options options=$countries selected=$country}
				</select>
			</td>
		</tr>

		<tr valign="top">
			<td class="label">{fieldLabel name="biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
			<td class="value"><textarea name="biography[{$formLocale|escape}]" id="biography" rows="5" cols="40" class="textArea">{$biography[$formLocale]|escape}</textarea></td>
		</tr>

		<tr valign="top">
			<td class="label">{fieldLabel name="sendPassword" key="user.sendPassword"}</td>
			<td class="value">
				<input type="checkbox" name="sendPassword" id="sendPassword" value="1"{if $sendPassword} checked="checked"{/if} /> <label for="sendPassword">{translate key="user.sendPassword.description"}</label>
			</td>
		</tr>

		{if count($availableLocales) > 1}
			<tr valign="top">
				<td class="label">{translate key="user.workingLanguages"}</td>
				<td class="value">{foreach from=$availableLocales key=localeKey item=localeName}
				<input type="checkbox" name="userLocales[]" id="userLocales-{$localeKey|escape}" value="{$localeKey|escape}"{if in_array($localeKey, $userLocales)} checked="checked"{/if} /> <label for="userLocales-{$localeKey|escape}">{$localeName|escape}</label><br />
				{/foreach}</td>
			</tr>
		{/if}{* count($availableLocales) > 1 *}
	{/if}{* !$existingUser *}
{/if}{* !$implicitAuth *}

{if $allowRegReader || $allowRegReader === null || $allowRegAuthor || $allowRegAuthor === null || $allowRegReviewer || $allowRegReviewer === null || ($currentJournal && $currentJournal->getSetting('publishingMode') == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION && $enableOpenAccessNotification)}
	<tr valign="top">
		<td class="label">{fieldLabel suppressId="true" name="registerAs" key="user.register.registerAs"}</td>
		<td class="value">{if $allowRegReader || $allowRegReader === null}<input type="checkbox" name="registerAsReader" id="registerAsReader" value="1"{if $registerAsReader} checked="checked"{/if} /> <label for="registerAsReader">{translate key="user.role.reader"}</label>: {translate key="user.register.readerDescription"}<br />{/if}
		{if $currentJournal && $currentJournal->getSetting('publishingMode') == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION && $enableOpenAccessNotification}<input type="checkbox" name="openAccessNotification" id="openAccessNotification" value="1"{if $openAccessNotification} checked="checked"{/if} /> <label for="openAccessNotification">{translate key="user.role.reader"}</label>: {translate key="user.register.openAccessNotificationDescription"}<br />{/if}
		{if $allowRegAuthor || $allowRegAuthor === null}<input type="checkbox" name="registerAsAuthor" id="registerAsAuthor" value="1"{if $registerAsAuthor} checked="checked"{/if} /> <label for="registerAsAuthor">{translate key="user.role.author"}</label>: {translate key="user.register.authorDescription"}<br />{/if}
		{if $allowRegReviewer || $allowRegReviewer === null}<input type="checkbox" name="registerAsReviewer" id="registerAsReviewer" value="1"{if $registerAsReviewer} checked="checked"{/if} /> <label for="registerAsReviewer">{translate key="user.role.reviewer"}</label>: {if $existingUser}{translate key="user.register.reviewerDescriptionNoInterests"}{else}{translate key="user.register.reviewerDescription"}{/if}
		<br /><div id="reviewerInterestsContainer" style="margin-left:25px;">
			<label class="desc">{translate key="user.register.reviewerInterests"}</label>
			{include file="form/interestsInput.tpl" FBV_interestsKeywords=$interestsKeywords FBV_interestsTextOnly=$interestsTextOnly}
		</div>
		</td>
		{/if}
	</tr>
{/if}

</table>

<br />
<p><input type="submit" value="{translate key="user.register"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="index" escape=false}'" /></p>

{if ! $implicitAuth}
	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
{/if}{* !$implicitAuth *}

<div id="privacyStatement">
{if $privacyStatement}
	<h3>{translate key="user.register.privacyStatement"}</h3>
	<p>{$privacyStatement|nl2br}</p>
{/if}
</div>

</form>

{include file="common/footer.tpl"}

