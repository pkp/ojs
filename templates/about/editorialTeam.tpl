{**
 * editorialTeam.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.editorialTeam"}
{include file="common/header.tpl"}

{if count($editors) > 0}
	<h3>{translate key="editor.journalEditor"}</h3>

	{foreach from=$editors item=editor}
		{$editor->getFullName()}{if strlen($editor->getAffiliation()) > 0}, {$editor->getAffiliation()}{/if}
	{/foreach}
{/if}

{if count($sectionEditors) > 0}
	<h3>{translate key="sectionEditor.journalSectionEditor"}</h3>
	{foreach from=$sectionEditors item=sectionEditor}
		{$sectionEditor->getFullName()}
		{if strlen($sectionEditor->getAffiliation()) > 0}
			, {$sectionEditor->getAffiliation()}
		{/if}
		<br/>
	{/foreach}
{/if}

{if count($layoutEditors) > 0}
	<h3>{translate key="layoutEditor.journalLayoutEditor"}</h3>
	{foreach from=$layoutEditors item=layoutEditor}
		{$layoutEditor->getFullName()}
		{if strlen($layoutEditor->getAffiliation()) > 0}
			, {$layoutEditor->getAffiliation()}
		{/if}
		<br/>
	{/foreach}
{/if}


{include file="common/footer.tpl"}
