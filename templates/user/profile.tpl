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
{url|assign:"url" op="profile"}
{include file="common/header.tpl"}

<form method="post" action="{url op="saveProfile"}">

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel suppressId="true" name="username" key="user.username"}</td>
	<td width="80%" class="value">{$username|escape}</td>
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
	<td class="label">{fieldLabel name="affiliation" key="user.affiliation"}</td>
	<td class="value"><input type="text" name="affiliation" id="affiliation" value="{$affiliation|escape}" size="30" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="email" required="true" key="user.email"}</td>
	<td class="value"><input type="text" name="email" id="email" value="{$email|escape}" size="30" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="userUrl" key="user.url"}</td>
	<td class="value"><input type="text" name="userUrl" id="url" value="{$userUrl|escape}" size="30" maxlength="90" class="textField" /></td>
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
	<td class="label">{fieldLabel name="interests" key="user.interests"}</td>
	<td class="value"><input type="text" name="interests" id="interests" value="{$interests|escape}" size="30" maxlength="255" class="textField" /></td>
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
	<td class="value"><textarea name="biography" id="biography" rows="5" cols="40" class="textArea">{$biography|escape}</textarea></td>
</tr>
{if $profileLocalesEnabled && count($availableLocales) > 1}
<tr valign="top">
	<td class="label">{translate key="user.workingLanguages"}</td>
	<td>{foreach from=$availableLocales key=localeKey item=localeName}
		<input type="checkbox" name="userLocales[]" id="userLocales-{$localeKey}" value="{$localeKey}"{if in_array($localeKey, $userLocales)} checked="checked"{/if} /> <label for="userLocales-{$localeKey}">{$localeName|escape}</label><br />
	{/foreach}</td>
</tr>
{/if}

{foreach from=$journals name=journalNotifications key=thisJournalId item=thisJournal}
	{assign var=thisJournalId value=$thisJournal->getJournalId()}
	{assign var=notificationEnabled value=`$journalNotifications.$thisJournalId`}
	{if !$notFirstJournal}
		{assign var=notFirstJournal value=1}
		<tr valign="top">
			<td class="label">{translate key="user.profile.form.publishedNotifications"}</td>
			<td class="value">
	{/if}

			<input type="checkbox" name="journalNotify[]" {if $notificationEnabled}checked="checked" {/if}id="journalNotify-{$thisJournalId}" value="{$thisJournalId}" /> <label for="journalNotify-{$thisJournalId}">{$thisJournal->getTitle()|escape}</label><br/>

	{if $smarty.foreach.journalNotifications.last}
			</td>
		</tr>
	{/if}
{/foreach}

{if $displayOpenAccessNotification}
	{assign var=notFirstJournal value=0}
	{foreach from=$journals name=journalOpenAccessNotifications key=thisJournalId item=thisJournal}
		{assign var=thisJournalId value=$thisJournal->getJournalId()}
		{assign var=enableSubscriptions value=$thisJournal->getSetting('enableSubscriptions')}
		{assign var=enableOpenAccessNotification value=$thisJournal->getSetting('enableOpenAccessNotification')}
		{assign var=notificationEnabled value=$user->getSetting('openAccessNotification', $thisJournalId)}
		{if !$notFirstJournal}
			{assign var=notFirstJournal value=1}
			<tr valign="top">
				<td class="label">{translate key="user.profile.form.openAccessNotifications"}</td>
				<td class="value">
		{/if}

		{if $enableSubscriptions && $enableOpenAccessNotification}
			<input type="checkbox" name="openAccessNotify[]" {if $notificationEnabled}checked="checked" {/if}id="openAccessNotify-{$thisJournalId}" value="{$thisJournalId}" /> <label for="openAccessNotify-{$thisJournalId}">{$thisJournal->getTitle()|escape}</label><br/>
		{/if}

		{if $smarty.foreach.journalOpenAccessNotifications.last}
				</td>
			</tr>
		{/if}
	{/foreach}
{/if}

</table>
<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="user" escape=false}'" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
