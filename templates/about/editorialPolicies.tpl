{**
 * templates/about/editorialPolicies.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the journal / Editorial Policies.
 *}
{strip}
{assign var="pageTitle" value="about.editorialPolicies"}
{include file="common/header.tpl"}
{/strip}

{url|assign:editUrl page="management" op="settings" path="journal" anchor="policies"}
{include file="common/linkToEditPage.tpl" editUrl=$editUrl}

{if $currentJournal->getLocalizedSetting('focusScopeDesc') != ''}
	<div id="focusAndScope"><h3>{translate key="about.focusAndScope"}</h3>
		<p>{$currentJournal->getLocalizedSetting('focusScopeDesc')|nl2br}</p>
	</div>
	<div class="separator"></div>
{/if}

{if $currentJournal->getLocalizedSetting('reviewPolicy') != ''}
	<div id="peerReviewProcess">
		<h3>{translate key="about.peerReviewProcess"}</h3>
		<p>{$currentJournal->getLocalizedSetting('reviewPolicy')|nl2br}</p>
	</div>
	<div class="separator"></div>
{/if}

{if $currentJournal->getLocalizedSetting('openAccessPolicy') != ''}
	<div id="openAccessPolicy">
		<h3>{translate key="about.openAccessPolicy"}</h3>
		<p>{$currentJournal->getLocalizedSetting('openAccessPolicy')|nl2br}</p>
	</div>
	<div class="separator"></div>
{/if}

{foreach key=key from=$currentJournal->getLocalizedSetting('customAboutItems') item=customAboutItem name=customAboutItems}
	{if !empty($customAboutItem.title)}
		<div id="custom-{$key|escape}"><h3>{$customAboutItem.title|escape}</h3>
			<p>{$customAboutItem.content|nl2br}</p>
		</div>
		{if !$smarty.foreach.customAboutItems.last}<div class="separator"></div>{/if}
	{/if}
{/foreach}

{include file="common/footer.tpl"}
