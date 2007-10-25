{**
 * settingsForm.tpl
 *
 * Copyright (c) 2000-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for manual payment settings.
 *
 *}
	<tr>
		<td colspan="2"><h4>{translate key="plugins.paymethod.manual.settings"}</td>
	</tr>
	<tr valign="top">
		<td class="label" width="20%">{fieldLabel name="manualInstructions" required="true" key="plugins.paymethod.manual.settings.instructions"}</td>
		<td class="value" width="80%">
			{translate key="plugins.paymethod.manual.settings.manualInstructions"}<br />
			<textarea name="manualInstructions" id="manualInstructions" rows="12" cols="60" class="textArea">{$manualInstructions}</textarea>
		</td>
	</tr>
