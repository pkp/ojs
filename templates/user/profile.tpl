{**
 * profile.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User profile form.
 *
 * $Id$
 *}

{assign var="pageTitle" value="user.profile.editProfile"}
{assign var="currentUrl" value="$pageUrl/user/profile"}
{include file="common/header.tpl"}

<form method="post" action="{$pageUrl}/user/saveProfile">

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="username" key="user.username"}</td>
	<td width="100%" class="value">{$username|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="firstName" required="true" key="user.firstName"}</td>
	<td class="value"><input type="text" name="firstName" value="{$firstName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="middleName" key="user.middleName"}</td>
	<td class="value"><input type="text" name="middleName" value="{$middleName|escape}" size="20" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="lastName" required="true" key="user.lastName"}</td>
	<td class="value"><input type="text" name="lastName" value="{$lastName|escape}" size="20" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="initials" key="user.initials"}</td>
	<td class="value"><input type="text" name="initials" value="{$initials|escape}" size="5" maxlength="5" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="affiliation" key="user.affiliation"}</td>
	<td class="value"><input type="text" name="affiliation" value="{$affiliation|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="email" required="true" key="user.email"}</td>
	<td class="value"><input type="text" name="email" value="{$email|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="phone" key="user.phone"}</td>
	<td class="value"><input type="text" name="phone" value="{$phone|escape}" size="15" maxlength="24" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="fax" key="user.fax"}</td>
	<td class="value"><input type="text" name="fax" value="{$fax|escape}" size="15" maxlength="24" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="interests" key="user.interests"}</td>
	<td class="value"><input type="text" name="interests" value="{$interests|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="mailingAddress" key="common.mailingAddress"}</td>
	<td class="value"><textarea name="mailingAddress" rows="3" cols="40" class="textArea">{$mailingAddress|escape}</textarea></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="biography" key="user.biography"}</td>
	<td class="value"><textarea name="biography" rows="5" cols="40" class="textArea">{$biography|escape}</textarea></td>
</tr>
{if $profileLocalesEnabled && count($availableLocales) > 1}
<tr valign="top">
	<td class="label">{translate key="user.workingLanguages"}</td>
	<td>{foreach from=$availableLocales key=localeKey item=localeName}
		<input type="checkbox" name="userLocales[]" id="userLocales[{$localeKey}]" value="{$localeKey}"{if in_array($localeKey, $userLocales)} checked="checked"{/if} /> <label for="userLocales[{$localeKey}]">{$localeName}</label><br />
	{/foreach}</td>
</tr>
{/if}

{foreach from=$journalNotifications name=journalNotifications key=thisJournalId item=notificationsEnabled}
	{assign var=thisJournal value=$journals.$thisJournalId}
	{if $thisJournal}
		{if !$notFirstJournal}
			{assign var=notFirstJournal value=1}
			<tr valign="top">
				<td class="label">{translate key="user.profile.form.notifications"}</td>
				<td class="value">
		{/if}
	
			<input type="checkbox" name="journalNotify[]" {if $notificationsEnabled}checked="checked" {/if}id="journalNotify" value="{$thisJournal->getJournalId()}"> <label for="journalNotify">{$thisJournal->getSetting('journalTitle')}</label><br/>
	
		{if $smarty.foreach.journalNotifications.last}
				</td>
			</tr>
		{/if}
	{/if}
{/foreach}

</table>
<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/user'" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
