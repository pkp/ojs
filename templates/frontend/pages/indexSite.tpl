{**
 * templates/frontend/pages/indexSite.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site index.
 *
 *}
{include file="frontend/components/header.tpl"}

<div class="page">
	{if $intro}{$intro|nl2br}{/if}

	<a name="journals"></a>

	{iterate from=journals item=journal}
		{if $site->getSetting('showThumbnail')}
			{assign var="displayJournalThumbnail" value=$journal->getLocalizedSetting('journalThumbnail')}
			<div style="clear:left;">
			{if $displayJournalThumbnail && is_array($displayJournalThumbnail)}
				{assign var="altText" value=$journal->getLocalizedSetting('journalThumbnailAltText')}
				<div class="homepageImage"><a href="{url journal=$journal->getPath()}" class="action"><img src="{$journalFilesPath}{$journal->getId()}/{$displayJournalThumbnail.uploadName|escape:"url"}" {if $altText != ''}alt="{$altText|escape}"{else}alt="{translate key="common.pageHeaderLogo.altText"}"{/if} /></a></div>
			{/if}
			</div>
		{/if}
		{if $site->getSetting('showTitle')}
			<h3>{$journal->getLocalizedName()|escape}</h3>
		{/if}
		{if $site->getSetting('showDescription')}
			{if $journal->getLocalizedDescription()}
				<p>{$journal->getLocalizedDescription()|nl2br}</p>
			{/if}
		{/if}
		<p><a href="{url journal=$journal->getPath()}" class="action">{translate key="site.journalView"}</a> | <a href="{url journal=$journal->getPath() page="issue" op="current"}" class="action">{translate key="site.journalCurrent"}</a> | <a href="{url journal=$journal->getPath() page="user" op="register"}" class="action">{translate key="site.journalRegister"}</a></p>
	{/iterate}
	{if $journals->wasEmpty()}
		{translate key="site.noJournals"}
	{/if}

	<div id="journalListPageInfo">{page_info iterator=$journals}</div>
	<div id="journalListPageLinks">{page_links anchor="journals" name="journals" iterator=$journals}</div>
</div><!-- .page -->

{include file="common/frontend/footer.tpl"}
