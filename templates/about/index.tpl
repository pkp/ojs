{**
 * index.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
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
		<li>&#187; <a href="{url op="contact"}">{translate key="about.contact"}</a></li>
	{/if}
	<li>&#187; <a href="{url op="editorialTeam"}">{translate key="about.editorialTeam"}</a></li>
	{call_hook name="Templates::About::Index::People"}
</ul>

<h3>{translate key="about.policies"}</h3>
<ul class="plain">
	{if !empty($journalSettings.focusScopeDesc)}<li>&#187; <a href="{url op="editorialPolicies" anchor="focusAndScope"}">{translate key="about.focusAndScope"}</a></li>{/if}
	<li>&#187; <a href="{url op="editorialPolicies" anchor="sectionPolicies"}">{translate key="about.sectionPolicies"}</a></li>
	{if !empty($journalSettings.reviewPolicy)}<li>&#187; <a href="{url op="editorialPolicies" anchor="peerReviewProcess"}">{translate key="about.peerReviewProcess"}</a></li>{/if}
	{if !empty($journalSettings.pubFreqPolicy)}<li>&#187; <a href="{url op="editorialPolicies" anchor="publicationFrequency"}">{translate key="about.publicationFrequency"}</a></li>{/if}
	{if empty($journalSettings.enableSubscriptions) && !empty($journalSettings.openAccessPolicy)}<li>&#187; <a href="{url op="editorialPolicies" anchor="openAccessPolicy"}">{translate key="about.openAccessPolicy"}</a></li>{/if}
	{if $journalSettings.enableLockss && !empty($journalSettings.lockssLicense)}<li>&#187; <a href="{url op="editorialPolicies" anchor="archiving"}">{translate key="about.archiving"}</a></li>{/if}
	{if !empty($journalSettings.enableSubscriptions)}<li>&#187; <a href="{url op="subscriptions"}">{translate key="about.subscriptions"}</a></li>{/if}
	{if !empty($journalSettings.enableSubscriptions) && !empty($journalSettings.enableAuthorSelfArchive)}<li>&#187; <a href="{url op="editorialPolicies" anchor="authorSelfArchivePolicy"}">{translate key="about.authorSelfArchive"}</a></li>{/if}
	{if !empty($journalSettings.enableSubscriptions) && !empty($journalSettings.enableDelayedOpenAccess)}<li>&#187; <a href="{url op="editorialPolicies" anchor="delayedOpenAccessPolicy"}">{translate key="about.delayedOpenAccess"}</a></li>{/if}
	{foreach key=key from=$customAboutItems item=customAboutItem}
		{if $customAboutItem.title!=''}<li>&#187; <a href="{url op="editorialPolicies" anchor=custom`$key`}">{$customAboutItem.title|escape}</a></li>{/if}
	{/foreach}
	{call_hook name="Templates::About::Index::Policies"}
</ul>

<h3>{translate key="about.submissions"}</h3>
<ul class="plain">
	<li>&#187; <a href="{url op="submissions" anchor="onlineSubmissions"}">{translate key="about.onlineSubmissions"}</a></li>
	{if !empty($journalSettings.authorGuidelines)}<li>&#187; <a href="{url op="submissions" anchor="authorGuidelines"}">{translate key="about.authorGuidelines"}</a></li>{/if}
	{if !empty($journalSettings.copyrightNotice)}<li>&#187; <a href="{url op="submissions" anchor="copyrightNotice"}">{translate key="about.copyrightNotice"}</a></li>{/if}
	{if !empty($journalSettings.privacyStatement)}<li>&#187; <a href="{url op="submissions" anchor="privacyStatement"}">{translate key="about.privacyStatement"}</a></li>{/if}
	{call_hook name="Templates::About::Index::Submissions"}
</ul>

<h3>{translate key="about.other"}</h3>
<ul class="plain">
	{if not (empty($journalSettings.publisher) && empty($journalSettings.contributorNote) && empty($journalSettings.contributors) && empty($journalSettings.sponsorNote) && empty($journalSettings.sponsors))}<li>&#187; <a href="{url op="journalSponsorship"}">{translate key="about.journalSponsorship"}</a></li>{/if}
	<li>&#187; <a href="{url op="siteMap"}">{translate key="about.siteMap"}</a></li>
	<li>&#187; <a href="{url op="aboutThisPublishingSystem"}">{translate key="about.aboutThisPublishingSystem"}</a></li>
	{if $publicStatisticsEnabled}<li>&#187; <a href="{url op="statistics"}">{translate key="about.statistics"}</a></li>{/if}
	{call_hook name="Templates::About::Index::Other"}
</ul>

{include file="common/footer.tpl"}
