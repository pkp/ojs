{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Section editor index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="sectionEditor.journalSectionEditor"}
{include file="common/header.tpl"}

<div class="blockTitle">{translate key="editor.submissions"}</div>
<div class="block">
	<ul>
		<li><a href="{$requestPageUrl}/assignments">{translate key="sectionEditor.activeEditorialAssignments"}</a></li>
		<li><a href="{$requestPageUrl}/assignments/completed">{translate key="sectionEditor.completedEditorialAssignments"}</a></li>
	</ul>
</div>
{include file="common/footer.tpl"}
