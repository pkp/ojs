{**
 * templates/about/displayMembership.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display group membership information.
 *
 *}
{strip}
{assign var="pageTitle" value="about.people"}
{include file="common/header.tpl"}
{/strip}

<div id="displayMembership">
<h4>{$group->getLocalizedTitle()}</h4>
{assign var=groupId value=$group->getId()}

{foreach from=$memberships item=member}
	{assign var=user value=$member->getUser()}
	<div class="member"><a href="javascript:openRTWindow('{url op="editorialTeamBio" path=$user->getId()}')">{$user->getFullName()|escape}</a>{if $user->getLocalizedAffiliation()}, {$user->getLocalizedAffiliation()|escape}{/if}{if $user->getCountry()}{assign var=countryCode value=$user->getCountry()}{assign var=country value=$countries.$countryCode}, {$country|escape}{/if}</div>
	<br />
{/foreach}
</div>

{include file="common/footer.tpl"}

