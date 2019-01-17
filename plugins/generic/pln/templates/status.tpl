{**
 * plugins/generic/pln/templates/settingsForm.tpl
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * PLN plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.pln.status_page"}
{include file="common/header.tpl"}
{/strip}

{translate|assign:"confirmReset" key="plugins.generic.pln.status.confirmReset"}
<div id="plnStatus">
	<h3>{translate key="plugins.generic.pln.status.deposits"}</h3>
	<p>{translate key="plugins.generic.pln.status.network_status" networkStatusMessage=$networkStatusMessage}</p>
	<form class="pkp_form" id="plnStatusForm" method="post" action="{plugin_url path="status"}">
		<table>
			<tr>
				<th>{translate key="common.id"}</th>
				<th>{translate key="common.type"}</th>
				<th>{translate key="plugins.generic.pln.status.checked"}</th>
				<th>{translate key="plugins.generic.pln.status.local_status"}</th>
				<th>{translate key="plugins.generic.pln.status.processing_status"}</th>
				<th>{translate key="plugins.generic.pln.status.lockss_status"}</th>
				<th>{translate key="plugins.generic.pln.status.complete"}</th>
				<th></th>
			</tr>
			{iterate from=deposits item=deposit}
			<tr>
				<td>{$deposit->getId()}</td>
				<td>{$deposit->getObjectType()}</td>
				<td>{$deposit->getLastStatusDate()}</td>
				<td>{translate key=$deposit->getLocalStatus()}</td>
				<td>{translate key=$deposit->getProcessingStatus()}</td>
				<td>{translate key=$deposit->getLockssStatus()}</td>
				<td>{translate key=$deposit->getComplete()}</td>
				<td><input type="submit" name="reset[{$deposit->getId()}]" class="button" value="{translate key="common.reset"}" onclick="return confirm('{$confirmReset|escape}')" /></td>
			</tr>
			{/iterate}
			{if $deposits->wasEmpty()}
			<tr>
				<td colspan="8" class="nodata">{translate key="common.none"}</td>
			</tr>
			<tr><td colspan="8" class="endseparator">&nbsp;</td></tr>
			{else}
				<tr>
					<td colspan="4" align="left">{page_info iterator=$deposits}</td>
					<td colspan="4" align="right">{page_links anchor="deposits" name="deposits" iterator=$deposits}</td>
				</tr>
			{/if}
		</table>
	</form>
		<p>{translate key='plugins.generic.pln.status.docs' statusDocsUrl=$plnStatusDocs}</p>
</div>

{include file="common/footer.tpl"}