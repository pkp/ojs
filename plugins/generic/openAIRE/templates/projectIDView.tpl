{**
 * plugins/generic/openAIRE/projectIDView.tpl
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Contributed by 4Science (http://www.4science.it).
 *
 * OpenAIRE projectID view
 *
 *}
<!-- OpenAIRE -->
<div id="openAIRE">
	<h4>{translate key="plugins.generic.openAIRE.metadata"}</h4>
	<table width="100%" class="data">
		<tr valign="top">
			<td width="20%" class="label">{translate key="plugins.generic.openAIRE.projectID"}</td>
			<td width="80%" class="value">{$submission->getData('projectID')|escape|default:"&mdash;"}</td>
		</tr>
		<tr valign="top">
			<td width="20%" class="label">{translate key="plugins.generic.openAIRE.projectTitle"}</td>
			<td width="80%" class="value">{$submission->getData('projectTitle')|escape|default:"&mdash;"}</td>
		</tr>
		<tr valign="top">
			<td width="20%" class="label">{translate key="plugins.generic.openAIRE.projectFunder"}</td>
			<td width="80%" class="value">{$submission->getData('projectFunder')|escape|default:"&mdash;"}</td>
		</tr>
		<tr valign="top">
			<td width="20%" class="label">{translate key="plugins.generic.openAIRE.projectFundingProgram"}</td>
			<td width="80%" class="value">{$submission->getData('projectFundingProgram')|escape|default:"&mdash;"}</td>
		</tr>
	</table>
</div>
<!-- /OpenAIRE -->