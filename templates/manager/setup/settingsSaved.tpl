{**
 * stepSaved.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show confirmation after saving settings.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.journalSetup"}
{include file="common/header.tpl"}

{if $step == 1}
<div><span class="disabledText">&lt;&lt; {translate key="manager.setup.previousStep"}</span> | <a href="{$pageUrl}/manager/setup/2">{translate key="manager.setup.nextStep"} &gt;&gt;</a></div>

{elseif $step == 2}
<div><a href="{$pageUrl}/manager/setup/1">&lt;&lt; {translate key="manager.setup.previousStep"}</a> | <a href="{$pageUrl}/manager/setup/3">{translate key="manager.setup.nextStep"} &gt;&gt;</a></div>

{elseif $step == 3}
<div><a href="{$pageUrl}/manager/setup/2">&lt;&lt; {translate key="manager.setup.previousStep"}</a> | <a href="{$pageUrl}/manager/setup/4">{translate key="manager.setup.nextStep"} &gt;&gt;</a></div>

{elseif $step == 4}
<div><a href="{$pageUrl}/manager/setup/3">&lt;&lt; {translate key="manager.setup.previousStep"}</a> | <a href="{$pageUrl}/manager/setup/5">{translate key="manager.setup.nextStep"} &gt;&gt;</a></div>

{elseif $step == 5}
<div><a href="{$pageUrl}/manager/setup/4">&lt;&lt; {translate key="manager.setup.previousStep"}</a> | <span class="disabledText">{translate key="manager.setup.nextStep"} &gt;&gt;</span></div>
{/if}

<br />

{translate key="manager.setup.journalSetupUpdated"}
<br /><br />
<a href="{$pageUrl}/manager/setup/{$step}">{translate key="common.back"}</a>

{include file="common/footer.tpl"}
