{**
 * templates/controllers/tab/settings/citations/form/citationsForm.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Citation assistant settings
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#citationSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="citationSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="saveFormData" tab="citations"}">
{csrf}

{include file="controllers/notification/inPlaceNotification.tpl" notificationId="policiesFormNotification"}
{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

<div id="citationAssistant">
<h3>{translate key="manager.setup.citationAssistant"}</h3>

<a name="metaCitationEditing"></a>
	<p>{translate key="manager.setup.metaCitationsDescription"}</p>
	<table class="data">
		<tr>
			<td width="5%" class="label">
				<input type="checkbox" name="metaCitations" id="metaCitations" value="1"{if $metaCitations} checked="checked"{/if} />
			</td>
			<td class="value"><label for="metaCitations">{translate key="manager.setup.citations.enable"}</label>
			</td>
		</tr>
	</table>

	<div id="citationFilterSetupToggle" {if !$metaCitations}style="visible: none"{/if}>
		<h4>{translate key="manager.setup.citationFilterParser"}</h4>
		<p>{translate key="manager.setup.citationFilterParserDescription"}</p>
		{load_url_in_div id="parserFilterGridContainer" loadMessageId="manager.setup.filter.parser.grid.loadMessage" url="$parserFilterGridUrl"}

		<h4>{translate key="manager.setup.citationFilterLookup"}</h4>
		<p>{translate key="manager.setup.citationFilterLookupDescription"}</p>
		{load_url_in_div id="lookupFilterGridContainer" loadMessageId="manager.setup.filter.lookup.grid.loadMessage" url="$lookupFilterGridUrl"}
		<h4>{translate key="manager.setup.citationOutput"}</h4>
		<p>{translate key="manager.setup.citationOutputStyleDescription"}</p>
		{fbvElement type="select" id="metaCitationOutputFilterSelect" name="metaCitationOutputFilterId"
				from=$metaCitationOutputFilters translate=false selected=$metaCitationOutputFilterId|escape
				defaultValue="-1" defaultLabel="manager.setup.filter.pleaseSelect"|translate}
	</div>
	{literal}<script type='text/javascript'>
		$(function(){
			// jQuerify DOM elements
			$metaCitationsCheckbox = $('#metaCitations');
			$metaCitationsSetupBox = $('#citationFilterSetupToggle');

			// Set the initial state
			initialCheckboxState = $metaCitationsCheckbox.attr('checked');
			if (initialCheckboxState) {
				$metaCitationsSetupBox.css('display', 'block');
			} else {
				$metaCitationsSetupBox.css('display', 'none');
			}

			// Toggle the settings box.
			// NB: Has to be click() rather than change() to work in IE.
			$metaCitationsCheckbox.click(function(){
				checkboxState = $metaCitationsCheckbox.attr('checked');
				toggleState = ($metaCitationsSetupBox.css('display') === 'block');
				if (checkboxState !== toggleState) {
					$metaCitationsSetupBox.toggle(300);
				}
			});
		});
	</script>{/literal}
</div>

{if !$wizardMode}
	{fbvFormButtons id="setupFormSubmit" submitText="common.save" hideCancel=true}
{/if}

</form>
