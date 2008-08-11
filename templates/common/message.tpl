{**
 * message.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Generic message page.
 * Displays a simple message and (optionally) a return link.
 *
 * $Id$
 *}
{strip}
{include file="common/header.tpl"}
{/strip}

<p>
	{if !$messageTranslated}
		{translate|assign:"messageTranslated" key=$message}
	{/if}
	{$messageTranslated}
</p>

{if $backLink}
<p>&#187; <a href="{$backLink}">{translate key="$backLinkLabel"}</a></p>
{/if}

{include file="common/footer.tpl"}
