{**
 * templates/status.tpl
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * PLN plugin settings
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#plnStatusForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

{capture assign="confirmReset"}{translate key="plugins.generic.pln.status.confirmReset"}{/capture}
<div id="plnStatus">
	<h3>{translate key="plugins.generic.pln.status.deposits"}</h3>
	<p>{translate key="plugins.generic.pln.status.network_status" networkStatusMessage=$networkStatusMessage}</p>
	{capture assign="depositsGridUrl"}{url component="plugins.generic.pln.controllers.grid.PLNStatusGridHandler" op="fetchGrid" escape=false}{/capture}
	{load_url_in_div id="depositsGridContainer" url=$depositsGridUrl}
	<p>
		<b>{translate key='plugins.generic.pln.status.description.title'}</b>
		<ul>
			<li>
				<b>{translate key='plugins.generic.pln.displayedstatus.pending'}</b>: {translate key='plugins.generic.pln.displayedstatus.pending.description'}
			</li>
			<li>
				<b>{translate key='plugins.generic.pln.displayedstatus.inprogress'}</b>: {translate key='plugins.generic.pln.displayedstatus.inprogress.description'}
			</li>
			<li>
				<b>{translate key='plugins.generic.pln.displayedstatus.completed'}</b>: {translate key='plugins.generic.pln.displayedstatus.completed.description'}
			</li>
			<li>
				<b>{translate key='plugins.generic.pln.displayedstatus.error'}</b>: {translate key='plugins.generic.pln.displayedstatus.error.description'}
			</li>
		</ul>
	</p>
	<p><span class='fa fa-exclamation-triangle'></span> {translate key='plugins.generic.pln.status.warning'}</p>
	
</div>
