{**
 * block.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- donation links.
 *
 *}
{if $donationEnabled}
<div class="block" id="sidebarDonation">
	<span class="blockTitle"><a href="{url page="donations"}">{translate key="payment.type.donation"}</a></span>
</div>
{/if}
