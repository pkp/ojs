{**
 * templates/about/index.tpl
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal index.
 *
 *}
{strip}
{assign var="pageTitle" value="about.aboutTheJournal"}
{include file="common/header.tpl"}
{/strip}
<div id="people">
<h3>{translate key="about.people"}</h3>
<ul>
	{if not (empty($journalSettings.mailingAddress) && empty($journalSettings.contactName) && empty($journalSettings.contactAffiliation) && empty($journalSettings.contactMailingAddress) && empty($journalSettings.contactPhone) && empty($journalSettings.contactFax) && empty($journalSettings.contactEmail) && empty($journalSettings.supportName) && empty($journalSettings.supportPhone) && empty($journalSettings.supportEmail))}
		<li id="contact"><a href="{url op="contact"}">{translate key="about.contact"}</a></li>
	{/if}
	<li id="editorialTeam"><a href="{url op="editorialTeam"}">{translate key="about.editorialTeam"}</a></li>
	{if $peopleGroups}
		{iterate from=peopleGroups item=peopleGroup}
			<li id="{$peopleGroup->getId()}"><a href="{url op="displayMembership" path=$peopleGroup->getId()}">{$peopleGroup->getLocalizedTitle()|escape}</a></li>
		{/iterate}
	{/if}
	{call_hook name="Templates::About::Index::People"}
</ul>
</div>
<div id="aboutPolicies">
<h3>{translate key="about.policies"}</h3>
<ul>
	{if $currentJournal->getLocalizedSetting('focusScopeDesc') != ''}<li id="focusAndScope"><a href="{url op="editorialPolicies" anchor="focusAndScope"}">{translate key="about.focusAndScope"}</a></li>{/if}
	<li id="sectionPolicies"><a href="{url op="editorialPolicies" anchor="sectionPolicies"}">{translate key="about.sectionPolicies"}</a></li>
	{if $currentJournal->getLocalizedSetting('reviewPolicy') != ''}<li id="peerReviewProcess"><a href="{url op="editorialPolicies" anchor="peerReviewProcess"}">{translate key="about.peerReviewProcess"}</a></li>{/if}
	{if $currentJournal->getLocalizedSetting('pubFreqPolicy') != ''}<li id="publicationFrequency"><a href="{url op="editorialPolicies" anchor="publicationFrequency"}">{translate key="about.publicationFrequency"}</a></li>{/if}
	{if $currentJournal->getSetting('publishingMode') == $smarty.const.PUBLISHING_MODE_OPEN && $currentJournal->getLocalizedSetting('openAccessPolicy') != ''}<li id="openAccessPolicy"><a href="{url op="editorialPolicies" anchor="openAccessPolicy"}">{translate key="about.openAccessPolicy"}</a></li>{/if}
	{if $journalSettings.enableLockss && $currentJournal->getLocalizedSetting('lockssLicense') != ''}<li id="archiving"><a href="{url op="editorialPolicies" anchor="archiving"}">{translate key="about.archiving"}</a></li>{/if}
	{if $paymentConfigured && $journalSettings.journalPaymentsEnabled && $journalSettings.membershipFeeEnabled && $journalSettings.membershipFee > 0}<li id="memberships"><a href="{url op="memberships"}">{translate key="about.memberships"}</a></li>{/if}
	{if $currentJournal->getSetting('publishingMode') == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION}
		<li id="subscriptions"><a href="{url op="subscriptions"}">{translate key="about.subscriptions"}</a></li>
		{if !empty($journalSettings.enableAuthorSelfArchive)}<li id="authorSelfArchive"><a href="{url op="editorialPolicies" anchor="authorSelfArchivePolicy"}">{translate key="about.authorSelfArchive"}</a></li>{/if}
		{if !empty($journalSettings.enableDelayedOpenAccess)}<li id="delayedOpenAccess"><a href="{url op="editorialPolicies" anchor="delayedOpenAccessPolicy"}">{translate key="about.delayedOpenAccess"}</a></li>{/if}
		{if $paymentConfigured && $journalSettings.journalPaymentsEnabled && $journalSettings.acceptSubscriptionPayments && $journalSettings.purchaseIssueFeeEnabled && $journalSettings.purchaseIssueFee > 0}<li id="purchaseIssue"><a href="{url op="editorialPolicies" anchor="purchaseIssue"}">{translate key="about.purchaseIssue"}</a></li>{/if}
		{if $paymentConfigured && $journalSettings.journalPaymentsEnabled && $journalSettings.acceptSubscriptionPayments && $journalSettings.purchaseArticleFeeEnabled && $journalSettings.purchaseArticleFee > 0}<li id="purchaseArticle"><a href="{url op="editorialPolicies" anchor="purchaseArticle"}">{translate key="about.purchaseArticle"}</a></li>{/if}
	{/if}{* $currentJournal->getSetting('publishingMode') == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION *}
	{foreach key=key from=$customAboutItems item=customAboutItem}
		{if $customAboutItem.title!=''}<li><a href="{url op="editorialPolicies" anchor=custom-$key}">{$customAboutItem.title|escape}</a></li>{/if}
	{/foreach}
	{call_hook name="Templates::About::Index::Policies"}
