{**
 * templates/gateway/lockss.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Adapted from lockss.tpl by Martin Paul Eve
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * LOCKSS Publisher Manifest gateway page.
 * NOTE: This page is not localized in order to provide a consistent interface to LOCKSS across all OJS installations. It is not meant to be accessed by humans.
 *
 *}
{strip}
{assign var="pageTitleTranslated" value="LOCKSS Publisher Manifest"}
{include file="frontend/components/header.tpl"}
{/strip}
<div class="page lockss">
{if $journals}
<h3>Archive of Published Issues</h3>

<ul>
{iterate from=journals item=journal}
	{if $journal->getData('enableLockss')}<li><a href="{url journal=$journal->getPath() page="gateway" op="lockss"}">{$journal->getLocalizedName()|escape}</a></li>{/if}
{/iterate}
</ul>
{else}

<p>{if $prevYear !== null}<a href="{url op="lockss" year=$prevYear}" class="action">&lt;&lt; Previous</a>{else}<span class="disabled heading">&lt;&lt; Previous</span>{/if} | {if $nextYear !== null}<a href="{url op="lockss" year=$nextYear}" class="action">Next &gt;&gt;</a>{else}<span class="disabled heading">Next &gt;&gt;</span>{/if}</p>

<h3>Archive of Published Issues: {$year|escape}</h3>

<ul>
{iterate from=issues item=issue}
	<li><a href="{url page="issue" op="view" path=$issue->getBestIssueId()}">{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a></li>
{/iterate}
</ul>


<br />

<div class="separator"></div>

<h3>Front Matter</h3>

<p>Front Matter associated with this Archival Unit includes:</p>

<ul>
	<li><a href="{url page="about"}">About the Journal</a></li>
	<li><a href="{url page="about" op="submissions"}">Submission Guidelines</a></li>
	<li><a href="{url page="about" op="contact"}">Contact Information</a></li>
</ul>

<br />

<div class="separator"></div>

<h3>Metadata</h3>

<p>Metadata associated with this Archival Unit includes:</p>

<table class="data">
<tr>
	<td width="15%" class="label">Journal URL</td>
	<td width="85%" class="value"><a href="{url journal=$journal->getPath()}">{url journal=$journal->getPath()|escape}</a></td>
</tr>
<tr>
	<td class="label">Title</td>
	<td class="value">{$journal->getLocalizedName()|escape}</td>
</tr>
{if $journal->getData('publisherInstitution')}
<tr>
	<td class="label">Publisher</td>
	<td class="value">{$journal->getData('publisherInstitution')|escape}</td>
</tr>
{/if}
{if $journal->getLocalizedData('searchDescription')}
<tr>
	<td class="label">Description</td>
	<td class="value">{$journal->getLocalizedData('searchDescription')|escape}</td>
</tr>
{/if}
{if $journal->getData('onlineIssn')}
<tr>
	<td class="label">ISSN</td>
	<td class="value">{$journal->getData('onlineIssn')|escape}</td>
</tr>
{elseif $journal->getData('printIssn')}
<tr>
	<td class="label">ISSN</td>
	<td class="value">{$journal->getData('printIssn')|escape}</td>
</tr>
{/if}
<tr>
	<td class="label">Language(s)</td>
	<td class="value">{foreach from=$locales key=localeKey item=localeName}{$localeName|escape} ({$localeKey|escape})<br />{/foreach}</td>
</tr>
{if $journal->getData('contactEmail')}
<tr>
	<td class="label">Publisher Email</td>
	<td class="value">{mailto address=$journal->getData('contactEmail')|escape encode="hex"}</td>
</tr>
{/if}
{if $journal->getLocalizedData('copyrightNotice')}
<tr>
	<td class="label">Copyright</td>
	<td class="value">{$journal->getLocalizedData('licenseTerms')|nl2br}</td>
</tr>
{/if}
{if $journal->getLocalizedData('openAccessPolicy')}
<tr>
	<td class="label">Rights</td>
	<td class="value">{$journal->getLocalizedData('openAccessPolicy')|nl2br}</td>
</tr>
{/if}
</table>
{/if}


<a href="http://www.lockss.org/"><img src="{$baseUrl}/templates/images/lockss.gif" style="border: 0;" alt="LOCKSS" /></a>
<p>LOCKSS system has permission to collect, preserve, and serve this Archival Unit.</p>

<p><a href="http://pkp.sfu.ca/"><img src="{$baseUrl}/lib/pkp/templates/images/pkp.gif" style="border: 0;" alt="The Public Knowledge Project" /></a></p>
<p>Open Journal Systems was developed by the Public Knowledge Project.</p>

</div>

{include file="frontend/components/footer.tpl"}
