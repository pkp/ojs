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
	<li><a href="{$pageUrl}/manager/setup/1">{translate key="manager.setup.details"}</a></li>
	<li><a href="{$pageUrl}/manager/setup/2">{translate key="manager.setup.policies"}</a></li>
	<li><a href="{$pageUrl}/manager/setup/3">{translate key="manager.setup.submissions"}</a></li>
	<li><a href="{$pageUrl}/manager/setup/4">{translate key="manager.setup.management"}</a></li>
	<li><a href="{$pageUrl}/manager/setup/5">{translate key="manager.setup.look"}</a></li>
</ol>

{translate key="manager.setup.setupDescription"}


<h4>{translate key="manager.setup.managementOverview"}</h4>

{translate key="manager.setup.overviewDescription"}

{include file="common/footer.tpl"}
