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

<h3>{translate key="about.people"}</h3>
<ul class="plain">
	<li>&#187;<a href="{$pageUrl}/about/contact">{translate key="about.contact"}</a></li>
	<li>&#187;<a href="{$pageUrl}/about/editorialTeam">{translate key="about.editorialTeam"}</a></li>
</ul>

<br />

<h3>{translate key="about.policies"}</h3>
<ul class="plain">
	<li>&#187;<a href="{$pageUrl}/about/editorialPolicies#focusAndScope">{translate key="about.focusAndScope"}</a></li>
	<li>&#187;<a href="{$pageUrl}/about/editorialPolicies#sectionPolicies">{translate key="about.sectionPolicies"}</a></li>
	<li>&#187;<a href="{$pageUrl}/about/submissions#privacyStatement">{translate key="about.privacyStatement"}</a></li>
	<li>&#187;<a href="{$pageUrl}/about/editorialPolicies#publicationFrequency">{translate key="about.publicationFrequency"}</a></li>
	<li>&#187;<a href="{$pageUrl}/about/editorialPolicies#openAccessPolicy">{translate key="about.openAccessPolicy"}</a></li>
</ul>

<br />

<h3>{translate key="about.submissions"}</h3>
<ul class="plain">
	<li>&#187;<a href="{$pageUrl}/about/submissions#onlineSubmissions">{translate key="about.onlineSubmissions"}</a></li>
	<li>&#187;<a href="{$pageUrl}/about/submissions#authorGuidelines">{translate key="about.authorGuidelines"}</a></li>
	<li>&#187;<a href="{$pageUrl}/about/editorialPolicies#peerReviewProcess">{translate key="about.peerReviewProcess"}</a></li>	
	<li>&#187;<a href="{$pageUrl}/about/submissions#copyrightNotice">{translate key="about.copyrightNotice"}</a></li>
</ul>

<br />

<h3>{translate key="about.other"}</h3>
<ul class="plain">
	<li>&#187;<a href="{$pageUrl}/about/journalSponsorship">{translate key="about.journalSponsorship"}</a></li>
	{foreach key=key from=$customAboutItems item=customAboutItem}
		{if $customAboutItem.title!=''}<li>&#187;<a href="{$pageUrl}/about/editorialPolicies#custom{$key}">{$customAboutItem.title}</a></li>{/if}
	{/foreach}
	<li>&#187;<a href="{$pageUrl}/about/siteMap">{translate key="about.siteMap"}</a></li>
	<li>&#187;<a href="{$pageUrl}/about/aboutThisPublishingSystem">{translate key="about.aboutThisPublishingSystem"}</a></li>
</ul>

<br />


{include file="common/footer.tpl"}
