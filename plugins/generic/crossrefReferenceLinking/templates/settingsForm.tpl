{**
 * plugins/generic/crossrefReferenceLinking/templates/settingsForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * CrossrefReferenceLinking plugin settings
 *
 *}
<div id="crossrefRefLinkingSettings">
	<p class="pkp_help">
		{translate key="plugins.generic.crossrefReferenceLinking.description.long"}
		<br />
		{translate key="plugins.generic.crossrefReferenceLinking.description.note"}
	</p>
	<p class="pkp_help">{translate key="plugins.generic.crossrefReferenceLinking.description.requirements"}</p>
	{if $crossrefSettingsLinkAction}
		<p>{translate key="plugins.generic.crossrefReferenceLinking.settings.form.crossrefSettings.required"} {include file="linkAction/linkAction.tpl" action=$crossrefSettingsLinkAction}</p>
	{/if}
	{if $submissionSettingsLinkAction}
		<p>{translate key="plugins.generic.crossrefReferenceLinking.settings.form.submissionSettings.required"} {include file="linkAction/linkAction.tpl" action=$submissionSettingsLinkAction}</p>
	{/if}
	{if !$crossrefSettingsLinkAction && !$submissionSettingsLinkAction}
		<p>{translate key="plugins.generic.crossrefReferenceLinking.settings.form.requirements.ok"}</p>
	{/if}
</div>
</form>
