{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal setup index/intro.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.journalSetup"}
{assign var="pageId" value="manager.setup.index"}
{include file="common/header.tpl"}

<div class="subTitle">{translate key="manager.setup.stepsToJournalSite"}</div>

<br />

<div class="blockTitle">{translate key="manager.setup.managingPublishingSetup"}&nbsp;{help_icon key="$pageId.managingPublishingSetup"}</div>
<div class="block">
	<ol>
		<li><a href="{$pageUrl}/manager/setup/1">{translate key="manager.setup.details"}</a></li>
		<li><a href="{$pageUrl}/manager/setup/2">{translate key="manager.setup.policies"}</a></li>
		<li><a href="{$pageUrl}/manager/setup/3">{translate key="manager.setup.submissions"}</a></li>
		<li><a href="{$pageUrl}/manager/setup/4">{translate key="manager.setup.management"}</a></li>
		<li><a href="{$pageUrl}/manager/setup/5">{translate key="manager.setup.look"}</a></li>
	</ol>
</div>

{include file="common/footer.tpl"}
