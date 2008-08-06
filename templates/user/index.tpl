{**
 * index.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User index.
 *
 * $Id$
 *}
{assign var="pageTitle" value="user.userHome"}
{include file="common/header.tpl"}

{if $showAllJournals}

<h3>{translate key="user.myJournals"}</h3>

{if $isSiteAdmin}
	{assign var="hasRole" value=1}
	<h4><a href="{url page="user"}">{$siteTitle|escape}</a></h4>
	<ul class="plain">
		<li>&#187; <a href="{url journal="index" page=$isSiteAdmin->getRolePath()}">{translate key=$isSiteAdmin->getRoleName()}</a></li>
		{call_hook name="Templates::User::Index::Site"}
	</ul>
{/if}

{foreach from=$userJournals item=journal}
	{assign var="hasRole" value=1}
	<h4><a href="{url journal=$journal->getPath() page="user"}">{$journal->getJournalTitle()|escape}</a></h4>
	<ul class="plain">
		{assign var="journalId" value=$journal->getJournalId()}
		{section name=role loop=$userRoles[$journalId]}
			{if $userRoles[$journalId][role]->getRolePath() != 'reader'}
				<li>&#187; <a href="{url journal=$journal->getPath() page=$userRoles[$journalId][role]->getRolePath()}">{translate key=$userRoles[$journalId][role]->getRoleName()}</a></li>
			{/if}
		{/section}
		{call_hook name="Templates::User::Index::Journal" journal=$journal}
	</ul>
{/foreach}

{else}{* $showAllJournals *}

<h3>{$userJournal->getJournalTitle()}</h3>
<ul class="plain">
	{if $isSiteAdmin && !$hasOtherJournals}
		{assign var="hasRole" value=1}
		<li>&#187; <a href="{url journal="index" page=$isSiteAdmin->getRolePath()}">{translate key=$isSiteAdmin->getRoleName()}</a></li>
	{/if}

	{assign var="journalId" value=$userJournal->getJournalId()}
	{section name=role loop=$userRoles[$journalId]}
		{assign var="hasRole" value=1}
		{if $userRoles[$journalId][role]->getRolePath() != 'reader'}
			<li>&#187; <a href="{url journal=$userJournal->getPath() page=$userRoles[$journalId][role]->getRolePath()}">{translate key=$userRoles[$journalId][role]->getRoleName()}</a></li>
		{/if}
	{/section}
</ul>
{/if}{* $showAllJournals *}

{if !$hasRole}
	{if $currentJournal}
		<p>{translate key="user.noRoles.noRolesForJournal"}</p>
		<ul class="plain">
			<li>
				&#187;
				{if $allowRegAuthor}
					{url|assign:"submitUrl" page="author" op="submit"}
					<a href="{url op="become" path="author" source=$submitUrl}">{translate key="user.noRoles.submitArticle"}</a>
				{else}{* $allowRegAuthor *}
					{translate key="user.noRoles.submitArticleRegClosed"}
				{/if}{* $allowRegAuthor *}
			</li>
			<li>
				&#187;
				{if $allowRegReviewer}
					{url|assign:"userHomeUrl" page="user" op="index"}
					<a href="{url op="become" path="reviewer" source=$userHomeUrl}">{translate key="user.noRoles.regReviewer"}</a>
				{else}{* $allowRegReviewer *}
					{translate key="user.noRoles.regReviewerClosed"}
				{/if}{* $allowRegReviewer *}
			</li>
		</ul>
	{else}{* $currentJournal *}
		<p>{translate key="user.noRoles.chooseJournal"}</p>
		<ul class="plain">
			{foreach from=$allJournals item=thisJournal}
				<li>&#187; <a href="{url journal=$thisJournal->getPath() page="user" op="index"}">{$thisJournal->getJournalTitle()|escape}</a></li>
			{/foreach}
		</ul>
	{/if}{* $currentJournal *}
{/if}{* !$hasRole *}

<h3>{translate key="user.myAccount"}</h3>
<ul class="plain">
	{if $hasOtherJournals}
		{if $showAllJournals}
			<li>&#187; <a href="{url journal="index" page="user" op="register"}">{translate key="user.registerForOtherJournals"}</a></li>
		{else}
			<li>&#187; <a href="{url journal="index" page="user"}">{translate key="user.showAllJournals"}</a></li>
		{/if}
	{/if}
	<li>&#187; <a href="{url page="user" op="profile"}">{translate key="user.editMyProfile"}</a></li>

	{if !$implicitAuth}
		<li>&#187; <a href="{url page="user" op="changePassword"}">{translate key="user.changeMyPassword"}</a></li>
	{/if}

	{if $userJournal}
		{if $journalPaymentsEnabled && $subscriptionEnabled && $userHasSubscription}
			<li>&#187; <a href="{url page="user" op="payRenewSubscription"}">{translate key="payment.subscription.renew"}</a> ({translate key="payment.subscription.expires"}: {$subscriptionEndDate|date_format:$dateFormatShort})</li>
		{/if}
		{if $journalPaymentsEnabled && $membershipEnabled}
			{if $dateEndMembership}
				<li>&#187; <a href="{url page="user" op="payMembership"}">{translate key="payment.membership.renewMembership"}</a> ({translate key="payment.membership.ends"}: {$dateEndMembership|date_format:$dateFormatShort})</li>
			{else}
				<li>&#187; <a href="{url page="user" op="payMembership"}">{translate key="payment.membership.buyMembership"}</a></li>		
			{/if}
		{/if}{* $journalPaymentsEnabled && $membershipEnabled *}
	{/if}{* $userJournal *}

	<li>&#187; <a href="{url page="login" op="signOut"}">{translate key="user.logOut"}</a></li>
	{call_hook name="Templates::User::Index::MyAccount"}
</ul>

{include file="common/footer.tpl"}
