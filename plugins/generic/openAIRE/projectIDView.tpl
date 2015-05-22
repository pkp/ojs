{**
 * plugins/generic/openAIRE/projectIDView.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * OpenAIRE projectID view
 *
 *}
<!-- OpenAIRE -->
<div id="openAIRE">
<h4>{translate key="plugins.generic.openAIRE.metadata"}</h4>
<table width="100%" class="data">
	<tr valign="top">
		<td rowspan="2" width="20%" class="label">{translate key="plugins.generic.openAIRE.projectID"}</td>
		<td width="80%" class="value">{$submission->getData('projectID')|escape|default:"&mdash;"}</td>
	</tr>
</table>
</div>
<!-- /OpenAIRE -->

