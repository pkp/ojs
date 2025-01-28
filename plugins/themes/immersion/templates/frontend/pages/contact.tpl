{**
 * templates/frontend/pages/contact.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to view the press's contact details.
 *
 * @uses $currentContext Journal|Press The current journal or press
 * @uses $mailingAddress string Mailing address for the journal/press
 * @uses $contactName string Primary contact name
 * @uses $contactTitle string Primary contact title
 * @uses $contactAffiliation string Primary contact affiliation
 * @uses $contactPhone string Primary contact phone number
 * @uses $contactEmail string Primary contact email address
 * @uses $supportName string Support contact name
 * @uses $supportPhone string Support contact phone number
 * @uses $supportEmail string Support contact email address
 *}
{include file="frontend/components/header.tpl" pageTitle="about.contact"}

<main class="container main__content" id="immersion_content_main">
	<div class="row">
		<div class="offset-md-1 col-md-10 offset-lg-2 col-lg-8">
			<header class="main__header">
				<h1 class="main__title">
					<span>{translate key="about.contact"}</span>
				</h1>
				{include file="frontend/components/editLink.tpl" page="management" op="settings" path="context" anchor="contact" sectionTitleKey="about.contact"}
			</header>

			{* Contact section *}
			<div class="contact_section">

				{if $mailingAddress}
					<div class="address">
						{$mailingAddress|nl2br|strip_unsafe_html}
					</div>
				{/if}

				{* Primary contact *}
				{if $contactTitle || $contactName || $contactAffiliation || $contactPhone || $contactEmail}
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
								{mailto address=$contactEmail encode='javascript'}
							</div>
						{/if}
					</div>
				{/if}

				{* Technical contact *}
				{if $supportName || $supportPhone || $supportEmail}
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
								{mailto address=$supportEmail encode='javascript'}
							</div>
						{/if}
					</div>
				{/if}
			</div>
		</div>
	</div><!-- .row -->
</main><!-- .main__content -->

{include file="frontend/components/footer.tpl"}
