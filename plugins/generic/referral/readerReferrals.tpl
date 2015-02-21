{**
 * plugins/generic/referral/readerReferrals.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Referral listing for readers
 *
 *}

<div class="separator"></div>

<h3>{translate key="plugins.generic.referral.referrals"}</h3>

<ul>
	{iterate from=referrals item=referral}
		<li><a href="{$referral->getUrl()|escape}" target="_parent">{$referral->getReferralName()|escape|default:"&mdash;"}</a></li>
	{/iterate}
	{if $referrals->wasEmpty()}
		<li>{translate key="plugins.generic.referral.all.empty"}</li>
	{/if}
</ul>
