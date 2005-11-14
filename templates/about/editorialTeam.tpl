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

{if count($editors) > 0}
<h4>{translate key="user.role.editors"}</h4>
{foreach from=$editors item=editor}
	{$editor->getFullName()|escape}{if $editor->getAffiliation()}, {$editor->getAffiliation()|escape}{/if}{if $editor->getBiography()}&nbsp;<a href="javascript:openRTWindow('{$requestPageUrl}/editorialTeamBio/{$editor->getUserId()}')" class="action">{translate key="user.bio"}</a>{/if}
	<br/>
{/foreach}
<br/>
{/if}

{if count($sectionEditors) > 0}
<h4>{translate key="user.role.sectionEditors"}</h4>

{foreach from=$sectionEditors item=sectionEditor}
	{$sectionEditor->getFullName()|escape}{if $sectionEditor->getAffiliation()}, {$sectionEditor->getAffiliation()|escape}{/if}{if $sectionEditor->getBiography()}&nbsp;<a href="javascript:openRTWindow('{$requestPageUrl}/editorialTeamBio/{$sectionEditor->getUserId()}')" class="action">{translate key="user.bio"}</a>{/if}
	<br/>
{/foreach}
<br/>
{/if}

{if count($layoutEditors) > 0}
<h4>{translate key="user.role.layoutEditors"}</h4>

{foreach from=$layoutEditors item=layoutEditor}
	{$layoutEditor->getFullName()|escape}{if $layoutEditor->getAffiliation()}, {$layoutEditor->getAffiliation()|escape}{/if}{if $layoutEditor->getBiography()}&nbsp;<a href="javascript:openRTWindow('{$requestPageUrl}/editorialTeamBio/{$layoutEditor->getUserId()}')" class="action">{translate key="user.bio"}</a>{/if}
	<br/>
{/foreach}
<br/>
{/if}


{include file="common/footer.tpl"}
