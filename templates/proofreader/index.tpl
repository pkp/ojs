{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Proofreader index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="proofreader.journalProofreader"}
{include file="common/header.tpl"}

<div class="blockTitle">{translate key="editor.submissionEditing"}</div>
<div class="block">
	<ul>
		<li><a href="{$pageUrl}/proofreader/assignments">{translate key="proofreader.activeAssignments"}</a></li>
		<li><a href="{$pageUrl}/proofreader/assignments/completed">{translate key="proofreader.completedAssignments"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
