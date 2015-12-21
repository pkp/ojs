{**
 * templates/frontend/pages/indexJournal.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display the index page for a journal
 *
 * @uses $currentJournal Journal This journal
 * @uses $journalDescription string Journal description from HTML text editor
 * @uses $homepageImage object Image to be displayed on the homepage
 * @uses $additionalHomeContent string Arbitrary input from HTML text editor
 * @uses $enableAnnouncementsHomepage bool Should we display announcements here?
 * @uses $issue Issue Current issue
 * @uses $socialMediaBlocks @todo
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$currentJournal->getLocalizedName()}

<div class="page">

	{$journalDescription}

	{call_hook name="Templates::Index::journal"}

	{if $homepageImage}
		<div class="homepage_image">
			<img src="{$publicFilesDir}/{$homepageImage.uploadName|escape:"url"}" width="{$homepageImage.width|escape}" height="{$homepageImage.height|escape}" {if $homepageImageAltText != ''}alt="{$homepageImageAltText|escape}"{else}alt="{translate key="common.journalHomepageImage.altText"}"{/if}>
		</div>
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
		{include file="frontend/objects/issue_toc.tpl"}
	{/if}

	{* Social media sharing blocks *}
	{* @todo this hasn't been formatted or styled. May be removed *}
	{if !empty($socialMediaBlocks)}
		<div id="socialMediaBlocksContainer">
		{foreach from=$socialMediaBlocks item=block name=b}
			<div id="socialMediaBlock{$smarty.foreach.b.index}" class="socialMediaBlock pkp_helpers_clear">
				{$block}
			</div>
		{/foreach}
		</div>
	{/if}

</div><!-- .page -->

{include file="frontend/components/footer.tpl"}
