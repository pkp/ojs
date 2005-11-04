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

<h3>{translate key="manager.managementPages"}</h3>

<ul class="plain">
	<li>&#187; <a href="{$pageUrl}/manager/setup">{translate key="manager.setup"}</a></li>
	<li>&#187; <a href="{$pageUrl}/manager/sections">{translate key="section.sections"}</a></li>
	<li>&#187; <a href="{$pageUrl}/manager/emails">{translate key="manager.emails"}</a></li>
	<li>&#187; <a href="{$pageUrl}/manager/languages">{translate key="common.languages"}</a></li>
	<li>&#187; <a href="{$pageUrl}/rtadmin">{translate key="manager.readingTools"}</a></li>
	<li>&#187; <a href="{$pageUrl}/manager/importexport">{translate key="manager.importExport"}</a></li>
	<li>&#187; <a href="{$pageUrl}/manager/files">{translate key="manager.filesBrowser"}</a></li>
	<li>&#187; <a href="{$pageUrl}/manager/plugins">{translate key="manager.plugins"}</a></li>
	{if $subscriptionsEnabled}
	<li>&#187; <a href="{$pageUrl}/manager/subscriptions">{translate key="manager.subscriptions"}</a></li>
	{/if}
	{call_hook name="Templates::Manager::Index::ManagementPages"}
</ul>


<h3>{translate key="manager.users"}</h3>

<ul class="plain">
	<li>&#187; <a href="{$pageUrl}/manager/people/all">{translate key="manager.people.allUsers"}</a></li>
	<li>&#187; <a href="{$pageUrl}/manager/createUser">{translate key="manager.people.createUser"}</a></li>
	{call_hook name="Templates::Manager::Index::Users"}
</ul>


<h3>{translate key="manager.roles"}</h3>

<ul class="plain">
	<li>&#187; <a href="{$pageUrl}/manager/people/managers">{translate key="user.role.managers"}</a></li>
	<li>&#187; <a href="{$pageUrl}/manager/people/editors">{translate key="user.role.editors"}</a></li>
	<li>&#187; <a href="{$pageUrl}/manager/people/sectionEditors">{translate key="user.role.sectionEditors"}</a></li>
	<li>&#187; <a href="{$pageUrl}/manager/people/layoutEditors">{translate key="user.role.layoutEditors"}</a></li>
	<li>&#187; <a href="{$pageUrl}/manager/people/reviewers">{translate key="user.role.reviewers"}</a></li>
	<li>&#187; <a href="{$pageUrl}/manager/people/copyeditors">{translate key="user.role.copyeditors"}</a></li>
	<li>&#187; <a href="{$pageUrl}/manager/people/proofreaders">{translate key="user.role.proofreaders"}</a></li>
	<li>&#187; <a href="{$pageUrl}/manager/people/authors">{translate key="user.role.authors"}</a></li>
	<li>&#187; <a href="{$pageUrl}/manager/people/readers">{translate key="user.role.readers"}</a></li>
	{call_hook name="Templates::Manager::Index::Roles"}
</ul>

{include file="common/footer.tpl"}
