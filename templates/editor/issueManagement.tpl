{**
 * issueManagement.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Issue Management in Publishing
 *
 * $Id$
 *}

{assign var="pageId" value="editor.issueManagement"}
{include file="common/header.tpl"}

<div id="topSelectBar">
	<span>{translate key="editor.issues.liveIssues"}:&nbsp;<select name="issue" onchange="location.href='{$requestPageUrl}/issueManagement/{$subsection}/'+this.options[this.selectedIndex].value" size="1" class="selectMenu">{html_options options=$issueOptions selected=$issueId}</select></span>
</div>

{include file="editor/issues/$subsection.tpl"}

{include file="common/footer.tpl"}
