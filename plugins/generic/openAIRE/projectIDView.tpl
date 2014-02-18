{**
 * plugins/generic/openAIRE/projectIDView.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * OpenAIRE projectID view
 *
 *}
<!-- OpenAIRE -->
<div id="openAIRE">
<h4>{translate key="plugins.generic.openAIRE.metadata"}</h4>
<table class="data">
	<tr>
		<td rowspan="2" class="label">{translate key="plugins.generic.openAIRE.projectID"}</td>
		<td class="value">{$submission->getData('projectID')|escape|default:"&mdash;"}</td>
	</tr>
</table>
</div>
<!-- /OpenAIRE -->

