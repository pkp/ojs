{**
 * userProfileForm.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User profile form under journal management.
 *
 * $Id$
 *
 *	20110803	BLH	Make changes related to updated Enroll New User functionality. 
 *					Note: I had to also have to make changes in templates/sectionEditor/createReviewerForm.tpl
 *					
 *}
{strip}
{url|assign:"currentUrl" op="people" path="all"}
{assign var="pageTitle" value="manager.people"}
{include file="common/header.tpl"}
{/strip}

{literal}
<script type="text/javascript">
	$(document).ready(function(){
	
		$("#existingUserInfo").hide();
		$("#existingUserEnrollmentDone").hide();
		
		$("#interestsTextOnly").hide();
		$("#interests").tagit({
			{/literal}{if $existingInterests}{literal}
			// This is the list of interests in the system used to populate the autocomplete
			availableTags: [{/literal}{foreach name=existingInterests from=$existingInterests item=interest}"{$interest|escape|escape:'javascript'}"{if !$smarty.foreach.existingInterests.last}, {/if}{/foreach}{literal}],{/literal}{/if}
			// This is the list of the user's interests that have already been saved
			{if $interestsKeywords}{literal}currentTags: [{/literal}{foreach name=currentInterests from=$interestsKeywords item=interest}"{$interest|escape|escape:'javascript'}"{if !$smarty.foreach.currentInterests.last}, {/if}{/foreach}{literal}]{/literal}
			{else}{literal}currentTags: []{/literal}{/if}{literal}
		});
	});
</script>
{/literal}

{literal}
<script type="text/javascript">
<!--

// -->
</script>
{/literal}


{if not $userId}
{assign var="passwordRequired" value="true"}

{literal}
<script type="text/javascript">
<!--
	function setGenerateRandom(value) {
		if (value) {
			document.userForm.password.value='********';
			document.userForm.password2.value='********';
			document.userForm.password.disabled=1;
			document.userForm.password2.disabled=1;
			document.userForm.sendNotify.checked=1;
			document.userForm.sendNotify.disabled=1;
		} else {
			document.userForm.password.disabled=0;
			document.userForm.password2.disabled=0;
			document.userForm.sendNotify.disabled=0;
			document.userForm.password.value='';
			document.userForm.password2.value='';
			document.userForm.password.focus();
		}
	}

	function enablePasswordFields() {
		document.userForm.password.disabled=0;
		document.userForm.password2.disabled=0;
	}

	function generateUsername() {
		var req = makeAsyncRequest();

		if (document.userForm.lastName.value == "") {
			alert("{/literal}{translate key="manager.people.mustProvideName"}{literal}");
			return;
		}

		req.onreadystatechange = function() {
			if (req.readyState == 4) {
				document.userForm.username.value = req.responseText;
			}
		}
		sendAsyncRequest(req, '{/literal}{url op="suggestUsername" firstName="REPLACE1" lastName="REPLACE2" escape=false}{literal}'.replace('REPLACE1', escape(document.userForm.firstName.value)).replace('REPLACE2', escape(document.userForm.lastName.value)), null, 'get');
	}

	function checkUsername() {
		var req = makeAsyncRequest();
		
		req.onreadystatechange = function() {
			if (req.readyState == 4) {
 				var userArray= req.responseText;
 		 				
				//if user exists, replace form with basic info for that user.
				if(userArray != 0) {
					userArray = $.parseJSON(userArray);
					var userId = userArray.userId;
					var username = userArray.username;
					var email = userArray.email;
					var fullName = userArray.fullName;
					var affiliation = userArray.affiliation;
					var enrollAs = userArray.enrollAs;
					var interests = userArray.interests;
					
					document.existingUserForm.existingUserId.value = userId;
					$("#existingUsername").text(username);
					$("#existingEmail").text(email);
					$("#existingFullName").text(fullName);
					$("#existingAffiliation").text(affiliation);
					$("#existingInterests").text(interests);
					//FIXME if user has selected an "Enroll user as" choice on previous page, select it here also.
					//FIXME if user already exists in a particular role in this journal, don't show "no role"
					
					$("#existingUserEnrollmentMsg").html("");
					$("#userFormContent").hide();
					$("#existingUserInfo").show();
				}
			}
		}
		
		sendAsyncRequest(req, '{/literal}{url op="checkUsername" username="REPLACE1" enrollAs="REPLACE2" escape=false}{literal}'.replace('REPLACE1', escape(document.userForm.username.value)).replace('REPLACE2', escape(document.userForm.enrollAs.value)), null, 'get');

	}
	
	function displayUserForm() {
		var username = "";
		document.userForm.username.value = username;
		$("#existingUserInfo").hide();
		$("#existingUserEnrollmentDone").hide();
		$("#userFormContent").show();		
	}
	
	function enrollExistingUser(enrollAnother) {
		var req = makeAsyncRequest();
 
		req.onreadystatechange = function() {
			if (req.readyState == 4) {
 				var enrollmentArray = req.responseText;

				//if success
				if(enrollmentArray != 0) {
					enrollmentArray = $.parseJSON(enrollmentArray);
					var userId = enrollmentArray.userId;
					var rolePath = enrollmentArray.rolePath;
					var roleId = enrollmentArray.roleId;
					var journalId = enrollmentArray.journalId;
					var userFullName = enrollmentArray.userFullName; 
					var enrollAnother = enrollmentArray.enrollAnother;
					var interests = enrollmentArray.interests;
					
					//FIXME add support for locale-sensitive language translation - get this from server
					var existingUserEnrollmentMsg = '<p><span class="errorText">The user ' + userFullName + ' was successfully enrolled in your journal '; 
					if(roleId != null) {
						existingUserEnrollmentMsg += "as " + rolePath;
					} else {
						existingUserEnrollmentMsg += " with no role";
					}
					existingUserEnrollmentMsg += ".</p>";
					document.userForm.username.value = "";
					$("#existingUserEnrollmentMsg").html(existingUserEnrollmentMsg);
					$("#existingUserInfo").hide();
					$("#existingUserEnrollmentDone").show();
				} else {
					//FIXME display an error message!
				}
			}
		}		
		sendAsyncRequest(req, '{/literal}{url op="enrollExistingUser" userId="REPLACE1" enrollAs="REPLACE2" enrollAnother="REPLACE3" escape=false}{literal}'.replace('REPLACE1', escape(document.existingUserForm.existingUserId.value)).replace('REPLACE2', escape(document.existingUserForm.enrollAs.value)).replace('REPLACE3', escape(enrollAnother)), null, 'get');
		
	}
