{**
 * importUsersConfirm.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show the results of importing users.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.people.importUsers"}
{include file="common/header.tpl"}

{translate key="manager.people.importUsers.confirmUsers"}:
<form action="{$pageUrl}/manager/importUsers/import" method="post">
{if $sendNotify}
	<input type="hidden" name="sendNotify" value="{$sendNotify}">
{/if}
{if $continueOnError}
	<input type="hidden" name="continueOnError" value="{$continueOnError}">
{/if}
<table>
	<tr class="heading">
		<td></td>
		<td>{translate key="user.firstName"}</td>
		<td>{translate key="user.middleName"}</td>
		<td>{translate key="user.lastName"}</td>
		<td>{translate key="user.username"}</td>
		<td>{translate key="user.email"}</td>
		<td>{translate key="user.roles"}</td>
	</tr>	
{foreach from=$users item=user key=userKey}
	<tr class="{cycle values="row,rowAlt"}">
		<td>
			<input type="checkbox" name="userKeys[]" value="{$userKey}" checked="checked">
			<input type="hidden" name="{$userKey}_fax" value="{$user->getFax()|escape}">
			<input type="hidden" name="{$userKey}_phone" value="{$user->getPhone()|escape}">
			<input type="hidden" name="{$userKey}_affiliation" value="{$user->getAffiliation()|escape}">
			<input type="hidden" name="{$userKey}_mailingAddress" value="{$user->getMailingAddress()|escape}">
			<input type="hidden" name="{$userKey}_biography" value="{$user->getBiography()|escape}">
			<input type="hidden" name="{$userKey}_password" value="{$user->getPassword()|escape}">
			<input type="hidden" name="{$userKey}_unencryptedPassword" value="{$user->getUnencryptedPassword()|escape}">
		</td>
		<td><input type="text" name="{$userKey}_firstName" value="{$user->getFirstName()|escape}" size="16"></td>
		<td><input type="text" name="{$userKey}_middleName" value="{$user->getMiddleName()|escape}" size="16"></td>
		<td><input type="text" name="{$userKey}_lastName" value="{$user->getLastName()|escape}" size="16"></td>
		<td><input type="text" name="{$userKey}_username" value="{$user->getUsername()|escape}" size="16"></td>
		<td><input type="text" name="{$userKey}_email" value="{$user->getEmail()|escape}" size="16"></td>
		<td>
			<select name="{$userKey}_roles[]" size="5" multiple>
				{foreach from=$roleOptions item=roleOption key=roleKey}
					<option value="{$roleKey}" {if ($roleKey eq '' and count($usersRoles[$userKey]) eq 0)}selected{elseif (in_array($roleOption, $usersRoles[$userKey]))}selected{/if}>{translate key=$roleOption}</option>
				{/foreach}
			</select>
		</td>
	</tr>
{/foreach}
</table>
<table class="plain">
<tr>
	<td class="formField"><input type="submit" value="{translate key="manager.people.importUsers"}" class="formButton" /></td>
</tr>
</table>
</form>
{if $isError}
	<br />
	<span class="formError">{translate key="manager.people.importUsers.errorsOccurred"}:</span>
	<ul class="formErrorList">
	{foreach key=field item=message from=$errors}
			<li>{$message}</li>
	{/foreach}
	</ul>
{/if}

<br />
&#187; <a href="{$pageUrl}/manager">{translate key="manager.journalManagement}</a>

{include file="common/footer.tpl"}
