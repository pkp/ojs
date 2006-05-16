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

{assign var="pageTitle" value="plugins.importexport.users.displayName"}
{include file="common/header.tpl"}

{translate key="plugins.importexport.users.import.confirmUsers"}:
<form action="{plugin_url path="import"}" method="post">
{if $sendNotify}
	<input type="hidden" name="sendNotify" value="{$sendNotify|escape}" />
{/if}
{if $continueOnError}
	<input type="hidden" name="continueOnError" value="{$continueOnError|escape}" />
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
			<input type="checkbox" name="userKeys[]" value="{$userKey}" checked="checked" />
			<input type="hidden" name="{$userKey}_fax" value="{$user->getFax()|escape}" />
			<input type="hidden" name="{$userKey}_url" value="{$user->getUrl()|escape}" />
			<input type="hidden" name="{$userKey}_phone" value="{$user->getPhone()|escape}" />
			<input type="hidden" name="{$userKey}_affiliation" value="{$user->getAffiliation()|escape}" />
			<input type="hidden" name="{$userKey}_mailingAddress" value="{$user->getMailingAddress()|escape}" />
			<input type="hidden" name="{$userKey}_biography" value="{$user->getBiography()|escape}" />
			<input type="hidden" name="{$userKey}_password" value="{$user->getPassword()|escape}" />
			<input type="hidden" name="{$userKey}_unencryptedPassword" value="{$user->getUnencryptedPassword()|escape}" />
		</td>
		<td><input type="text" name="{$userKey}_firstName" value="{$user->getFirstName()|escape}" size="9" class="textField" /></td>
		<td><input type="text" name="{$userKey}_middleName" value="{$user->getMiddleName()|escape}" size="9" class="textField" /></td>
		<td><input type="text" name="{$userKey}_lastName" value="{$user->getLastName()|escape}" size="9" class="textField" /></td>
		<td><input type="text" name="{$userKey}_username" value="{$user->getUsername()|escape}" size="9" class="textField" /></td>
		<td><input type="text" name="{$userKey}_email" value="{$user->getEmail()|escape}" size="9" class="textField" /></td>
		<td>
			<select name="{$userKey}_roles[]" size="5" multiple="multiple" class="selectMenu">
				{foreach from=$roleOptions item=roleOption key=roleKey}
					<option value="{$roleKey}" {if ($roleKey eq '' and count($usersRoles[$userKey]) eq 0)}selected{elseif (in_array($roleOption, $usersRoles[$userKey]))}selected="selected"{/if}>{translate key=$roleOption}</option>
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
