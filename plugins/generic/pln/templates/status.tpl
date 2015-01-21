{**
 * plugins/generic/pln/templates/settingsForm.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * PLN plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.pln.status_page"}
{include file="common/header.tpl"}
{/strip}

<div id="plnStatus">
	<h3>{translate key="plugins.generic.pln.status.deposits"}</h3>
	<p>{translate key="plugins.generic.pln.status.network_status" networkStatusMessage=$networkStatusMessage}</p>
	<form class="pkp_form" id="plnStatusForm" method="post" action="{plugin_url path="status"}">
		<table>
			<tr>
				<th>{translate key="plugins.generic.pln.status.id"}</th>
				<th>{translate key="plugins.generic.pln.status.type"}</th>
				<th>{translate key="plugins.generic.pln.status.items"}</th>
				<th>{translate key="plugins.generic.pln.status.packaged"}</th>
				<th>{translate key="plugins.generic.pln.status.transferred"}</th>
				<th>{translate key="plugins.generic.pln.status.received"}</th>
				<th>{translate key="plugins.generic.pln.status.syncing"}</th>
				<th>{translate key="plugins.generic.pln.status.synced"}</th>
				<th>{translate key="plugins.generic.pln.status.updated"}</th>
				<th>{translate key="plugins.generic.pln.status.local_failure"}</th>
				<th>{translate key="plugins.generic.pln.status.remote_failure"}</th>
				<th></th>
			</tr>
			{iterate from=deposits item=deposit}
			<tr>
				<td>{$deposit->getId()}</td>
				<td>{$deposit->getObjectType()}</td>
				<td>{$deposit->getDepositObjects()|@count}</td>
				<td>{if $deposit->getPackagedStatus()}{translate key="plugins.generic.pln.status.yes"}{else}{translate key="plugins.generic.pln.status.no"}{/if}</td>
				<td>{if $deposit->getTransferredStatus()}{translate key="plugins.generic.pln.status.yes"}{else}{translate key="plugins.generic.pln.status.no"}{/if}</td>
				<td>{if $deposit->getReceivedStatus()}{translate key="plugins.generic.pln.status.yes"}{else}{translate key="plugins.generic.pln.status.no"}{/if}</td>
				<td>{if $deposit->getSyncingStatus()}{translate key="plugins.generic.pln.status.yes"}{else}{translate key="plugins.generic.pln.status.no"}{/if}</td>
				<td>{if $deposit->getSyncedStatus()}{translate key="plugins.generic.pln.status.yes"}{else}{translate key="plugins.generic.pln.status.no"}{/if}</td>
				<td>{if $deposit->getUpdateStatus()}{translate key="plugins.generic.pln.status.yes"}{else}{translate key="plugins.generic.pln.status.no"}{/if}</td>
				<td>{if $deposit->getLocalFailureStatus()}{translate key="plugins.generic.pln.status.yes"}{else}{translate key="plugins.generic.pln.status.no"}{/if}</td>
				<td>{if $deposit->getRemoteFailureStatus()}{translate key="plugins.generic.pln.status.yes"}{else}{translate key="plugins.generic.pln.status.no"}{/if}</td>
				<td><input type="submit" name="reset[{$deposit->getId()}]" class="button" value="{translate key="plugins.generic.pln.status.reset"}"/></td>
			</tr>
			{/iterate}
			{if $deposits->wasEmpty()}
			<tr>
				<td colspan="12" class="nodata">{translate key="common.none"}</td>
			</tr>
			<tr><td colspan="12" class="endseparator">&nbsp;</td></tr>
			{else}
				<tr>
					<td colspan="7" align="left">{page_info iterator=$deposits}</td>
					<td colspan="5" align="right">{page_links anchor="deposits" name="deposits" iterator=$deposits}</td>
				</tr>
			{/if}
		</table>
	</form>
</div>

{include file="common/footer.tpl"}