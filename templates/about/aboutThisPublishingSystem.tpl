{**
 * templates/about/aboutThisPublishingSystem.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal / About This Publishing System.
 *
 * TODO: Display the image describing the system.
 *
 *}
{strip}
{assign var="pageTitle" value="about.aboutThisPublishingSystem"}
{include file="common/header.tpl"}
{/strip}

<p id="aboutThisPublishingSystem">
{if $currentJournal}
	{translate key="about.aboutOJSJournal" ojsVersion=$appVersion}
{else}
	{translate key="about.aboutOJSSite" ojsVersion=$appVersion}
{/if}
</p>

<p align="center">
	<img src="{$baseUrl}/{$pubProcessFile}" style="border: 0;" alt="{translate key="about.aboutThisPublishingSystem.altText"}" />
</p>

{include file="common/footer.tpl"}
