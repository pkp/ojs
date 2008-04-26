{**
 * site.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site index.
 *
 * $Id$
 *}
{if $siteTitle}{assign var="pageTitleTranslated" value=$siteTitle}{/if}
{include file="common/header.tpl"}

<br />

{if $intro}
<p>{$intro|nl2br}</p>
{/if}

{iterate from=journals item=journal}

	{assign var="displayHomePageImage" value=$journal->getLocalizedSetting('homepageImage')}
	{assign var="displayHomePageLogo" value=$journal->getJournalPageHeaderLogo(true)}
	{assign var="displayPageHeaderLogo" value=$journal->getJournalPageHeaderLogo()}

	<div style="clear:left;">
	{if $displayHomePageImage && is_array($displayHomePageImage)}
		<div class="homepageImage"><a href="{url journal=$journal->getPath()}" class="action"><img src="{$journalFilesPath}{$journal->getJournalId()}/{$displayHomePageImage.uploadName|escape:"url"}" {if $displayPageHeaderLogo.altText != ''}alt="{$displayPageHeaderLogo.altText|escape}"{else}alt="{translate key="common.pageHeaderLogo.altText"}"{/if} /></a></div>
	{elseif $displayHomePageLogo && is_array($displayHomePageLogo)}
		<div class="homepageImage"><a href="{url journal=$journal->getPath()}" class="action"><img src="{$journalFilesPath}{$journal->getJournalId()}/{$displayHomePageLogo.uploadName|escape:"url"}" {if $displayPageHeaderLogo.altText != ''}alt="{$displayPageHeaderLogo.altText|escape}"{else}alt="{translate key="common.pageHeaderLogo.altText"}"{/if} /></a></div>
	{elseif $displayPageHeaderLogo && is_array($displayPageHeaderLogo)}
		<div class="homepageImage"><a href="{url journal=$journal->getPath()}" class="action"><img src="{$journalFilesPath}{$journal->getJournalId()}/{$displayPageHeaderLogo.uploadName|escape:"url"}" {if $displayPageHeaderLogo.altText != ''}alt="{$displayPageHeaderLogo.altText|escape}"{else}alt="{translate key="common.pageHeaderLogo.altText"}"{/if} /></a></div>
	{/if}
	</div>

	<h3>{$journal->getJournalTitle()|escape}</h3>
	{if $journal->getJournalDescription()}
		<p>{$journal->getJournalDescription()|nl2br}</p>
	{/if}

	<p><a href="{url journal=$journal->getPath()}" class="action">{translate key="site.journalView"}</a> | <a href="{url journal=$journal->getPath() page="issue" op="current"}" class="action">{translate key="site.journalCurrent"}</a> | <a href="{url journal=$journal->getPath() page="user" op="register"}" class="action">{translate key="site.journalRegister"}</a></p>
{/iterate}

{include file="common/footer.tpl"}
