{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal management index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.journalManagement"}
{include file="common/header.tpl"}

<div class="blockTitle">{translate key="manager.journalManagement"}</div>
<div class="block">
	<ul>
		<li><a href="{$pageUrl}/manager/setup">{translate key="manager.setup"}</a></li>
		<li><a href="{$pageUrl}/manager/sections">{translate key="manager.sections"}</a></li>
		<li><a href="{$pageUrl}/manager/emails">{translate key="manager.emails"}</a></li>
		<li><a href="{$pageUrl}/manager/statistics">{translate key="manager.statistics"}</a></li>
		<li><a href="{$pageUrl}/manager/rst">{translate key="manager.researchSupportTool"}</a></li>
	</ul>
</div>

<br />

<div class="blockTitle">{translate key="manager.people"}</div>
<div class="block">
	<ul>
		<li><a href="{$pageUrl}/manager/people/all">{translate key="manager.people.allUsers"}</a></li>
		<li><a href="{$pageUrl}/manager/createUser">{translate key="manager.people.createUser"}</a></li>
		<hr class="blockSeparator" />
		<li><a href="{$pageUrl}/manager/people/managers">{translate key="user.role.managers"}</a></li>
		<li><a href="{$pageUrl}/manager/people/editors">{translate key="user.role.editors"}</a></li>
		<li><a href="{$pageUrl}/manager/people/sectionEditors">{translate key="user.role.sectionEditors"}</a></li>
		<li><a href="{$pageUrl}/manager/people/layoutEditors">{translate key="user.role.layoutEditors"}</a></li>
		<li><a href="{$pageUrl}/manager/people/copyeditors">{translate key="user.role.copyeditors"}</a></li>
		<li><a href="{$pageUrl}/manager/people/proofreaders">{translate key="user.role.proofreaders"}</a></li>
		<li><a href="{$pageUrl}/manager/people/authors">{translate key="user.role.authors"}</a></li>
		<li><a href="{$pageUrl}/manager/people/readers">{translate key="user.role.readers"}</a></li>
	</ul>
</div>

{include file="common/footer.tpl"}
