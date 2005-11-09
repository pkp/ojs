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
<h3>{$group->getGroupTitle()}</h3>
{assign var=groupId value=$group->getGroupId()}
{assign var=members value=$teamInfo[$groupId]}

{assign var=sectionHasBio value=0}
{foreach from=$members item=member}
	{assign var=user value=$member->getUser()}
	{if $user->getBiography()}{assign var=sectionHasBio value=1}{/if}
{/foreach}

<p>
{foreach from=$members item=member}
	{assign var=user value=$member->getUser()}
	<strong>{$user->getFullName()|escape}{if strlen($user->getAffiliation()) > 0}, {$user->getAffiliation()|escape}{/if}</strong>
	<br />
	{if $sectionHasBio}
		{if $user->getBiography()}
			{$user->getBiography()|escape|nl2br}
			<br/>
		{/if}
		<br/>
	{/if}
{/foreach}
</p>
{/foreach}


{include file="common/footer.tpl"}
