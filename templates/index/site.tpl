{**
 * site.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site index.
 *
 * $Id$
 *}
{if $siteTitle}{assign var="pageTitleTranslated" value=$siteTitle}{/if}
{include file="common/header.tpl"}

<br />

{if $intro}
<p>{$intro|nl2br}</p>
{/if}

{iterate from=journals item=journal}

<h3>{$journal->getJournalTitle()|escape}</h3>

{if $journal->getJournalDescription()}
<p>{$journal->getJournalDescription()|nl2br}</p>
{/if}

<p><a href="{url journal=$journal->getPath()}" class="action">{translate key="site.journalView"}</a> | <a href="{url journal=$journal->getPath() page="issue" op="current"}" class="action">{translate key="site.journalCurrent"}</a> | <a href="{url journal=$journal->getPath() page="user" op="register"}" class="action">{translate key="site.journalRegister"}</a></p>
{/iterate}

{include file="common/footer.tpl"}
