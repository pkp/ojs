{**
 * templates/controllers/informationCenter/informationCenter.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display information center's main modal.
 *}

<script type="text/javascript">
	// Attach the Information Center handler.
	$(function() {ldelim}
		$('#informationCenter').pkpHandler(
			'$.pkp.controllers.TabHandler', {ldelim}
				selected: {$selectedTabIndex|escape:"javascript"}
			{rdelim}
		);
	{rdelim});
</script>

<div id="informationCenter" class="pkp_controllers_informationCenter pkp_controllers_tab">
	<ul>
		{if !$removeHistoryTab}
			<li><a href="{url op="viewHistory" params=$linkParams}">{translate key="submission.informationCenter.history"}</a></li>
		{/if}
		<li><a href="{url op="viewNotes" params=$linkParams}">{translate key="submission.informationCenter.notes"}</a></li>
	</ul>
</div>
