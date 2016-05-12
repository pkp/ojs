{**
 * templates/frontend/pages/contact.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to view the journal's contact details
 *
 * @uses $contextSettings object Settings related to this page. Used for
 *       accessing contact details
 * @uses $currentJournal Journal The current journal
 * @uses $mailingAddress string Mailing address for the journal
 * @uses $showContact bool Should the primary contact section be shown?
 * @uses $contactName string Primary contact name
 * @uses $contactTitle string Primary contact title
 * @uses $contactAffiliation string Primary contact affiliation
 * @uses $contactPhone string Primary contact phone number
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
{include file="frontend/components/header.tpl" pageTitle="about.contact"}

<div class="page page_contact">
	{include file="frontend/components/breadcrumbs.tpl" currentTitleKey="about.contact"}

	{* Contact section *}
	{if $showContact || $showSupportContact || $mailingAddress}
		<div class="contact_section">

			{if $mailingAddress}
				<div class="address">
					{$mailingAddress|strip_unsafe_html|nl2br}
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

</div><!-- .page -->

{include file="common/frontend/footer.tpl"}
