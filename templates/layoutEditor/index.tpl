{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Layout editor index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="layoutEditor.journalLayoutEditor"}
{assign var="pageId" value="layoutEditor.index"}
{include file="common/header.tpl"}

<div class="blockTitle">{translate key="editor.submissionEditing"}</div>
<div class="block">
	<ul>
		<li><a href="{$pageUrl}/layoutEditor/assignments">{translate key="layoutEditor.activeEditorialAssignments"}</a></li>
		<li><a href="{$pageUrl}/layoutEditor/assignments/completed">{translate key="layoutEditor.completedEditorialAssignments"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
