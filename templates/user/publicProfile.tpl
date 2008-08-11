{**
 * publicProfile.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Public user profile display.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="user.profile.publicProfile"}
{url|assign:"url" op="profile"}{include file="common/header.tpl"}
{/strip}

<div id="profilePicContent" style="float: right;">
	{assign var="profileImage" value=$user->getSetting('profileImage')}
	{if $profileImage}
		<img height="{$profileImage.height|escape}" width="{$profileImage.width|escape}" alt="{translate key="user.profile.profileImage"}" src="{$sitePublicFilesDir}/{$profileImage.uploadName}" />
	{/if}
</div>

<div id="mainContent">

<h4>
	{$user->getFullName()|escape}
	{if $isUserLoggedIn}
		{url|assign:"mailUrl" page="user" op="email" to=$user->getEmail()|to_array}
		{icon name="mail" url=$mailUrl}
	{/if}
</h4>

<table class="listing" width="100%">
	{if $user->getAffiliation()}
		<tr valign="top">
			<td class="label" width="20%">
				{translate key="user.affiliation"}
			</td>
			<td class="data" width="80%">
				{$user->getAffiliation()|escape}
			</td>
		</tr>
	{/if}{* $user->getAffiliation() *}

	{if $user->getUserBiography()}
		<tr valign="top">
			<td class="label">
				{translate key="user.biography"}
			</td>
			<td class="data">
				{$user->getUserBiography()|strip_unsafe_html}
			</td>
		</tr>
	{/if}{* $user->getUserBiography() *}
</table>

</div>{* id="mainContent" *}

{include file="common/footer.tpl"}
