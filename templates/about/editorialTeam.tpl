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
<h3>{translate key="user.role.editors"}</h3>

{assign var=sectionHasBio value=0}
{foreach from=$editors item=editor}
	{if $editor->getBiography()}{assign var=sectionHasBio value=1}{/if}
{/foreach}

<p>
{foreach from=$editors item=editor}
	<strong>{$editor->getFullName()|escape}{if strlen($editor->getAffiliation()) > 0}, {$editor->getAffiliation()|escape}{/if}</strong>
	<br />
	{if $sectionHasBio}
		{if $editor->getBiography()}
			{$editor->getBiography()|escape|nl2br}
			<br/>
		{/if}
		<br/>
	{/if}
{/foreach}
</p>
{/if}

{if count($sectionEditors) > 0}
<h3>{translate key="user.role.sectionEditors"}</h3>

{assign var=sectionHasBio value=0}
{foreach from=$sectionEditors item=editor}
	{if $editor->getBiography()}{assign var=sectionHasBio value=1}{/if}
{/foreach}

<p>
{foreach from=$sectionEditors item=sectionEditor}
	<strong>{$sectionEditor->getFullName()|escape}{if strlen($sectionEditor->getAffiliation()) > 0}, {$sectionEditor->getAffiliation()|escape}{/if}</strong>
	<br/>
	{if $sectionHasBio}
		{if $sectionEditor->getBiography()}
			{$sectionEditor->getBiography()|escape|nl2br}
			<br/>
		{/if}
		<br/>
	{/if}
{/foreach}
</p>
{/if}

{if count($layoutEditors) > 0}
<h3>{translate key="user.role.layoutEditors"}</h3>

{assign var=sectionHasBio value=0}
{foreach from=$layoutEditors item=editor}
	{if $editor->getBiography()}{assign var=sectionHasBio value=1}{/if}
{/foreach}

<p>
{foreach from=$layoutEditors item=layoutEditor}
	<strong>{$layoutEditor->getFullName()|escape}{if strlen($layoutEditor->getAffiliation()) > 0}, {$layoutEditor->getAffiliation()|escape}{/if}</strong>
	<br/>
	{if $sectionHasBio}
		{if $layoutEditor->getBiography()}
			{$layoutEditor->getBiography()|escape|nl2br}
			<br/>
		{/if}
		<br/>
	{/if}
{/foreach}
</p>
{/if}


{include file="common/footer.tpl"}
