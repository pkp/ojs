{**
 * index.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.aboutTheJournal"}
{include file="common/header.tpl"}

<h3>{translate key="about.people"}</h3>
<ul class="plain">
	{if not (empty($journalSettings.mailingAddress) && empty($journalSettings.contactName) && empty($journalSettings.contactAffiliation) && empty($journalSettings.contactMailingAddress) && empty($journalSettings.contactPhone) && empty($journalSettings.contactFax) && empty($journalSettings.contactEmail) && empty($journalSettings.supportName) && empty($journalSettings.supportPhone) && empty($journalSettings.supportEmail))}
		<li>&#187; <a href="{$pageUrl}/about/contact">{translate key="about.contact"}</a></li>
	{/if}
	<li>&#187; <a href="{$pageUrl}/about/editorialTeam">{translate key="about.editorialTeam"}</a></li>
</ul>

<br />

<h3>{translate key="about.policies"}</h3>
<ul class="plain">
	{if !empty($journalSettings.focusScopeDesc)}<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#focusAndScope">{translate key="about.focusAndScope"}</a></li>{/if}
	<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#sectionPolicies">{translate key="about.sectionPolicies"}</a></li>
	{if !empty($journalSettings.reviewPolicy)}<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#peerReviewProcess">{translate key="about.peerReviewProcess"}</a></li>{/if}
	{if !empty($journalSettings.pubFreqPolicy)}<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#publicationFrequency">{translate key="about.publicationFrequency"}</a></li>{/if}
	{if !empty($journalSettings.openAccessPolicy)}<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#openAccessPolicy">{translate key="about.openAccessPolicy"}</a></li>{/if}
	{if $enableSubscriptions}
		<li>&#187; <a href="{$pageUrl}/about/subscriptions">{translate key="about.subscriptions"}</a></li>
	{/if}
</ul>

<br />

<h3>{translate key="about.submissions"}</h3>
<ul class="plain">
	<li>&#187; <a href="{$pageUrl}/about/submissions#onlineSubmissions">{translate key="about.onlineSubmissions"}</a></li>
	{if !empty($journalSettings.authorGuidelines)}<li>&#187; <a href="{$pageUrl}/about/submissions#authorGuidelines">{translate key="about.authorGuidelines"}</a></li>{/if}
	{if !empty($journalSettings.copyrightNotice)}<li>&#187; <a href="{$pageUrl}/about/submissions#copyrightNotice">{translate key="about.copyrightNotice"}</a></li>{/if}
	{if !empty($journalSettings.privacyStatement)}<li>&#187; <a href="{$pageUrl}/about/submissions#privacyStatement">{translate key="about.privacyStatement"}</a></li>{/if}
</ul>

<br />

<h3>{translate key="about.other"}</h3>
<ul class="plain">
	{if not (empty($journalSettings.publisher) && empty($journalSettings.contributorNote) && empty($journalSettings.contributors) && empty($journalSettings.sponsorNote) && empty($journalSettings.sponsors))}<li>&#187; <a href="{$pageUrl}/about/journalSponsorship">{translate key="about.journalSponsorship"}</a></li>{/if}
	{foreach key=key from=$customAboutItems item=customAboutItem}
		{if $customAboutItem.title!=''}<li>&#187; <a href="{$pageUrl}/about/editorialPolicies#custom{$key}">{$customAboutItem.title|escape}</a></li>{/if}
	{/foreach}
	<li>&#187; <a href="{$pageUrl}/about/siteMap">{translate key="about.siteMap"}</a></li>
	<li>&#187; <a href="{$pageUrl}/about/aboutThisPublishingSystem">{translate key="about.aboutThisPublishingSystem"}</a></li>
</ul>

<br />


{include file="common/footer.tpl"}
