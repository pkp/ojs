{**
 * templates/about/site.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal site.
 *
 *}
{strip}
{assign var="pageTitle" value="about.aboutSite"}
{include file="common/header.tpl"}
{/strip}

{if !empty($about)}
	<div id="about">
	<p>{$about|nl2br}</p>
	</div>
{/if}

<div id="journals">
<h3>{translate key="journal.journals"}</h3>
<ul>
{iterate from=journals item=journal}
	<li><a href="{url journal=$journal->getPath() page="about" op="index"}">{$journal->getLocalizedTitle()|escape}</a></li>
{/iterate}
</ul>
</div>

<a href="{url op="aboutThisPublishingSystem"}">{translate key="about.aboutThisPublishingSystem"}</a>

{include file="common/footer.tpl"}

