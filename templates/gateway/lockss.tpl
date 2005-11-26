{**
 * lockss.tpl
 *
 * Copyright (c) 2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * LOCKSS Publisher Manifest gateway page.
 * NOTE: This page is not localized in order to provide a consistent interface to LOCKSS across all OJS installations. It is not meant to be accessed by humans.
 *
 * $Id$
 *}

{assign var="pageTitleTranslated" value="LOCKSS Publisher Manifest"}
{include file="common/header.tpl"}

{if $journals}
<h3>Archive of Published Issues</h3>

<ul>
{iterate from=journals item=journal}
	{if $journal->getSetting('enableLockss')}<li><a href="{url journal=$journal->getPath() page="gateway" op="lockss"}">{$journal->getTitle()|escape}</a></li>{/if}
{/iterate}
</ul>
{else}

<p>{if $prevYear !== null}<a href="{url op="lockss" year=$prevYear}" class="action">&lt;&lt; Previous</a>{else}<span class="disabled heading">&lt;&lt; Previous</span>{/if} | {if $nextYear !== null}<a href="{url op="lockss" year=$nextYear}" class="action">Next &gt;&gt;</a>{else}<span class="disabled heading">Next &gt;&gt;</span>{/if}</p>

<h3>Archive of Published Issues: {$year}</h3>

<ul>
{iterate from=issues item=issue}
	<li><a href="{url page="issue" op="view" path=$issue->getBestIssueId($journal)}">{$issue->getIssueIdentification()|escape}</a></li>
{/iterate}
</ul>

{if $showInfo}
<br />

<div class="separator"></div>

<h3>Front Matter</h3>

<p>Front Matter associated with this Archival Unit includes:</p>

<ul>
	<li><a href="{url page="about"}">About the Journal</a></li>
	<li><a href="{url page="about" op="submission"}">Submission Guidelines</a></li>
	<li><a href="{url page="about" op="contact"}">Contact Information</a></li>
</ul>

<br />

<div class="separator"></div>

<h3>Metadata</h3>

<p>Metadata associated with this Archival Unit includes:</p>

<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">Journal URL</td>
	<td width="80%" class="value"><a href="{$journal->getUrl()}">{$journal->getUrl()}</a></td>
</tr>
<tr valign="top">
	<td class="label">Title</td>
	<td class="value">{$journal->getTitle()|escape}</td>
</tr>
<tr valign="top">
	<td class="label">Publisher</td>
	<td class="value">{assign var="publisher" value=$journal->getSetting('publisher')}<a href="{$publisher.url|escape}">{$publisher.institution|escape}</a></td>
</tr>
<tr valign="top">
	<td class="label">Description</td>
	<td class="value">{$journal->getSetting('searchDescription')|escape}</td>
</tr>
<tr valign="top">
	<td class="label">Keywords</td>
	<td class="value">{$journal->getSetting('searchKeywords')|escape}</td>
</tr>
{if $journal->getSetting('issn')}
<tr valign="top">
	<td class="label">ISSN</td>
	<td class="value">{$journal->getSetting('issn')|escape}</td>
</tr>
{/if}
<tr valign="top">
	<td class="label">Language(s)</td>
	<td class="value">{foreach from=$locales key=localeKey item=localeName}{$localeName} ({$localeKey})<br />{/foreach}</td>
</tr>
<tr valign="top">
	<td class="label">Publisher Email</td>
	<td class="value">{mailto address=$journal->getSetting('contactEmail')|escape encode="hex"}</td>
</tr>
{if $journal->getSetting('copyrightNotice')}
<tr valign="top">
	<td class="label">Copyright</td>
	<td class="value">{$journal->getSetting('copyrightNotice')|nl2br}</td>
</tr>
{/if}
{if $journal->getSetting('openAccessPolicy')}
<tr valign="top">
	<td class="label">Rights</td>
	<td class="value">{$journal->getSetting('openAccessPolicy')|nl2br}</td>
</tr>
{/if}
</table>
{/if}

{/if}

<br /><br />

<div style="text-align: center; width: 250px; margin: 0 auto">
	<a href="http://lockss.stanford.edu/"><img src="{$baseUrl}/templates/images/lockss.gif" width="108" height="108" border="0" alt="LOCKSS" /></a>
	<br />
	LOCKSS system has permission to collect, preserve, and serve this Archival Unit.
		
	<br /><br />
	
	<a href="http://pkp.sfu.ca/"><img src="{$baseUrl}/templates/images/pkp.gif" width="79" height="56" border="0" alt="The Public Knowledge Project" /></a>
	<br />
	Open Journal Systems was developed by the Public Knowledge Project.
</div>

{include file="common/footer.tpl"}