// -->
</script>
{/literal}
{/if}

{literal}
<script type="text/javascript">
<!--
        function toggleAffiliationOther(value) {
                if (value == 'Other') {
                        document.getElementById('affiliationOther').style.display = 'block';
                } else {
                        document.getElementById('affiliationOther').style.display = 'none';
                }
        }

        function copyUsernameToEmail() {
                document.userForm.email.value = document.userForm.username.value;
        }
// -->
</script>
{/literal}

{if $userCreated}
<p>{translate key="manager.people.userCreatedSuccessfully"}</p>
{/if}

<h3>{if $userId}{translate key="manager.people.editProfile"}{else}{translate key="manager.people.createUser"}{/if}</h3>

<div id="existingUserInfo">
	<div id="existingUserMsg">
		{** FIXME add locale translation support **}
		<p>A user account already exists for the Email Address you provided. See the information below.</p> 
		<p>If you would like to enroll this user in your journal, select the appropriate role from the list and click 'Enroll'. If not, please click 'Go Back without Enrolling This User'
		and try entering a different Email Address.</p>
	</div>
	<form name="existingUserForm">
		<input type="hidden" name="existingUserId" id="existingUserId" />
		<table width="100%" class="data">
			<tr>
				<td class="label" width="20%">{fieldLabel key="user.username"}</td>
				<td class="value" id="existingUsername"></td>
			</tr>		
			<tr>
				<td class="label" width="20%">Full Name</td>
				<td class="value" id="existingFullName"></td>
			</tr>
			<tr>
				<td class="label" width="20%">{fieldLabel name="affiliation" key="user.affiliation"}</td>
				<td class="value" id="existingAffiliation"></td>
			</tr>
			<tr>
				<td class="label" width="20%">{fieldLabel for="interests" key="user.interests"}</td>
				<td class="value" id="existingInterests"></td>
			</tr>
			<tr valign="top">
				<td class="label">{fieldLabel name="enrollAs" key="manager.people.enrollUserAs"}</td>
				<td class="value">
					<select name="enrollAs[]" id="enrollAs" multiple="multiple" size="11" class="selectMenu">
					{html_options_translate options=$roleOptions selected=$enrollAs}
					</select>
					<br />
					<span class="instruct">{translate key="manager.people.enrollUserAsDescription"}</span>
				</td>
			</tr>
		</table>
		<br />
		<input type="button" name="enroll" id="enroll" class="button defaultButton" value="Enroll" onclick="enrollExistingUser(0)" />
		<input type="button" class="button" value="Go Back without Enrolling This User" onclick="displayUserForm()" />
	</form>
