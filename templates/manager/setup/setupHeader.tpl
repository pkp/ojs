{**
 * setupHeader.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Header for journal setup pages.
 *
 * $Id$
 *}

{assign var="pageCrumbTitle" value="manager.setup.journalSetup"}
{assign var="currentUrl" value="$pageUrl/manager/setup"}
{include file="common/header.tpl"}

<ul class="steplist">
	<li{if $setupStep == 1} class="current"{/if}><a href="{$pageUrl}/manager/setup/1">1. {translate key="manager.setup.details"}</a></li>
	<li{if $setupStep == 2} class="current"{/if}><a href="{$pageUrl}/manager/setup/2">2. {translate key="manager.setup.policies"}</a></li>
	<li{if $setupStep == 3} class="current"{/if}><a href="{$pageUrl}/manager/setup/3">3. {translate key="manager.setup.submissions"}</a></li>
	<li{if $setupStep == 4} class="current"{/if}><a href="{$pageUrl}/manager/setup/4">4. {translate key="manager.setup.management"}</a></li>
	<li{if $setupStep == 5} class="current"{/if}><a href="{$pageUrl}/manager/setup/5">5. {translate key="manager.setup.look"}</a></li>
</ul>
