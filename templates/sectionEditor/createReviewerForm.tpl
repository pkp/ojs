{**
 * templates/sectionEditor/createReviewerForm.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for editors to create reviewers.
 *
 *}
{strip}
{assign var="pageTitle" value="sectionEditor.review.createReviewer"}
{include file="common/header.tpl"}
{/strip}

<form method="post" id="reviewerForm" action="{url op="createReviewer" path=$articleId|to_array:"create"}">

{include file="common/formErrors.tpl"}

<script type="text/javascript">
{literal}
// <!--

	function generateUsername() {
		var req = makeAsyncRequest();

		if (document.getElementById('reviewerForm').lastName.value == "") {
			alert("{/literal}{translate key="manager.people.mustProvideName"}{literal}");
			return;
		}

		req.onreadystatechange = function() {
			if (req.readyState == 4) {
				document.getElementById('reviewerForm').username.value = req.responseText;
			}
		}
		sendAsyncRequest(req, '{/literal}{url op="suggestUsername" firstName="REPLACE1" lastName="REPLACE2" escape=false}{literal}'.replace('REPLACE1', escape(document.getElementById('reviewerForm').firstName.value)).replace('REPLACE2', escape(document.getElementById('reviewerForm').lastName.value)), null, 'get');
	}


// -->
{/literal}
</script>
<div id="createReviewerForm">
<table width="100%" class="data">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"createReviewerUrl" op="createReviewer" path=$articleId escape=false}
			{form_language_chooser form="reviewerForm" url=$createReviewerUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
	<tr valign="top">
		<td class="label">{fieldLabel name="salutation" key="user.salutation"}</td>
		<td class="value"><input type="text" name="salutation" id="salutation" value="{$salutation|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="firstName" required="true" key="user.firstName"}</td>
		<td class="value"><input type="text" name="firstName" id="firstName" value="{$firstName|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="middleName" key="user.middleName"}</td>
		<td class="value"><input type="text" name="middleName" id="middleName" value="{$middleName|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="lastName" required="true" key="user.lastName"}</td>
		<td class="value"><input type="text" name="lastName" id="lastName" value="{$lastName|escape}" size="20" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="initials" key="user.initials"}</td>
		<td class="value"><input type="text" name="initials" id="initials" value="{$initials|escape}" size="5" maxlength="5" class="textField" />&nbsp;&nbsp;{translate key="user.initialsExample"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="gender" key="user.gender"}</td>
		<td class="value">
			<select name="gender" id="gender" size="1" class="selectMenu">
				{html_options_translate options=$genderOptions selected=$gender}
			</select>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="username" required="true" key="user.username"}</td>
		<td class="value">
			<input type="text" name="username" id="username" value="{$username|escape}" size="20" maxlength="32" class="textField" />&nbsp;&nbsp;<input type="button" class="button" value="{translate key="common.suggest"}" onclick="generateUsername()" />
			<br />
			<span class="instruct">{translate key="user.register.usernameRestriction"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">&nbsp;</td>
		<td class="value"><input type="checkbox" name="sendNotify" id="sendNotify" value="1"{if $sendNotify} checked="checked"{/if} /> <label for="sendNotify">{translate key="manager.people.createUserSendNotify"}</label></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="affiliation" key="user.affiliation"}</td>
		<td class="value">
			<textarea name="affiliation[{$formLocale|escape}]" id="affiliation" rows="5" cols="40" class="textArea">{$affiliation[$formLocale]|escape}</textarea><br/>
			<span class="instruct">{translate key="user.affiliation.description"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="email" required="true" key="user.email"}</td>
		<td class="value"><input type="text" name="email" id="email" value="{$email|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="orcid" key="user.orcid"}</td>
		<td class="value"><input type="text" name="orcid" id="orcid" value="{$orcid|escape}" size="30" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="userUrl" key="user.url"}</td>
		<td class="value"><input type="text" name="userUrl" id="userUrl" value="{$userUrl|escape}" size="30" maxlength="255" class="textField" /></td>
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
		<td class="label">{fieldLabel for="interests" key="user.interests"}</td>
		<td class="value">
			{include file="form/interestsInput.tpl" FBV_interestsKeywords=$interestsKeywords FBV_interestsTextOnly=$interestsTextOnly}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="gossip" key="user.gossip"}</td>
		<td class="value"><textarea name="gossip[{$formLocale|escape}]" id="gossip" rows="3" cols="40" class="textArea">{$gossip[$formLocale]|escape}</textarea></td>
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
	{if count($availableLocales) > 1}
	<tr valign="top">
		<td class="label">{translate key="user.workingLanguages"}</td>
		<td>{foreach from=$availableLocales key=localeKey item=localeName}
			<input type="checkbox" name="userLocales[]" id="userLocales-{$localeKey|escape}" value="{$localeKey|escape}"{if $userLocales && in_array($localeKey, $userLocales)} checked="checked"{/if} /> <label for="userLocales-{$localeKey|escape}">{$localeName|escape}</label><br />
		{/foreach}</td>
	</tr>
	{/if}
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="selectReviewer" path=$articleId escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
</form>

{include file="common/footer.tpl"}

