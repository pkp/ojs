{**
 * templates/about/contact.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal contact.
 *}
{strip}
{assign var="pageTitle" value="about.journalContact"}
{include file="common/header.tpl"}
{/strip}

{url|assign:editUrl page="management" op="settings" path="journal" anchor="contact"}
{include file="common/linkToEditPage.tpl" editUrl=$editUrl}

{if !empty($contextSettings.mailingAddress)}
<h3>{translate key="common.mailingAddress"}</h3>
<p>
	{$contextSettings.mailingAddress|nl2br}
</p>
<div class="separator"></div>
{/if}

{if not ($currentJournal->getLocalizedSetting('contactTitle') == '' && $currentJournal->getLocalizedSetting('contactAffiliation') == '' && $currentJournal->getLocalizedSetting('contactMailingAddress') == '' && empty($contextSettings.contactPhone) && empty($contextSettings.contactFax) && empty($contextSettings.contactEmail))}
<h3>{translate key="about.contact.principalContact"}</h3>
<p>
	{if !empty($contextSettings.contactName)}
		<strong>{$contextSettings.contactName|escape}</strong><br />
	{/if}

	{assign var=s value=$currentJournal->getLocalizedSetting('contactTitle')}
	{if $s}{$s|escape}<br />{/if}

	{assign var=s value=$currentJournal->getLocalizedSetting('contactAffiliation')}
	{if $s}{$s|strip_unsafe_html}{/if}

	{assign var=s value=$currentJournal->getLocalizedSetting('contactMailingAddress')}
	{if $s}{$s|strip_unsafe_html}{/if}
</p>
<p>
	{if !empty($contextSettings.contactPhone)}
		{translate key="about.contact.phone"}: {$contextSettings.contactPhone|escape}<br />
	{/if}
	{if !empty($contextSettings.contactFax)}
		{translate key="about.contact.fax"}: {$contextSettings.contactFax|escape}<br />
	{/if}
	{if !empty($contextSettings.contactEmail)}
		{translate key="about.contact.email"}: {mailto address=$contextSettings.contactEmail|escape encode="hex"}
	{/if}
</p>
<div class="separator"></div>
{/if}

{if not (empty($contextSettings.supportName) && empty($contextSettings.supportPhone) && empty($contextSettings.supportEmail))}
<h3>{translate key="about.contact.supportContact"}</h3>
<p>
	{if !empty($contextSettings.supportName)}
		<strong>{$contextSettings.supportName|escape}</strong><br />
	{/if}
	{if !empty($contextSettings.supportPhone)}
		{translate key="about.contact.phone"}: {$contextSettings.supportPhone|escape}<br />
	{/if}
	{if !empty($contextSettings.supportEmail)}
		{translate key="about.contact.email"}: {mailto address=$contextSettings.supportEmail|escape encode="hex"}<br />
	{/if}
</p>
{/if}

{include file="common/footer.tpl"}
