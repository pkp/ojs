{**
 * templates/index/journal.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal index page.
 *
 *}
{strip}
{assign var="pageTitleTranslated" value=$currentJournal->getLocalizedName()}
{include file="common/frontend/header.tpl"}
{/strip}

{$journalDescription}

{call_hook name="Templates::Index::journal"}

{if $homepageImage}
<div class="homepage_image"><img src="{$publicFilesDir}/{$homepageImage.uploadName|escape:"url"}" width="{$homepageImage.width|escape}" height="{$homepageImage.height|escape}" {if $homepageImageAltText != ''}alt="{$homepageImageAltText|escape}"{else}alt="{translate key="common.journalHomepageImage.altText"}"{/if} /></div>
{/if}

{$additionalHomeContent}

{if $enableAnnouncementsHomepage}
	<div class="homepage_announcements">
		<h3>{translate key="announcement.announcementsHome"}</h3>
		{include file="announcements/announcements.tpl" displayLimit=true}
	</div>
{/if}

{if $issue && $currentJournal->getSetting('publishingMode') != $smarty.const.PUBLISHING_MODE_NONE}
	{* Display the table of contents or cover page of the current issue. *}
	<h3>{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</h3>
	{include file="issue/view.tpl"}
{/if}

{if !empty($socialMediaBlocks)}
	<div id="socialMediaBlocksContainer">
	{foreach from=$socialMediaBlocks item=block name=b}
		<div id="socialMediaBlock{$smarty.foreach.b.index}" class="socialMediaBlock pkp_helpers_clear">
			{$block}
		</div>
	{/foreach}
	</div>
{/if}

{include file="common/frontend/footer.tpl"}
