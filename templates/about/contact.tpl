{**
 * contact.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal / Journal Contact.
 *
 * $Id$
 *}

{assign var="pageTitle" value="about.journalContact"}
{include file="common/header.tpl"}

<div class="subTitle">{translate key="common.mailingAddress"}</div>
<p>
	{if !empty($journalSettings.mailingAddress)}
		{$journalSettings.mailingAddress|nl2br}
	{/if}
</p>

<div class="subTitle">{translate key="about.contact.principalContact"}</div>
<p>
	{if !empty($journalSettings.contactName)}
		<strong>{$journalSettings.contactName}</strong><br />
	{/if}
	{if !empty($journalSettings.contactEmail)}
		<a href="mailto:{$journalSettings.contactEmail}">{$journalSettings.contactEmail}</a><br />
	{/if}
	{if !empty($journalSettings.contactTitle)}
		{$journalSettings.contactTitle}<br />
	{/if}
	{if !empty($journalSettings.contactAffiliation)}
		{$journalSettings.contactAffiliation}<br />
	{/if}
	{if !empty($journalSettings.contactMailingAddress)}
		{$journalSettings.contactMailingAddress|nl2br}<br />
	{/if}
	{if !empty($journalSettings.contactPhone)}
		{translate key="about.contact.phone"}: {$journalSettings.contactPhone}<br />
	{/if}
	{if !empty($journalSettings.contactFax)}
		{translate key="about.contact.fax"}: {$journalSettings.contactFax}<br />
	{/if}
</p>

<div class="subTitle">{translate key="about.contact.supportContact"}</div>
<p>
	{if !empty($journalSettings.supportName)}
		<strong>{$journalSettings.supportName}</strong><br />
	{/if}
	{if !empty($journalSettings.supportEmail)}
		<a href="mailto:{$journalSettings.supportEmail}">{$journalSettings.supportEmail}</a><br />
	{/if}
	{if !empty($journalSettings.supportPhone)}
		Phone: {$journalSettings.supportPhone}
	{/if}
</p>

{include file="common/footer.tpl"}
