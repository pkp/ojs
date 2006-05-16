{**
 * userProfile.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display user profile.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.people"}
{include file="common/header.tpl"}

<h3>{translate key="user.profile"}: {$user->getFullName()|escape}</h3>

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{translate key="user.username"}:</td>
	<td width="80%" class="value">{$user->getUsername()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.firstName"}:</td>
	<td class="value">{$user->getFirstName()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.middleName"}:</td>
	<td class="value">{$user->getMiddleName()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.lastName"}:</td>
	<td class="value">{$user->getLastName()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.affiliation"}:</td>
	<td class="value">{$user->getAffiliation()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.email"}:</td>
	<td class="value">
		{$user->getEmail()|escape} 
		{assign var=emailString value="`$user->getFullName()` <`$user->getEmail()`>"}
		{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl}
		{icon name="mail" url=$url}
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.url"}:</td>
	<td class="value"><a href="{$user->getUrl()|escape:"quotes"}">{$user->getUrl()|escape}</a></td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.phone"}:</td>
	<td class="value">{$user->getPhone()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.fax"}:</td>
	<td class="value">{$user->getFax()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.interests"}:</td>
	<td class="value">{$user->getInterests()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="common.mailingAddress"}:</td>
	<td class="value">{$user->getMailingAddress()|escape|nl2br}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.biography"}:</td>
	<td class="value">{$user->getBiography()|strip_unsafe_html|nl2br}</td>
</tr>
{if $profileLocalesEnabled}
<tr valign="top">
	<td class="label">{translate key="user.workingLanguages"}:</td>
	<td class="value">{foreach name=workingLanguages from=$user->getLocales() item=localeKey}{$localeNames.$localeKey|escape}{if !$smarty.foreach.workingLanguages.last}; {/if}{/foreach}</td>
</tr>
{/if}
<tr valign="top">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.dateRegistered"}:</td>
	<td class="value">{$user->getDateRegistered()|date_format:$datetimeFormatLong}</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="user.dateLastLogin"}:</td>
	<td class="value">{$user->getDateLastLogin()|date_format:$datetimeFormatLong}</td>
</tr>
</table>

{include file="common/footer.tpl"}
