{**
 * citationExport.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Formatted citation export
 *}
<div id="citationEditorExportCanvas" class="canvas">
	<div id="citationEditorExportPane" class="pane text-pane">
		{if $errorMessage}
			<div class="help-message">
				{$errorMessage}
			</div>
		{else}
			<script>
				<!--
				$(function() {ldelim}
					// Activate the export filter selection drop-down boxes.
					$('#exportCitationForm .select')
						.change(function() {ldelim}
							// Check whether a filter has been selected.
							// (The regex below deliberately excludes
							// the default value '-1').
							var filterId = $(this).val();
							if (/^[0-9]+$/.test(filterId)) {ldelim}
								var $throbberId = $('#citationEditorExportPane');
								$throbberId.triggerHandler('actionStart');
								$.post(
									'{url op="exportCitations"}',
									{ldelim}
										'assocId': {$assocId},
										'filterId': filterId
									{rdelim},
									function(jsonData) {ldelim}
										$throbberId.triggerHandler('actionStop');
										if (jsonData !== null && jsonData.status === true) {ldelim}
											$('#citationEditorExportCanvas').replaceWith(jsonData.content);
										{rdelim}
									{rdelim},
									'json'
								);
							{rdelim} else {ldelim}
								$(this).val($(this).data('original-value'));
								alert('{translate key="submission.citations.editor.export.filterSelectionError"}');
							{rdelim}
						{rdelim})
						.each(function() {ldelim}
							// Save the original selection.
							$(this).data('original-value', $(this).val());
						{rdelim});

					// Activate throbber for tab reloading.
					actionThrobber('#citationEditorExportPane');

					// Activate the "select all" link.
					$('#markForExport').click(function() {ldelim}
						$('#citationEditorExportPane .scrollable').selectRange();
						return false;
					{rdelim});
				{rdelim});
				// -->
			</script>
			<form class="pkp_form" id="exportCitationForm" method="post" action="">
				{csrf}
				<br />
				<p>
					<p>{translate key="submission.citations.editor.export.filterSelectionDescription"}</p>
					<select class="field select" name="filterId" id="exportFilterSelect">
						{foreach name="exportFilters" from=$exportFilters key="exportFilterGroupHeader" item="exportFilterOptions"}
							<option value="-1">{translate key=$exportFilterGroupHeader}</option>
							{html_options options=$exportFilterOptions selected=$exportFilterId}
							{if !$smarty.foreach.exportFilters.last}<option value="-1">&nbsp;</option>{/if}
						{/foreach}
					</select>
				</p>
			</form>

			{if $exportOutput}
				<p>{translate key="submission.citations.editor.export.exportDescription"}</p>

				<div class="scrollable">
					--<p/>
					{if substr($exportFilterType, 0, 5) == 'xml::'}
						<pre>{$exportOutput|escape:htmlall}</pre>
					{else}
						{$exportOutput}
					{/if}
					--
				</div>
			{/if}
		{/if}
	</div>
</div>
