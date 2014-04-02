{**
 * plugins/generic/referral/authorReferrals.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Referral listing for Authors
 *
 *}

<div class="separator"></div>

<script type="text/javascript">
{literal}
<!--
function toggleChecked() {
	var elements = document.getElementsByName("referralId[]");
	for (var i=0; i < elements.length; i++) {
			elements[i].checked = !elements[i].checked;
	}
}
// -->
{/literal}
</script>

<h3>{translate key="plugins.generic.referral.referrals"}</h3>

<ul class="menu">
	<li{if $referralFilter == null} class="current"{/if}><a href="{url referralFilter=null}">{translate key="plugins.generic.referral.all"}</a></li>
	<li{if $referralFilter == $smarty.const.REFERRAL_STATUS_NEW} class="current"{/if}><a href="{url referralFilter=$smarty.const.REFERRAL_STATUS_NEW}">{translate key="plugins.generic.referral.status.new"}</a></li>
	<li{if $referralFilter == $smarty.const.REFERRAL_STATUS_ACCEPT} class="current"{/if}><a href="{url referralFilter=$smarty.const.REFERRAL_STATUS_ACCEPT}">{translate key="plugins.generic.referral.status.accepted"}</a></li>
	<li{if $referralFilter == $smarty.const.REFERRAL_STATUS_DECLINE} class="current"{/if}><a href="{url referralFilter=$smarty.const.REFERRAL_STATUS_DECLINE}">{translate key="plugins.generic.referral.status.declined"}</a></li>
</ul>

<div id="referrals">
<form action="{url page="referral" op="bulkAction"}" method="post">
<table width="100%" class="listing">
	<tr><td class="headseparator" colspan="8">&nbsp;</td></tr>
	<tr class="heading" valign="bottom">
		<td width="3%">&nbsp;</td>
		<td width="7%">{translate key="plugins.generic.referral.dateAdded"}</td>
		<td width="3%">{translate key="plugins.generic.referral.count"}</td>
		<td>{translate key="common.url"}</td>
		<td>{translate key="article.article}</td>
		<td>{translate key="common.title"}</td>
		<td>{translate key="common.status"}</td>
		<td width="10%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr><td class="headseparator" colspan="8">&nbsp;</td></tr>
{iterate from=referrals item=referral}
	{assign var=articleId value=$referral->getArticleId()}
	<tr valign="top">
		<td><input type="checkbox" name="referralId[]" value="{$referral->getId()}"/></td>
		<td>{$referral->getDateAdded()|date_format:$dateFormatShort}</td>
		<td>{$referral->getLinkCount()|escape}</td>
		<td><a href="{$referral->getUrl()|escape}">{$referral->getUrl()|truncate:50|escape}</a></td>
		<td>{$articleTitles[$articleId]|strip_unsafe_html}</td>
		<td>{$referral->getReferralName()|truncate:50|escape|default:"&mdash;"}</td>
		<td>{translate key=$referral->getStatusKey()}</td>
		<td align="right">
			<a class="action" href="{url page="referral" op="editReferral" path=$referral->getId()}">{translate key="common.edit"}</a>&nbsp;|&nbsp;<a class="action" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.referral.confirmDelete"}')" href="{url page="referral" op="deleteReferral" path=$referral->getId()}">{translate key="common.delete"}</a>
		</td>
	</tr>
	<tr valign="top">
		<td colspan="8" class="{if $referrals->eof()}end{/if}separator">&nbsp;</td>
	</tr>
{/iterate}
{if $referrals->wasEmpty()}
	<tr valign="top">
		<td colspan="8" class="nodata">
			{if $referralFilter == null}
				{translate key="plugins.generic.referral.all.empty"}
			{elseif $referralFilter == $smarty.const.REFERRAL_STATUS_NEW}
				{translate key="plugins.generic.referral.status.new.empty"}
			{elseif $referralFilter == $smarty.const.REFERRAL_STATUS_ACCEPT}
				{translate key="plugins.generic.referral.status.accept.empty"}
			{else}{* REFERRAL_STATUS_DECLINE *}
				{translate key="plugins.generic.referral.status.decline.empty"}
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td colspan="8" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="4" align="left">{page_info iterator=$referrals}</td>
		<td colspan="4" align="right">{page_links anchor="referrals" name="referrals" iterator=$referrals referralFilter=$referralFilter}</td>
	</tr>
{/if}
</table>
<p>
	<input type="submit" name="accept" value="{translate key="plugins.generic.referral.status.accept"}" class="button" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.referral.status.accept.confirm"}')"/>
	<input type="submit" name="decline" value="{translate key="plugins.generic.referral.status.decline"}" class="button" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.referral.status.decline.confirm"}')"/>
	<input type="submit" name="delete" value="{translate key="common.delete"}" class="button" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.referral.confirmDelete"}')"/>
	<input type="button" value="{translate key="common.selectAll"}" class="button" onclick="toggleChecked()" />
</p>
</form>
</div>
