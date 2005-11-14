{**
 * editorialTeam.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.editorialTeam"}
{include file="common/header.tpl"}

{foreach from=$groups item=group}
<h4>{$group->getGroupTitle()}</h4>
{assign var=groupId value=$group->getGroupId()}
{assign var=members value=$teamInfo[$groupId]}

{foreach from=$members item=member}
	{assign var=user value=$member->getUser()}
	{$user->getFullName()|escape}{if $user->getAffiliation()}, {$user->getAffiliation()|escape}{/if}{if $user->getBiography()}&nbsp;<a href="javascript:openRTWindow('{$requestPageUrl}/editorialTeamBio/{$user->getUserId()}')" class="action">{translate key="user.bio"}</a>{/if}
	<br />
{/foreach}
<br/>
{/foreach}


{include file="common/footer.tpl"}