</ul>
</div>
<div id="aboutSubmissions">
<h3>{translate key="about.submissions"}</h3>
<ul>
	<li id="onlineSubmissions"><a href="{url op="submissions" anchor="onlineSubmissions"}">{translate key="about.onlineSubmissions"}</a></li>
	{if $currentJournal->getLocalizedSetting('authorGuidelines') != ''}<li id="authorGuidelines"><a href="{url op="submissions" anchor="authorGuidelines"}">{translate key="about.authorGuidelines"}</a></li>{/if}
	{if $currentJournal->getLocalizedSetting('copyrightNotice') != ''}<li id="copyrightNotice"><a href="{url op="submissions" anchor="copyrightNotice"}">{translate key="about.copyrightNotice"}</a></li>{/if}
	{if $currentJournal->getLocalizedSetting('privacyStatement') != ''}<li id="privacyStatement"><a href="{url op="submissions" anchor="privacyStatement"}">{translate key="about.privacyStatement"}</a></li>{/if}
	{if $currentJournal->getSetting('journalPaymentsEnabled') && ($currentJournal->getSetting('submissionFeeEnabled') || $currentJournal->getSetting('fastTrackFeeEnabled') || $currentJournal->getSetting('publicationFeeEnabled'))}<li id="authorFees"><a href="{url op="submissions" anchor="authorFees"}">{translate key="about.authorFees"}</a></li>{/if}
	{call_hook name="Templates::About::Index::Submissions"}
</ul>
</div>
<div id="aboutOther">
<h3>{translate key="about.other"}</h3>
<ul>
	{if not ($currentJournal->getSetting('publisherInstitution') == '' && $currentJournal->getLocalizedSetting('publisherNote') == '' && $currentJournal->getLocalizedSetting('contributorNote') == '' && empty($journalSettings.contributors) && $currentJournal->getLocalizedSetting('sponsorNote') == '' && empty($journalSettings.sponsors))}<li id="journalSponsorship"><a href="{url op="journalSponsorship"}">{translate key="about.journalSponsorship"}</a></li>{/if}
	{if $currentJournal->getLocalizedSetting('history') != ''}<li id="history"><a href="{url op="history"}">{translate key="about.history"}</a></li>{/if}
	<li id="siteMap"><a href="{url op="siteMap"}">{translate key="about.siteMap"}</a></li>
	<li id="thisPublishingSystem"><a href="{url op="aboutThisPublishingSystem"}">{translate key="about.aboutThisPublishingSystem"}</a></li>
	{if $publicStatisticsEnabled}<li id="statistics"><a href="{url op="statistics"}">{translate key="about.statistics"}</a></li>{/if}
	{call_hook name="Templates::About::Index::Other"}
</ul>
</div>

{include file="common/footer.tpl"}

