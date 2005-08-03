{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Site index.
 *
 * $Id$
 *}

{include file="common/header.tpl"}

<br />

{if $intro}
<p>{$intro|nl2br}</p>
{/if}

{iterate from=journals item=journal}
<br />

<h3>{$journal->getTitle()|escape}</h3>

{if $journal->getDescription()}
<p>{$journal->getDescription()|nl2br}</p>
{/if}

<p><a href="{$indexUrl}/{$journal->getPath()}" class="action">{translate key="site.journalView"}</a> | <a href="{$indexUrl}/{$journal->getPath()}/issue/current" class="action">{translate key="site.journalCurrent"}</a> | <a href="{$indexUrl}/{$journal->getPath()}/user/register" class="action">{translate key="site.journalRegister"}</a></p>
{/iterate}

{include file="common/footer.tpl"}
