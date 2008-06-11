{**
 * error.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Generic error page.
 * Displays a simple error message and (optionally) a return link.
 *
 * $Id$
 *}
{include file="common/header.tpl"}

<span class="errorText">{translate key=$errorMsg params=$errorParams}</span>

{if $backLink}
<br /><br />
&#187; <a href="{$backLink}">{translate key="$backLinkLabel"}</a>
{/if}

{include file="common/footer.tpl"}
