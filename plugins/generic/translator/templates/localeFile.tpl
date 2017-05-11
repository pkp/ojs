{**
 * templates/localeFile.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit a specific locale file.
 *}
{assign var=saveFormId value="saveLocaleFile"|uniqid}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#{$saveFormId}').pkpHandler('$.pkp.controllers.form.AjaxFormHandler',
			{ldelim}
				trackFormChanges: true
			{rdelim}
		);
	{rdelim});
</script>
<form id="{$saveFormId}" action="{url op="save" locale=$locale filename=$filename}" method="post" class="pkp_form">
	{csrf}
	{* An input for the listbuilder to save its changes into *}
	<input type="hidden" name="localeKeys" />

	{* Present the listbuilder *}
	{url|assign:localeFileListbuilderUrl router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.translator.controllers.listbuilder.LocaleFileListbuilderHandler" op="fetchGrid" locale=$locale tabsSelector=$tabsSelector filename=$filename escape=false}
	{load_url_in_div id="localeFileListbuilderContainer"|uniqid url=$localeFileListbuilderUrl}

	{* Form buttons *}
	{fbvElement type="submit" class="submitFormButton" id="submitFormButton-"|uniqid label="common.save"}
</form>
