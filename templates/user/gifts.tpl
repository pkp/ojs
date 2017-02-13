{**
 * templates/user/gifts.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User gifts management page.
 *}
{strip}
{assign var="pageTitle" value="gifts.myGifts"}
{include file="common/header.tpl"}
{/strip}

{if $acceptGiftSubscriptionPayments}
<h3>{translate key="gifts.subscriptions"}</h3>
<p>{translate key="gifts.subscriptionsDescription"}</p>
<p><a class="action" href="{url page="gifts" op="purchaseGiftSubscription"}">{translate key="gifts.purchaseGiftSubscription"}</a></p>

<br />

<table class="info">
	{iterate from=giftSubscriptions item=gift}
		<tr>
			<td>{$gift->getGiftName()|escape}</td>
			<td>
			{assign var="giftStatus" value=$gift->getStatus()}
			{if $giftStatus == $smarty.const.GIFT_STATUS_NOT_REDEEMED}
				<span class="disabled">{translate key="gifts.status.notRedeemed"}</span>
			{elseif $giftStatus == $smarty.const.GIFT_STATUS_REDEEMED}
				<span class="disabled">{translate key="gifts.status.redeemed"}</span>
			{/if}
			</td>
			<td align="right">
				{if $giftStatus == $smarty.const.GIFT_STATUS_NOT_REDEEMED}
					<a class="action" href="{url op="redeemGift" path=$gift->getId()}">{translate key="gifts.redeemGift"}</a>
				{elseif $giftStatus == $smarty.const.GIFT_STATUS_REDEEMED}
					{$gift->getDatetimeRedeemed()|escape}
				{else}
					&nbsp;
				{/if}
			</td>
		</tr>
		<tr>
			<td colspan="3" class="separator">&nbsp;</td>
		</tr>
	{/iterate}
	{if $giftSubscriptions->wasEmpty()}
		<tr>
			<td colspan="3" class="separator">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="3" class="nodata">{translate key="gifts.noSubscriptions"}</td>
		</tr>
		<tr>
			<td colspan="3" class="separator">&nbsp;</td>
		</tr>
	{/if}
</table>
{/if}

{include file="common/footer.tpl"}
