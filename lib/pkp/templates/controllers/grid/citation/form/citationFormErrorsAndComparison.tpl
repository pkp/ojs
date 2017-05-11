{**
 * templates/controllers/grid/citation/form/citationFormErrorsAndComparison.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * A part of the citation form that will be refreshed
 * whenever the user changes one of the fields (by manual
 * edit or via a database query). Can be used stand-alone
 * or when refreshing the form as a whole.
 *}

{assign var=containerId value="citationEditorDetailCanvas"}
{* create the before/after markup versions of the citations from the citation diff *}
{capture assign=rawCitationWithMarkup}{strip}
	{foreach from=$citationDiff item=change}
		{foreach from=$change key=changeType item=text}
			{* The raw citation gets common strings and deletions *}
			{if $changeType <= 0}
				<span class="citation-comparison-{if $changeType == 0}common{elseif $changeType == -1}deletion{/if}">{$text}</span>
			{/if}
		{/foreach}
	{/foreach}
{/strip}{/capture}
{capture assign=generatedCitationWithMarkup}{strip}
	{foreach from=$citationDiff item=change}
		{foreach from=$change key=changeType item=text}
			{* The generated citation gets common strings and additions *}
			{if $changeType >= 0}
				<span class="citation-comparison-{if $changeType == 0}common{elseif $changeType == 1}addition{/if}">{$text}</span>
			{/if}
		{/foreach}
	{/foreach}
{/strip}{/capture}
<script>
	<!--
	$(function() {ldelim}
		//
		// Initial setup
		//
		// Initial setup depends on whether we add or
		// edit a citation.
		{if $citation->getId()}
			// Hide editable raw citation on startup unless we're adding a
			// new citation.
			$('#editableRawCitation').hide();
		{else}
			// Hide the citation comparison markup instead when we add a new
			// citation.
			$('.citation-comparison').hide();
		{/if}


		//
		// Handle form messages
		//
		// "Click to dismiss message" feature. Must be done
		// with live as we use JS to insert messages sometimes.
		$('#citationFormMessages li').die('click').live('click', function() {ldelim}
			$(this).remove();
			if($('#citationFormMessages .pkp_form_error_list').children().length === 0) {ldelim}
				$('#citationFormMessages').remove();
			{rdelim}
		{rdelim});


		//
		// Handle raw citation edition
		//
		// Clicking on the raw citation should make it editable.
		$('#rawCitationWithMarkup div.value, #rawCitationWithMarkup .actions a.edit').each(function() {ldelim}
			$(this).click(function() {ldelim}
				$('#rawCitationWithMarkup').hide();
				$editableRawCitation = $('#editableRawCitation').show();
				$textarea = $editableRawCitation.find('textarea').focus();

				// Save original value for undo
				$textarea.data('original-value', $textarea.val());
				return false;
			{rdelim});
		{rdelim});

		// Handle expert settings
		extrasOnDemand('#rawCitationEditingExpertOptions');

		// Handle abort raw citation editing.
		$('#cancelRawCitationEditing').click(function() {ldelim}
			$editableRawCitation = $('#editableRawCitation').hide();
			$('#rawCitationWithMarkup').show();

			// Restore original raw citation value.
			$textarea = $editableRawCitation.find('textarea');
			$textarea.val($textarea.data('original-value'));
			return false;
		{rdelim});

		// Open a confirmation dialog when the user
		// clicks the "process raw citation" button.
		$('#processRawCitation')
			// modalConfirm() doesn't remove prior events
			// so we do it ourselves.
			.die('click')
			// Activate a throbber when the button is clicked.
			.click(function() {ldelim}
				// Throbber for raw citation processing.
				actionThrobber('#{$containerId}');
			{rdelim});

		{if $rawCitationEditingWarningHide}
			// Process the citation without asking the user.
			ajaxAction(
				'#{$containerId}',
				'#processRawCitation',
				'{url op="updateRawCitation"}',
				null,
				'click',
				'#editCitationForm'
			);
		{else}
			// Configure the dialog.
			var warningContent =
				'{translate key="submission.citations.editor.details.processRawCitationWarning"}<br /><br />' +
				'<input id="rawCitationEditingWarningHide" type="checkbox" />{translate|escape:javascript key="submission.citations.editor.details.dontShowMessageAgain"}';
			modalConfirm(
				'{url op="updateRawCitation"}',
				'replace',
				'#{$containerId}',
				warningContent,
				[
					'{translate key="submission.citations.editor.details.processRawCitationGoAhead"}',
					'{translate key="common.cancel"}'
				],
				'#processRawCitation',
				'{translate key="submission.citations.editor.details.processRawCitationTitle"}',
				true
			);

			// Feature to disable raw citation editing warning.
			$('#rawCitationEditingWarningHide').die('click').live('click', function() {ldelim}
				$.getJSON(
					'{url router=$smarty.const.ROUTE_COMPONENT component="api.user.UserApiHandler" op="updateUserMessageState"}?setting-name=citation-editor-hide-raw-editing-warning&setting-value='+($(this).attr('checked')===true ? 'true' : 'false'),
					function(jsonData) {ldelim}
						if (jsonData.status !== true) {ldelim}
							alert(jsonData.content);
						{rdelim}
					{rdelim}
				);
			{rdelim});
		{/if}

		//
		// Handle field level data changes.
		//
		// Register event handler for refresh of the citation
		// comparison and message part of the form.
		ajaxAction(
			'#citationFormErrorsAndComparison',
			// We bind the wrapper to a custom event. This can
			// be manually triggered if we want to refresh the
			// interface for some reason.
			'#citationFormErrorsAndComparison',
			'{url op="fetchCitationFormErrorsAndComparison"}',
			null,
			'refresh',
			'#editCitationForm'
		);

		// Bind citation fields with live to the refresh event
		// so that new fields will be automatically active.
		$('.citation-field').die('change').live('change', function() {ldelim}
			$('#citationFormErrorsAndComparison').triggerHandler('refresh');
		{rdelim});
	{rdelim});
	// -->
