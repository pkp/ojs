{**
 * aboutThisPublishingSystem.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal / About This Publishing System.
 *
 * TODO: Display the image describing the system.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.aboutThisPublishingSystem"}
{include file="common/header.tpl"}

<p>
{if $currentJournal}
{translate key="about.aboutOJSJournal" ojsVersion=$ojsVersion}
{else}
{translate key="about.aboutOJSSite" ojsVersion=$ojsVersion}
{/if}
</p>

<p align="center">
	<img src="{$baseUrl}/templates/images/edprocesslarge.png" width="620" height="701" border="0" alt="" />
</p>

{include file="common/footer.tpl"}
