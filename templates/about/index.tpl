{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal index.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.aboutTheJournal"}
{include file="common/header.tpl"}

<div class="blockTitle">{translate key="about.aboutTheJournal"}</div>
<div class="block">
	
	<br />

	<div class="blockSubtitle">{translate key="about.people"}</div>
	<ul class="sidebar">
		<li><a href="{$pageUrl}/about/contact">{translate key="about.contact"}</a></li>
		<li><a href="{$pageUrl}/about/editorialTeam">{translate key="about.editorialTeam"}</a></li>
	</ul>

	<br />
	
	<div class="blockSubtitle">{translate key="about.policies"}</div>
	<ul class="sidebar">
		<li><a href="{$pageUrl}/about/editorialPolicies#focusAndScope">{translate key="about.focusAndScope"}</a></li>
		<li><a href="{$pageUrl}/about/editorialPolicies#sectionPolicies">{translate key="about.sectionPolicies"}</a></li>
		<li><a href="{$pageUrl}/about/submissions#privacyStatement">{translate key="about.privacyStatement"}</a></li>
		<li><a href="{$pageUrl}/about/editorialPolicies#publicationFrequency">{translate key="about.publicationFrequency"}</a></li>
		<li><a href="{$pageUrl}/about/editorialPolicies#openAccessPolicy">{translate key="about.openAccessPolicy"}</a></li>
	</ul>

	<br />
	
	<div class="blockSubtitle">{translate key="about.submissions"}</div>
	<ul class="sidebar">
		<li><a href="{$pageUrl}/about/submissions#onlineSubmissions">{translate key="about.onlineSubmissions"}</a></li>
		<li><a href="{$pageUrl}/about/submissions#authorGuidelines">{translate key="about.authorGuidelines"}</a></li>
		<li><a href="{$pageUrl}/about/editorialPolicies#peerReviewProcess">{translate key="about.peerReviewProcess"}</a></li>	
		<li><a href="{$pageUrl}/about/submissions#copyrightNotice">{translate key="about.copyrightNotice"}</a></li>
	</ul>

	<br />

	<div class="blockSubtitle">{translate key="about.other"}</div>
	<ul class="sidebar">
		<li><a href="{$pageUrl}/about/journalSponsorship">{translate key="about.journalSponsorship"}</a></li>
		{foreach key=key from=$customAboutItems item=customAboutItem}
			<li><a href="{$pageUrl}/about/editorialPolicies#custom{$key}">{$customAboutItem.title}</a></li>
		{/foreach}
		<li><a href="{$pageUrl}/about/siteMap">{translate key="about.siteMap"}</a></li>
		<li><a href="{$pageUrl}/about/aboutThisPublishingSystem">{translate key="about.aboutThisPublishingSystem"}</a></li>
	</ul>

	<br />

</div>

{include file="common/footer.tpl"}
