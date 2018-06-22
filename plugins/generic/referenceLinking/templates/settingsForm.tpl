{**
 * plugins/generic/referenceLinking/templates/settingsForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * ReferenceLinking plugin settings
 *
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#referenceLinkingSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<div id="crossrefRefLinkingSettings">
	<p class="pkp_help">
		{translate key="plugins.generic.referenceLinking.description.long"}
		<br />
		{translate key="plugins.generic.referenceLinking.description.note"}
	</p>
	<p class="pkp_help">{translate key="plugins.generic.referenceLinking.description.requirements"}</p>
	{if $crossrefSettingsLinkAction}
		<p>{translate key="plugins.generic.referenceLinking.settings.form.crossrefSettings.required"} {include file="linkAction/linkAction.tpl" action=$crossrefSettingsLinkAction}</p>
	{/if}
	{if $submissionSettingsLinkAction}
		<p>{translate key="plugins.generic.referenceLinking.settings.form.submissionSettings.required"} {include file="linkAction/linkAction.tpl" action=$submissionSettingsLinkAction}</p>
	{/if}
	{if !$crossrefSettingsLinkAction && !$submissionSettingsLinkAction}
		<p>{translate key="plugins.generic.referenceLinking.settings.form.requirements.ok"}</p>
	{/if}
</div>
</form>