</script>
<div id="citationFormErrorsAndComparison" class="form-block">
	{if $unsavedChanges || $isError}
		<div id="citationFormMessages" class="help-message" title="{translate key="submission.citations.editor.details.clickToDismissMessage"}">
			<div id="formErrors">
				<p>
					<span class="pkp_form_error">{translate key="submission.citations.editor.details.messages"}:</span>
					<ul class="pkp_form_error_list">
						{if $unsavedChanges}
							<li class="unsaved-data-warning">{translate key="submission.citations.editor.details.unsavedChanges"}</li>
						{/if}
						{if $isError}
							{foreach key=field item=message from=$errors}
								<li>{$message}</li>
							{/foreach}
						{/if}
					</ul>
				</p>
			</div>
		</div>
	{/if}


	{* We have two versions of the raw citation - one editable and the
	   other with mark-up for comparison. We use JS to switch between the
	   two on user demand. *}
	<div id="editableRawCitation">
		<div class="label">
			{if $citation->getId()}
				{fieldLabel name="rawCitation" key="submission.citations.editor.details.rawCitation"}
			{else}
				{fieldLabel name="rawCitation" key="submission.citations.editor.citationlist.newCitation"}
			{/if}
		</div>
		<div class="value">
			{* Hack to fix textarea sizing bug in IE7 - only required when editing existing citation. *}
			{if $citation->getId()}<!--[if lt IE 8]><div><![endif]-->{/if}
			<textarea class="textarea" validation="required" id="rawCitation" name="rawCitation" rows="5">{$rawCitation}</textarea>
			{if $citation->getId()}<!--[if lt IE 8]></div><![endif]-->{/if}
		</div>
		{if $citation->getId()}
			<div id="rawCitationEditingExpertOptions">
				<div class="options-head">
					<span class="ui-icon"></span>
					<span class="option-block-inactive">{translate key="submission.citations.editor.details.editRawCitationExpertSettingsInactive"}</span>
					<span class="option-block-active">{translate key="submission.citations.editor.details.editRawCitationExpertSettingsActive"}</span>
				</div>
				{include file="controllers/grid/citation/form/citationFilterOptionBlock.tpl"
					titleKey="submission.citations.editor.details.editRawCitationExtractionServices"
					availableFilters=$availableParserFilters}
			</div>
			<div class="form-block actions">
				<button id="cancelRawCitationEditing" type="button" title="{translate key="common.cancel"}">{translate key="common.cancel"}</button>
				<button id="processRawCitation" type="button" title="{translate key="submission.citations.editor.details.processRawCitation"}">{translate key="submission.citations.editor.details.processRawCitation"}</button>
			</div>
			<div class="pkp_helpers_clear"></div>
		{/if}
	</div>
	<div id="rawCitationWithMarkup" class="citation-comparison">
		<div class="label">{translate key="submission.citations.editor.details.rawCitation"}</div>
		<div class="actions">
			<a class="edit" title="{translate key="submission.citations.editor.clickToEdit"}" href=""></a>
		</div>
		<div class="value ui-corner-all" title="{translate key="submission.citations.editor.clickToEdit"}">{$rawCitationWithMarkup}</div>
	</div>
	<div id="generatedCitationWithMarkup" class="citation-comparison">
		<div class="label">{translate key="submission.citations.editor.details.citationExportPreview"} ({$currentOutputFilter})</div>
		<div class="value ui-corner-all">{$generatedCitationWithMarkup}</div>
	</div>
</div>

