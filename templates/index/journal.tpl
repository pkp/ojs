{**
 * index.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Journal index page.
 *
 * $Id$
 *}

{include file="common/header.tpl"}

<a href="{$pageUrl}/login">Login</a>

<br /><br />

<a href="{$pageUrl}/user/register">Register</a>

<br /><br />

<a href="{$indexUrl}/index">Journal Index</a>

<br /><br />

{$journalDescription}

<br /><br />

{if $homepageImage}<div align="center"><img src="{$publicDir}/{$homepageImage.uploadName}" alt="{$homepageImage.name}"/></div>{/if}

<br /><br />

{$additionalContent}

{include file="common/footer.tpl"}