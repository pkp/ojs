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
<div class="subTitle">{translate key="editor.journalEditor"}</div>
<table class="plain" width="100%">
{foreach from=$editors item=editor}
<tr class="{cycle values="row,rowAlt"}">
	<td width="100%">
		{$editor->getFullName()}{if strlen($editor->getAffiliation()) > 0}, {$editor->getAffiliation()}{/if}
	</td>
</tr>
{/foreach}
</table>
{/if}

{if count($sectionEditors) > 0}
<div class="subTitle">{translate key="sectionEditor.journalSectionEditor"}</div>
<table class="plain" width="100%">
{foreach from=$sectionEditors item=sectionEditor}
<tr class="{cycle values="row,rowAlt"}">
	<td width="100%">
		{$sectionEditor->getFullName()}
		{if strlen($sectionEditor->getAffiliation()) > 0}
			, {$sectionEditor->getAffiliation()}
		{/if}
	</td>
</tr>
{/foreach}
</table>
{/if}

{if count($layoutEditors) > 0}
<div class="subTitle">{translate key="layoutEditor.journalLayoutEditor"}</div>
<table class="plain">
{foreach from=$layoutEditors item=layoutEditor}
<tr class="{cycle values="row,rowAlt"}">
	<td width="100%">
		{$layoutEditor->getFullName()}
		{if strlen($layoutEditor->getAffiliation()) > 0}
			, {$layoutEditor->getAffiliation()}
		{/if}
	</td>
</tr>
{/foreach}
</table>
{/if}


{include file="common/footer.tpl"}
