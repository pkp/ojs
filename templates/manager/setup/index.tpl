{**
 * index.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal setup index/intro.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.journalSetup"}
{include file="common/header.tpl"}

<h4>{translate key="manager.setup.stepsToJournalSite"}</h4>

<ol class="medium">
	<li>
		<a href="{$pageUrl}/manager/setup/1">{translate key="manager.setup.details"}</a><br/>
		{translate key="manager.setup.details.description"}<br/>
		&nbsp;
	</li>
	<li>
		<a href="{$pageUrl}/manager/setup/2">{translate key="manager.setup.policies"}</a><br/>
		{translate key="manager.setup.policies.description"}<br/>
		&nbsp;
	</li>
	<li>
		<a href="{$pageUrl}/manager/setup/3">{translate key="manager.setup.submissions"}</a><br/>
		{translate key="manager.setup.submissions.description"}<br/>
		&nbsp;
	</li>
	<li>
		<a href="{$pageUrl}/manager/setup/4">{translate key="manager.setup.management"}</a><br/>
		{translate key="manager.setup.management.description"}<br/>
		&nbsp;
	</li>
	<li>
		<a href="{$pageUrl}/manager/setup/5">{translate key="manager.setup.look"}</a><br/>
		{translate key="manager.setup.look.description"}<br/>
		&nbsp;
	</li>
</ol>

{translate key="manager.setup.setupDescription"}


<h4>{translate key="manager.setup.managementOverview"}</h4>

{translate key="manager.setup.overviewDescription"}

{include file="common/footer.tpl"}
