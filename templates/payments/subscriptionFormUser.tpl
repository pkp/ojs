{**
 * templates/payments/subscriptionFormUser.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common subscription fields
 *
 *}
<tr>
	<td>&nbsp;</td>
	<td><span class="instruct">{translate key="manager.subscriptions.form.userProfileInstructions"}</span></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="userSalutation" key="user.salutation"}</td>
	<td class="value"><input type="text" name="userSalutation" id="userSalutation" value="{$userSalutation|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="userFirstName" required="true" key="user.firstName"}</td>
	<td class="value"><input type="text" name="userFirstName" id="userFirstName" value="{$userFirstName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="userMiddleName" key="user.middleName"}</td>
	<td class="value"><input type="text" name="userMiddleName" id="userMiddleName" value="{$userMiddleName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="userLastName" required="true" key="user.lastName"}</td>
	<td class="value"><input type="text" name="userLastName" id="userLastName" value="{$userLastName|escape}" size="20" maxlength="90" class="textField" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="userInitials" key="user.initials"}</td>
	<td class="value"><input type="text" name="userInitials" id="userInitials" value="{$userInitials|escape}" size="5" maxlength="5" class="textField" />&nbsp;&nbsp;{translate key="user.initialsExample"}</td>
</tr>
<tr>
	<td class="label">{fieldLabel name="userAffiliation" key="user.affiliation"}</td>
	<td class="value">
		<textarea name="userAffiliation[{$formLocale|escape}]" id="userAffiliation" rows="5" cols="40" class="textArea">{$userAffiliation[$formLocale]|escape}</textarea><br/>
		<span class="instruct">{translate key="user.affiliation.description"}</span>
	</td>
</tr>
<tr>
	<td class="label">{fieldLabel name="userUrl" key="user.url"}</td>
	<td class="value"><input type="text" name="userUrl" id="userUrl" value="{$userUrl|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="userEmail" required="true" key="user.email"}</td>
	<td class="value"><input type="text" name="userEmail" id="userEmail" value="{if $userEmail}{$userEmail|escape}{/if}" size="30" maxlength="90" class="textField" /></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td class="value">
		<table>
			<tr>
				<td width="5%"><input type="checkbox" name="notifyEmail" id="notifyEmail" value="1"{if $notifyEmail} checked="checked"{/if} /></td>
				<td><label for="notifyEmail">{translate key="manager.subscriptions.form.notifyEmail"}</label></td>
			</tr>
		</table>
	</td>
</tr>
<tr>
	<td class="label">{fieldLabel name="userPhone" key="user.phone"}</td>
	<td class="value"><input type="text" name="userPhone" id="userPhone" value="{$userPhone|escape}" size="15" maxlength="24" class="textField" /></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="userMailingAddress" key="common.mailingAddress"}</td>
	<td class="value"><textarea name="userMailingAddress" id="userMailingAddress" rows="3" cols="40" class="textArea">{$userMailingAddress|escape}</textarea></td>
</tr>
<tr>
	<td class="label">{fieldLabel name="userCountry" key="common.country"}</td>
	<td class="value">
		<select name="userCountry" id="userCountry" class="selectMenu">
			<option value=""></option>
			{html_options options=$validCountries selected=$userCountry}
		</select>
	</td>
</tr>

