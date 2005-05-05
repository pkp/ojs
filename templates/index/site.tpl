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

{$intro|nl2br}


{iterate from=journals item=journal}
<br /><br />

<h3>{$journal->getTitle()}</h3>
{$journal->getDescription()|nl2br}
<br />
<a href="{$indexUrl}/{$journal->getPath()}" class="action">{translate key="site.journalView"}</a> | <a href="{$indexUrl}/{$journal->getPath()}/user/register" class="action">{translate key="site.journalRegister"}</a>
{/iterate}

{include file="common/footer.tpl"}
