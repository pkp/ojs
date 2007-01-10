{**
 * displayMembership.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display group membership information.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.people"}
{include file="common/header.tpl"}

<h4>{$group->getGroupTitle()}</h4>
{assign var=groupId value=$group->getGroupId()}

{foreach from=$memberships item=member}
	{assign var=user value=$member->getUser()}
	<a href="javascript:openRTWindow('{url op="editorialTeamBio" path=$user->getUserId()}')">{$user->getFullName()|escape}</a>{if $user->getAffiliation()}, {$user->getAffiliation()|escape}{/if}{if $user->getCountry()}{assign var=countryCode value=$user->getCountry()}{assign var=country value=$countries.$countryCode}, {$country}{/if}
	<br />
{/foreach}

{include file="common/footer.tpl"}