</div>

<div id="existingUserEnrollmentDone">
	<div id="existingUserEnrollmentMsg"></div>
	<p>
		<input type="button" value="OK" class="button defaultButton" onclick="{if $source == ''}history.go(-1);{else}document.location='{$source|escape:"jsparam"}';{/if}" />
		<input type="button" class="button" value="Enroll Another" onclick="displayUserForm()" />
	</p>
</div>

<form name="userForm" method="post" action="{url op="updateUser"}" onsubmit="enablePasswordFields()">
<div id="userFormContent">
<input type="hidden" name="source" value="{$source|escape}" />
{if $userId}
<input type="hidden" name="userId" value="{$userId|escape}" />
{/if}

{include file="common/formErrors.tpl"}

<table width="100%" class="data">
{if count($formLocales) > 1}
	 <tr valign="top">
	 	<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"userFormUrl" page="manager" op="editUser" path=$userId escape=false}
			{form_language_chooser form="userForm" url=$userFormUrl}
		</td>
	</tr>
{/if}
	{if not $userId}
	<tr valign="top">
		<td class="label">{fieldLabel name="username" required="true" key="user.username"}</td>
		<td class="value">
			<input type="text" name="username" id="username" value="{$username|escape}" size="30" maxlength="90" class="textField" onblur="checkUsername()" />
			<br />
			<span class="instruct">{translate key="user.register.usernameDescription"}</span>
			<!--<span class="instruct">{translate key="user.register.usernameRestriction"}</span>-->
		</td>
	</tr>	
	<tr valign="top">
		<td class="label">{fieldLabel name="enrollAs" key="manager.people.enrollUserAs"}</td>
		<td class="value">
			<select name="enrollAs[]" id="enrollAs" multiple="multiple" size="11" class="selectMenu">
			{html_options_translate options=$roleOptions selected=$enrollAs}
			</select>
			<br />
			<span class="instruct">{translate key="manager.people.enrollUserAsDescription"}</span>
		</td>
	</tr>
	{else}
	<tr valign="top">
		<td class="label">{fieldLabel suppressId="true" name="username" key="user.username"}</td>
		<td class="value"><strong>{$username|escape}</strong></td>
	</tr>
	{/if}
	{**
	<tr valign="top">
		<td class="label">{fieldLabel name="email" required="true" key="user.email"}</td>
		<td class="value"><input type="text" name="email" id="email" value="{$email|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	**}
	<input type="hidden" name="email" id="email" value="{$email|escape}" />
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
		<td class="label">{fieldLabel name="suffix" key="user.suffix"}</td>
		<td class="value"><input type="text" name="suffix" id="suffix" value="{$suffix|escape}" size="10" maxlength="40" class="textField" /></td>
	</tr>
	{**
	<tr valign="top">
		<td class="label">{fieldLabel suppressId="true" name="gender" key="user.gender"}</td>
		<td class="value">
			<select name="gender" id="gender" size="1" class="selectMenu">
				{html_options_translate options=$genderOptions selected=$gender}
			</select>
		</td>
	</tr>
	**}
	<tr valign="top">
		<td class="label">{fieldLabel name="initials" key="user.initials"}</td>
		<td class="value"><input type="text" name="initials" id="initials" value="{$initials|escape}" size="5" maxlength="5" class="textField" />&nbsp;&nbsp;{translate key="user.initialsExample"}</td>
	</tr>
	{if $authSourceOptions}
	<tr valign="top">
		<td class="label">{fieldLabel name="authId" key="manager.people.authSource"}</td>
		<td class="value"><select name="authId" id="authId" size="1" class="selectMenu">
			<option value=""></option>
			{html_options options=$authSourceOptions selected=$authId}
		</select></td>
	</tr>
	{/if}

	{if !$implicitAuth}
		<tr valign="top">
			<td class="label">{fieldLabel name="password" required=$passwordRequired key="user.password"}</td>
			<td class="value">
				<input type="password" name="password" id="password" value="{$password|escape}" size="20" maxlength="32" class="textField" />
				<br />
				<span class="instruct">{translate key="user.register.passwordLengthRestriction" length=$minPasswordLength}</span>
			</td>
		</tr>
		<tr valign="top">
			<td class="label">{fieldLabel name="password2" required=$passwordRequired key="user.register.repeatPassword"}</td>
			<td class="value"><input type="password" name="password2"  id="password2" value="{$password2|escape}" size="20" maxlength="32" class="textField" /></td>
		</tr>
		{if $userId}
		<tr valign="top">
			<td>&nbsp;</td>
			<td class="value">{translate key="user.register.passwordLengthRestriction" length=$minPasswordLength}<br />{translate key="user.profile.leavePasswordBlank"}</td>
		</tr>
		{else}
		<tr valign="top">
			<td class="label">&nbsp;</td>
			<td class="value"><input type="checkbox" onclick="setGenerateRandom(this.checked)" name="generatePassword" id="generatePassword" value="1"{if $generatePassword} checked="checked"{/if} /> <label for="generatePassword">{translate key="manager.people.createUserGeneratePassword"}</label></td>
		</tr>
		<tr valign="top">
			<td class="label">&nbsp;</td>
			<td class="value"><input type="checkbox" name="sendNotify" id="sendNotify" value="1"{if $sendNotify} checked="checked"{/if} /> <label for="sendNotify">{translate key="manager.people.createUserSendNotify"}</label></td>
		</tr>
		{/if}
		<tr valign="top">
			<td class="label">&nbsp;</td>
			<td class="value"><input type="checkbox" name="mustChangePassword" id="mustChangePassword" value="1"{if $mustChangePassword} checked="checked"{/if} /> <label for="mustChangePassword">{translate key="manager.people.userMustChangePassword"}</label></td>
		</tr>
	{/if}{* !$implicitAuth *}

	<tr valign="top">
		<td class="label">{fieldLabel name="affiliation" key="user.affiliation"}</td>
		<td class="value">
                        <select name="affiliation[{$formLocale|escape}]" id="affiliation" class="selectMenu" onchange="toggleAffiliationOther(this.value)">
                                <option value="">Select Institution</option>
                                {html_options options=$institutionList selected=$affiliation[$formLocale]|escape}
				<option value="Other" {if not in_array($affiliation[$formLocale], $institutionList) and $affiliation[$formLocale] != ''}selected{/if}>Other:</option>
                        </select>	
			<!--{** Only show "other" field if appropriate **} --> 
			{if not in_array($affiliation[$formLocale], $institutionList) and $affiliation[$formLocale] != ''}
				{assign var="displayStyle" value=""}
				{assign var="affiliationOther" value=$affiliation[$formLocale]|escape}
			{else}
				{assign var="displayStyle" value='style="display:none"'}
				{assign var="affiliationOther" value=""}
			{/if}
			<input type="text" name="affiliationOther" id="affiliationOther" value="{$affiliationOther}" size="70" class="textField" {$displayStyle} />
		</td>
<!--{**
		<td class="value">
			<textarea name="affiliation[{$formLocale|escape}]" id="affiliation" rows="5" cols="40" class="textArea">{$affiliation[$formLocale]|escape}</textarea><br/>
			<span class="instruct">{translate key="user.affiliation.description"}</span>
		</td>
**}-->
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="professionalTitle" key="user.professionalTitle"}</td>
		<td class="value"><input type="text" name="professionalTitle[{$formLocale|escape}]" id="professionalTitle" value="{$professionalTitle[$formLocale]|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="signature" key="user.signature"}</td>
		<td class="value"><textarea name="signature[{$formLocale|escape}]" id="signature" rows="5" cols="40" class="textArea">{$signature[$formLocale]|escape}</textarea></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="userUrl" key="user.url"}</td>
		<td class="value"><input type="text" name="userUrl" id="userUrl" value="{$userUrl|escape}" size="30" maxlength="90" class="textField" /></td>
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
			<span class="interestDescription">{fieldLabel for="interests" key="user.interests.description"}</span>
                        <br />
			<ul id="interests">
				<li></li>
			</ul>
			<textarea name="interests" id="interestsTextOnly" rows="5" cols="40" class="textArea">
				{foreach name=currentInterests from=$interestsKeywords item=interest}{$interest|escape}{if !$smarty.foreach.currentInterests.last}, {/if}{/foreach}
			</textarea>
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

<p>
	{assign var="onclick" value='onclick="copyUsernameToEmail()"'}
	<input type="submit" value="{translate key="common.save"}" class="button defaultButton" {$onclick} /> 
	{if not $userId}<input type="submit" name="createAnother" value="{translate key="manager.people.saveAndCreateAnotherUser"}" class="button" {$onclick} /> {/if}
	<input type="button" value="{translate key="common.cancel"}" class="button" onclick="{if $source == ''}history.go(-1);{else}document.location='{$source|escape:"jsparam"}';{/if}" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
</form>

{if $generatePassword}
{literal}
	<script type="text/javascript">
		<!--
		setGenerateRandom(1);
		// -->
	</script>
{/literal}
{/if}

{include file="common/footer.tpl"}

