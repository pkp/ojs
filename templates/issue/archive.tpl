{**
 * archive.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Issue Archive.
 *
 * $Id$
 *}

{assign var="hierarchyCurrentTitle" value=$issueIdentification}
{assign var="pageTitle" value=$issueTitle}
{assign var="currentUrl" value="$pageUrl/issue/archive/$issueId"}
{include file="issue/header.tpl"}

<div>
	<span>{translate key="archive.browse"}:&nbsp;<select name="issue" onchange="location.href='{$requestPageUrl}/archive/'+this.options[this.selectedIndex].value" size="1" class="selectMenu">{html_options options=$issueOptions selected=$issueId}</select></span>
</div>

<br />

{include file="issue/issue.tpl"}

{include file="common/footer.tpl"}
