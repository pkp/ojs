{**
 * importUsersConfirm.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the results of importing users.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.users.displayName"}
{include file="common/header.tpl"}
{/strip}

{translate key="plugins.importexport.users.import.confirmUsers"}:
<form action="{plugin_url path="import"}" method="post">
{if $sendNotify}
	<input type="hidden" name="sendNotify" value="{$sendNotify|escape}" />
{/if}
{if $continueOnError}
	<input type="hidden" name="continueOnError" value="{$continueOnError|escape}" />
{/if}

{if $errors}
	<p>
		<span class="formError">{translate key="plugins.importexport.users.import.warning"}:</span>
		<ul class="formErrorList">
			{foreach key=field item=message from=$errors}
				<li>{$message}</li>
			{/foreach}
		</ul>
	</p>
{/if}

<table width="100%" class="listing">
	<tr>
		<td colspan="7" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">&nbsp;</td>
		<td width="10%">{translate key="user.firstName"}</td>
		<td width="10%">{translate key="user.middleName"}</td>
		<td width="15%">{translate key="user.lastName"}</td>
		<td width="15%">{translate key="user.username"}</td>
		<td width="20%">{translate key="user.email"}</td>
		<td width="25%">{translate key="user.roles"}</td>
	</tr>	
	<tr>
		<td colspan="7" class="headseparator">&nbsp;</td>
	</tr>
{foreach name=users from=$users item=user key=userKey}
	<tr valign="top">
		<td>
			<input type="checkbox" name="userKeys[]" value="{$userKey|escape}" checked="checked" />
			{foreach from=$user->getBiography(null) key=locale item=value}
				<input type="hidden" name="{$userKey|escape}_biography[{$locale|escape}]" value="{$value|escape}" />
			{/foreach}
			{foreach from=$user->getSignature(null) key=locale item=value}
				<input type="hidden" name="{$userKey|escape}_signature[{$locale|escape}]" value="{$value|escape}" />
			{/foreach}
			{foreach from=$user->getInterests(null) key=locale item=value}
				<input type="hidden" name="{$userKey|escape}_interests[{$locale|escape}]" value="{$value|escape}" />
			{/foreach}
			{foreach name=locales from=$user->getLocales() item=locale}
				<input type="hidden" name="{$userKey|escape}_locales[]" value="{$locale|escape}" />
			{/foreach}			
			<input type="hidden" name="{$userKey|escape}_country" value="{$user->getCountry()|escape}" />
			<input type="hidden" name="{$userKey|escape}_mailingAddress" value="{$user->getMailingAddress()|escape}" />
			<input type="hidden" name="{$userKey|escape}_fax" value="{$user->getFax()|escape}" />
			<input type="hidden" name="{$userKey|escape}_phone" value="{$user->getPhone()|escape}" />
			<input type="hidden" name="{$userKey|escape}_url" value="{$user->getUrl()|escape}" />
			<input type="hidden" name="{$userKey|escape}_affiliation" value="{$user->getAffiliation()|escape}" />
			<input type="hidden" name="{$userKey|escape}_gender" value="{$user->getGender()|escape}" />
			<input type="hidden" name="{$userKey|escape}_initials" value="{$user->getInitials()|escape}" />
			<input type="hidden" name="{$userKey|escape}_salutation" value="{$user->getSalutation()|escape}" />
			<input type="hidden" name="{$userKey|escape}_password" value="{$user->getPassword()|escape}" />
			<input type="hidden" name="{$userKey|escape}_unencryptedPassword" value="{$user->getUnencryptedPassword()|escape}" />
			<input type="hidden" name="{$userKey|escape}_mustChangePassword" value="{$user->getMustChangePassword()|escape}" />
		</td>
		<td><input type="text" name="{$userKey|escape}_firstName" value="{$user->getFirstName()|escape}" size="9" class="textField" /></td>
		<td><input type="text" name="{$userKey|escape}_middleName" value="{$user->getMiddleName()|escape}" size="9" class="textField" /></td>
		<td><input type="text" name="{$userKey|escape}_lastName" value="{$user->getLastName()|escape}" size="9" class="textField" /></td>
		<td><input type="text" name="{$userKey|escape}_username" value="{$user->getUsername()|escape}" size="9" class="textField" /></td>
		<td><input type="text" name="{$userKey|escape}_email" value="{$user->getEmail()|escape}" size="9" class="textField" /></td>
		<td>
			<select name="{$userKey|escape}_roles[]" size="5" multiple="multiple" class="selectMenu">
				{foreach from=$roleOptions item=roleOption key=roleKey}
					<option value="{$roleKey|escape}" {if ($roleKey eq '' and count($usersRoles[$userKey]) eq 0)}selected{elseif (in_array($roleOption, $usersRoles[$userKey]))}selected="selected"{/if}>{translate key=$roleOption}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="7" class="{if $smarty.foreach.users.last}end{/if}separator">&nbsp;</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="7" class="nodata">{translate key="manager.people.noneEnrolled"}</td>
	</tr>
	<tr>
		<td colspan="7" class="endseparator">&nbsp;</td>
	</tr>
{/foreach}
</table>

<input type="submit" value="{translate key="plugins.importexport.users.import.importUsers"}" class="button defaultButton" />
</form>

{if $isError}
<p>
	<span class="formError">{translate key="plugins.importexport.users.import.errorsOccurred"}:</span>
	<ul class="formErrorList">
	{foreach key=field item=message from=$errors}
			<li>{$message}</li>
	{/foreach}
	</ul>
</p>
{/if}

<p>&#187; <a href="{url page="manager"}">{translate key="manager.journalManagement"}</a></p>

{include file="common/footer.tpl"}
