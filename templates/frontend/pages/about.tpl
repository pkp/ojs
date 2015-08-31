{**
 * templates/frontend/pages/about.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to view a journal's description, contact details,
 *  politics and more.
 *
 * @uses $contextSettings object Settings related to this page. Used for
 *       accessing contact details
 * @uses $currentJournal Journal The current journal
 * @uses $mailingAddress string Mailing address for the journal
 * @uses $showContact bool Should the primary contact section be shown?
 * @uses $contactName string Primary contact name
 * @uses $contactTitle string Primary contact title
 * @uses $contactAffiliation string Primary contact affiliation
 * @uses $contactMailingAddress Primary contact Mailing Address
 * @uses $contactPhone string Primary contact phone number
 * @uses $contactFax string Primary contact fax number
 * @uses $contactEmail string Primary contact email address
 * @uses $showSupportContact bool Should the support contact section be shown?
 * @uses $supportName string Support contact name
 * @uses $supportPhone string Support contact phone number
 * @uses $supportEmail string Support contact email address
 * @uses $contributorNote string Description for contributors section
 * @uses $contributors array List of contributors to this journal
 * @uses $sponsorNote string Description for sponsors section
 * @uses $sponsors array List of sponsors of this journal
 *}
{include file="common/frontend/header.tpl" pageTitle="about.aboutTheJournal"}

<div class="page page_about">
	<h1 class="page_title">
		{translate key="about.aboutTheJournal"}
	</h1>

	{if $currentJournal->getLocalizedSetting('description')}
	<div class="description">
		{$currentJournal->getLocalizedSetting('description')|nl2br}
	</div>
	{/if}

	{* Contact section *}
	{if $showContact || $showSupportContact}
		<div class="contact_section">
			<h2>
				{translate key="about.journalContact"}
			</h2>

			{if $mailingAddress}
				<div class="address">
					{$mailingAddress}
				</div>
			{/if}

			{* Primary contact *}
			{if $showContact}
				<div class="contact primary">
					<h3>
						{translate key="about.contact.principalContact"}
					</h3>

					{if $contactName}
					<div class="name">
						{$contactName|escape}
					</div>
					{/if}

					{if $contactTitle}
					<div class="title">
						{$contactTitle|escape}
					</div>
					{/if}

					{if $contactAffiliation}
					<div class="affiliation">
						{$contactAffiliation|strip_unsafe_html}
					</div>
					{/if}

					{if $contactMailingAddress}
					<div class="address">
						{$contactMailingAddress|strip_unsafe_html}
					</div>
					{/if}

					{if $contactPhone}
					<div class="phone">
						<span class="label">
							{translate key="about.contact.phone"}
						</span>
						<span class="value">
							{$contactPhone|escape}
						</span>
					</div>
					{/if}

					{if $contactFax}
					<div class="fax">
						<span class="label">
							{translate key="about.contact.fax"}
						</span>
						<span class="value">
							{$contactFax|escape}
						</span>
					</div>
					{/if}

					{if $contactEmail}
					<div class="email">
						<a href="mailto:{$contactEmail|escape}">
							{$contactEmail|escape}
						</a>
					</div>
					{/if}
				</div>
			{/if}

			{* Technical contact *}
			{if $showSupportContact}
				<div class="contact support">
					<h3>
						{translate key="about.contact.supportContact"}
					</h3>

					{if $supportName}
					<div class="name">
						{$supportName|escape}
					</div>
					{/if}

					{if $supportPhone}
					<div class="phone">
						<span class="label">
							{translate key="about.contact.phone"}
						</span>
						<span class="value">
							{$supportPhone|escape}
						</span>
					</div>
					{/if}

					{if $supportEmail}
					<div class="email">
						<a href="mailto:{$supportEmail|escape}">
							{$supportEmail|escape}
						</a>
					</div>
					{/if}
				</div>
			{/if}
		</div>
	{/if}{* /Contact Section *}

	{* Policies *}
	{if $currentJournal->getLocalizedSetting('focusScopeDesc')}
		<div class="focus_scope">
			<h2>
				{translate key="about.focusAndScope"}
			</h2>
			<div>
				{$currentJournal->getLocalizedSetting('focusScopeDesc')}
			</div>
		</div>
	{/if}

	{if $currentJournal->getLocalizedSetting('reviewPolicy')}
		<div class="review">
			<h2>
				{translate key="about.peerReviewProcess"}
			</h2>
			{$currentJournal->getLocalizedSetting('reviewPolicy')|nl2br}
		</div>
	{/if}

	{if $currentJournal->getLocalizedSetting('openAccessPolicy')}
		<div class="open_access">
			<h2>
				{translate key="about.openAccessPolicy"}
			</h2>
			{$currentJournal->getLocalizedSetting('openAccessPolicy')|nl2br}
		</div>
	{/if}

	{foreach key=key from=$currentJournal->getLocalizedSetting('customAboutItems') item=customAboutItem}
		{if !empty($customAboutItem.title)}
			<div class="custom custom-{$key|escape}">
				<h2>
					{$customAboutItem.title|escape}
				</h2>
				{$customAboutItem.content|nl2br}
			</div>
		{/if}
	{/foreach}

	{* Sponsors *}
	{if $sponsorNote || $sponsors}
		<div class="sponsors">
			<h2>
				{translate key="about.sponsors"}
			</h2>
			{$sponsorNote|nl2br}
			{if $sponsors}
				<ul>
					{foreach from=$sponsors item=sponsor}
						<li>
							<a href="{$sponsor.url|escape}">
								{$sponsor.institution|escape}
							</a>
						</li>
					{/foreach}
				</ul>
			{/if}
		</div>
	{/if}

	{* Contributors *}
	{if $contributorNote || $contributors}
		<div class="contributors">
			<h2>
				{translate key="about.contributors"}
			</h2>
			{$contributorNote|nl2br}
			{if $contributors}
				<ul>
					{foreach from=$contributors item=contributor}
						<li>
							<a href="{$contributor.url|escape}">
								{$contributor.institution|escape}
							</a>
						</li>
					{/foreach}
				</ul>
			{/if}
		</div>
	{/if}

</div><!-- .page -->

{include file="common/frontend/footer.tpl"}
