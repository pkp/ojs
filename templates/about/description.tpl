{**
 * templates/about/description.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Description of the journal.
 *}
{strip}
{assign var="pageTitle" value="about.description"}
{include file="common/header.tpl"}
{/strip}

{url|assign:editUrl page="management" op="settings" path="journal" anchor="masthead"}
{include file="common/linkToEditPage.tpl" editUrl=$editUrl}

<div id="description">
	{$currentJournal->getLocalizedSetting('description')|nl2br}
</div>

{include file="common/footer.tpl"}
