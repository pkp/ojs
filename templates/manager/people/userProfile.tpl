{**
 * templates/manager/people/userProfile.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display user profile.
 *
 *}
{strip}
{assign var="pageTitle" value="manager.people"}
{include file="common/header.tpl"}
{/strip}

<h3 id="userFullName">{$user->getFullName()|escape}</h3>
<div id="profile">
<h4>{translate key="user.profile"}</h4>

<p><a href="{url op="editUser" path=$user->getId()}" class="action">{translate key="manager.people.editProfile"}</a></p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="user.username"}</td>
		<td width="80%" class="data"><strong>{$user->getUsername()|escape}</strong></td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.salutation"}</td>
		<td class="value">{$user->getSalutation()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.firstName"}</td>
		<td class="value">{$user->getFirstName()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.middleName"}</td>
		<td class="value">{$user->getMiddleName()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.lastName"}</td>
		<td class="value">{$user->getLastName()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.affiliation"}</td>
		<td class="value">{$user->getLocalizedAffiliation()|escape|nl2br|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.signature"}</td>
		<td class="value">{$user->getLocalizedSignature()|escape|nl2br|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.initials"}</td>
		<td class="value">{$user->getInitials()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.gender"}</td>
		<td class="value">
			{if $user->getGender() == "M"}{translate key="user.masculine"}
			{elseif $user->getGender() == "F"}{translate key="user.feminine"}
			{elseif $user->getGender() == "O"}{translate key="user.other"}
			{else}&mdash;
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.email"}</td>
		<td class="value">
			{$user->getEmail()|escape}
			{assign var=emailString value=$user->getFullName()|concat:" <":$user->getEmail():">"}
			{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl}
			{icon name="mail" url=$url}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.url"}</td>
		<td class="value"><a href="{$user->getUrl()|escape:"quotes"}">{$user->getUrl()|escape}</a></td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.phone"}</td>
		<td class="value">{$user->getPhone()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.fax"}</td>
		<td class="value">{$user->getFax()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.interests"}</td>
		<td class="value">{$userInterests|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.gossip"}</td>
		<td class="value">{$user->getLocalizedGossip()|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.mailingAddress"}</td>
		<td class="value">{$user->getMailingAddress()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.country"}</td>
		<td class="value">{$country|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.biography"}</td>
		<td class="value">{$user->getLocalizedBiography()|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.workingLanguages"}</td>
		<td class="value">{foreach name=workingLanguages from=$user->getLocales() item=localeKey}{$localeNames.$localeKey|escape}{if !$smarty.foreach.workingLanguages.last}; {/if}{foreachelse}&mdash;{/foreach}</td>
	</tr>
	<tr valign="top">
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.dateRegistered"}</td>
		<td class="value">{$user->getDateRegistered()|date_format:$datetimeFormatLong}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.dateLastLogin"}</td>
		<td class="value">{$user->getDateLastLogin()|date_format:$datetimeFormatLong}</td>
	</tr>
</table>
</div>
<div class="separator"></div>
<div id="enrollment>">
<h4>{translate key="manager.people.enrollment"}</h4>

{section name=role loop=$userRoles}
	{assign var=roleJournalId value=$userRoles[role]->getJournalId()}
	{if $isSiteAdmin && $lastJournalTitle != $journalTitles[$roleJournalId]}
		{if $notFirstRole}
			</ul>
		{/if}
		{assign var=lastJournalTitle value=$journalTitles[$roleJournalId]}
		<h3>{$lastJournalTitle}</h3>
		{if $notFirstRole}
			<ul>
		{/if}
	{/if}
	{if !$notFirstRole}
		<ul>
		{assign var=notFirstRole value=1}
	{/if}
	<li>
		{translate key=$userRoles[role]->getRoleName()}
		{if $userRoles[role]->getRoleId() != $smarty.const.ROLE_ID_SITE_ADMIN}
			<a href="{url op="unEnroll" path=$userRoles[role]->getRoleId() userId=$user->getId() journalId=$userRoles[role]->getJournalId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.people.confirmUnenroll"}')" class="action">{translate key="manager.people.unenroll"}</a>
		{/if}
	</li>
{/section}
{if $notFirstRole}
	</ul>
{/if}
</div>
{include file="common/footer.tpl"}

