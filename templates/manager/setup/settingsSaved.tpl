{**
 * templates/manager/setup/settingsSaved.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Show confirmation after saving settings.
 *
 *}
{assign var="pageTitle" value="manager.setup.journalSetup"}
{include file="manager/setup/setupHeader.tpl"}

<p>{translate key="manager.setup.journalSetupUpdated"}</p>

{if $setupStep == 1}
<div><span class="disabled">&lt;&lt; {translate key="navigation.previousStep"}</span> | <a href="{url op="setup" path="2"}">{translate key="navigation.nextStep"} &gt;&gt;</a></div>

{elseif $setupStep == 2}
<div><a href="{url op="setup" path="1"}">&lt;&lt; {translate key="navigation.previousStep"}</a> | <a href="{url op="setup" path="3"}">{translate key="navigation.nextStep"} &gt;&gt;</a></div>

{elseif $setupStep == 3}
<div><a href="{url op="setup" path="2"}">&lt;&lt; {translate key="navigation.previousStep"}</a> | <a href="{url op="setup" path="4"}">{translate key="navigation.nextStep"} &gt;&gt;</a></div>

{elseif $setupStep == 4}
<div><a href="{url op="setup" path="3"}">&lt;&lt; {translate key="navigation.previousStep"}</a> | <a href="{url op="setup" path="5"}">{translate key="navigation.nextStep"} &gt;&gt;</a></div>

{elseif $setupStep == 5}
<div><a href="{url op="setup" path="4"}">&lt;&lt; {translate key="navigation.previousStep"}</a> | <span class="disabled">{translate key="navigation.nextStep"} &gt;&gt;</span></div>
{/if}

{include file="common/footer.tpl"}

