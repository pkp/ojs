{**
 * controllers/tab/settings/appearance/form/setup.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Common setup required for the appearance settings form.
 *
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#appearanceForm').pkpHandler('$.pkp.controllers.tab.settings.form.FileViewFormHandler',
			{ldelim}
				fetchFileUrl: {url|json_encode op='fetchFile' tab='appearance' escape=false}
			{rdelim}
		);
	{rdelim});
</script>

{* In wizard mode, these fields should be hidden *}
{if $wizardMode}
	{assign var="wizardClass" value="is_wizard_mode"}
{else}
	{assign var="wizardClass" value=""}
{/if}
