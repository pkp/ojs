{**
 * createReviewerForm.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for editors to create reviewers.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="sectionEditor.review.createReviewer"}
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

<!-- BLH 20110822 	moved this js to top of tpl and added several functions to support searching on email address for existing user 
					FIXME this js duplicates what's in ojs/templates/manager/people/userProfileForm.tpl - need to merge this tpl into that one!!!
-->
{/literal}
<script type="text/javascript">
{literal}
// <!--

	function generateUsername() {
		var req = makeAsyncRequest();

		if (document.reviewerForm.lastName.value == "") {
			alert("{/literal}{translate key="manager.people.mustProvideName"}{literal}");
			return;
		}

		req.onreadystatechange = function() {
			if (req.readyState == 4) {
				document.reviewerForm.username.value = req.responseText;
			}
		}
		sendAsyncRequest(req, '{/literal}{url op="suggestUsername" firstName="REPLACE1" lastName="REPLACE2" escape=false}{literal}'.replace('REPLACE1', escape(document.reviewerForm.firstName.value)).replace('REPLACE2', escape(document.reviewerForm.lastName.value)), null, 'get');
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
		
		sendAsyncRequest(req, '{/literal}{url op="checkUsername" username="REPLACE1" enrollAs="REPLACE2" escape=false}{literal}'.replace('REPLACE1', escape(document.reviewerForm.username.value)).replace('REPLACE2', 'reviewer'), null, 'get');

	}
	
	function displayUserForm() {
		var username = "";
		document.reviewerForm.username.value = username;
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
					document.reviewerForm.username.value = "";
					$("#existingUserEnrollmentMsg").html(existingUserEnrollmentMsg);
					$("#existingUserInfo").hide();
					$("#existingUserEnrollmentDone").show();
				} else {
					//FIXME display an error message!
					$("#existingUserEnrollmentMsg").html("Error: enrollmentArray empty!!");
					$("#existingUserInfo").hide();
					$("#existingUserEnrollmentDone").show();
				}
			}
		}		
		sendAsyncRequest(req, '{/literal}{url op="enrollExistingUser" userId="REPLACE1" enrollAs="REPLACE2" enrollAnother="REPLACE3" articleId="REPLACE4" escape=false}{literal}'.replace('REPLACE1', escape(document.existingUserForm.existingUserId.value)).replace('REPLACE2', 'reviewer').replace('REPLACE3', 0).replace('REPLACE4', document.existingUserForm.articleId.value), null, 'get');
		
	}
	
	function copyUsernameToEmail() {
		document.reviewerForm.email.value = document.reviewerForm.username.value;
	}
// -->
{/literal}
</script>

<div id="existingUserInfo">
	<div id="existingUserMsg">
		{** FIXME add locale translation support **}
		<p>A user account already exists for the Email Address you provided. See the information below.</p> 
		<p>If you would like to enroll this user as Reviewer for your journal, click 'Enroll'. If not, please click 'Go Back without Enrolling This User'
		and try entering a different Email Address.</p>
	</div>
	<form name="existingUserForm">
		<input type="hidden" name="existingUserId" id="existingUserId" />
		<input type="hidden" name="articleId" id="articleId" value={$articleId} />
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
			<input type="hidden" name="enrollAs" id="enrollAs" value="Reviewer">
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

<form method="post" name="reviewerForm" action="{url op="createReviewer" path=$articleId|to_array:"create"}">
<div id="userFormContent">
{include file="common/formErrors.tpl"}

<div id="createReviewerForm">
<table width="100%" class="data">
{if count($formLocales) > 1}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"createReviewerUrl" op="createReviewer" escape=false}
			{form_language_chooser form="reviewerForm" url=$createReviewerUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
	<tr valign="top">
		<td class="label">{fieldLabel name="username" required="true" key="user.username"}</td>
		<td class="value">
			<input type="text" name="username" id="username" value="{$username|escape}" size="30" maxlength="90" class="textField" onblur="checkUsername()"  />&nbsp;&nbsp;
			{** BLH 20110822 using email address as username **}
			{** 
			<input type="button" class="button" value="{translate key="common.suggest"}" onclick="generateUsername()" />
			<br />
			<span class="instruct">{translate key="user.register.usernameRestriction"}</span> **}
		</td>
	</tr>
	{** BLH 20110822 moved checkbox to be next to email address/username field **}
	<tr valign="top">
		<td class="label">&nbsp;</td>
		<td class="value"><input type="checkbox" name="sendNotify" id="sendNotify" value="1"{if $sendNotify} checked="checked"{/if} /> <label for="sendNotify">{translate key="manager.people.createUserSendNotify"}</label></td>
	</tr>
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
	{** BLH 20110822 not using this field **}
	{**
	<tr valign="top">
		<td class="label">{fieldLabel name="gender" key="user.gender"}</td>
		<td class="value">
			<select name="gender" id="gender" size="1" class="selectMenu">
				{html_options_translate options=$genderOptions selected=$gender}
			</select>
		</td>
	</tr>
	**}
	<tr valign="top">
		<td class="label">{fieldLabel name="affiliation" key="user.affiliation"}</td>
		<td class="value">
			<textarea name="affiliation[{$formLocale|escape}]" id="affiliation" rows="5" cols="40" class="textArea">{$affiliation[$formLocale]|escape}</textarea><br/>
			<span class="instruct">{translate key="user.affiliation.description"}</span>
		</td>
	</tr>
	{** BLH 20110822 using email address as username **}
	{** 
	<tr valign="top">
		<td class="label">{fieldLabel name="email" required="true" key="user.email"}</td>
		<td class="value"><input type="text" name="email" id="email" value="{$email|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	**}
	<input type="hidden" name="email" id="email" value="{$email|escape}" />
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
			<span class="interestDescription">{fieldLabel for="interests" key="user.interests.description"}</span><br />
			<ul id="interests"><li></li></ul>
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
	<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="selectReviewer" path=$articleId escape=false}'" />
</p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
</div>{** end userFormContent div **}
</form>

{include file="common/footer.tpl"}

