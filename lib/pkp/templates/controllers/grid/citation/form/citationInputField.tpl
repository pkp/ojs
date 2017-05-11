{**
 * citationInputField.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * A template for a single citation input field.
 *
 * Parameters:
 *   $availableFields: an array of field names for the supported meta-data schema.
 *   $fieldName: the name of the field to be constructed
 *   $fieldValue: the current value of the field
 *   $required: whether the field is required
 *}
<tr{if $required} class="citation-field-required"{/if}>
	<td class="first_column">
		<div class="row_container">
			<div class="row_actions">
				<a class="delete" title="{translate key="common.delete"}" href="">&nbsp;</a>
			</div>
			<div class="row_file label">
				<select name="citation-field-input-label[]"
					title="{translate|escape key="submission.citations.editor.details.changeFieldInfo"}"
					class="citation-field-label">
						<option value="-1"{if $fieldName == 'new'} selected="selected"{/if}>{translate|escape key="submission.citations.editor.pleaseSelect"}</option>
						{foreach from=$availableFields key=availableFieldName item=availableField}
							<option value="{$availableFieldName}"{if $availableFieldName == $fieldName} selected="selected"{/if}>{$availableField.displayName}</option>
						{/foreach}
				</select>
			</div>
		</div>
	</td>
	<td class="value">
		<input type="text" class="{if $fieldName == 'new'}new-citation-field{/if} citation-field text large" maxlength="1500"
			value="{if $fieldName == 'new'}{translate|escape key="submission.citations.editor.details.newFieldInfo"}{else}{$fieldValue|escape}{/if}"
			name="{if $fieldName == 'new'}new-field{else}{$fieldName}{/if}" title="{translate|escape key="submission.citations.editor.clickToEdit"}" />
	</td>
</tr>
