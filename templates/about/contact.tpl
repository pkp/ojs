{**
 * contact.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal / Journal Contact.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.journalContact"}
{include file="common/header.tpl"}

{if !empty($journalSettings.mailingAddress)}
<h3>{translate key="common.mailingAddress"}</h3>
<p>
	{$journalSettings.mailingAddress|nl2br}
</p>
{/if}

{if not (empty($journalSettings.contactTitle) && empty($journalSettings.contactAffiliation) && empty($journalSettings.contactAffiliation) && empty($journalSettings.contactMailingAddress) && empty($journalSettings.contactPhone) && empty($journalSettings.contactFax) && empty($journalSettings.contactEmail))}
<h3>{translate key="about.contact.principalContact"}</h3>
<p>
	{if !empty($journalSettings.contactName)}
		<strong>{$journalSettings.contactName|escape}</strong><br />
	{/if}
	{if !empty($journalSettings.contactTitle)}
		{$journalSettings.contactTitle|escape}<br />
	{/if}
	{if !empty($journalSettings.contactAffiliation)}
		{$journalSettings.contactAffiliation|escape}<br />
	{/if}
	{if !empty($journalSettings.contactMailingAddress)}
		{$journalSettings.contactMailingAddress|nl2br}<br />
	{/if}
	{if !empty($journalSettings.contactPhone)}
		{translate key="about.contact.phone"}: {$journalSettings.contactPhone|escape}<br />
	{/if}
	{if !empty($journalSettings.contactFax)}
		{translate key="about.contact.fax"}: {$journalSettings.contactFax|escape}<br />
	{/if}
	{if !empty($journalSettings.contactEmail)}
		{translate key="about.contact.email"}: {mailto address=$journalSettings.contactEmail|escape encode="hex"}<br />
	{/if}
</p>
{/if}

{if not (empty($journalSettings.supportName) && empty($journalSettings.supportPhone) && empty($journalSettings.supportEmail))}
<h3>{translate key="about.contact.supportContact"}</h3>
<p>
	{if !empty($journalSettings.supportName)}
		<strong>{$journalSettings.supportName|escape}</strong><br />
	{/if}
	{if !empty($journalSettings.supportPhone)}
		{translate key="about.contact.phone"}: {$journalSettings.supportPhone|escape}<br />
	{/if}
	{if !empty($journalSettings.supportEmail)}
		{translate key="about.contact.email"}: {mailto address=$journalSettings.supportEmail|escape encode="hex"}<br />
	{/if}
</p>
{/if}

{include file="common/footer.tpl"}
