{**
 * plugins/generic/openAIRE/projectIDEdit.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit OpenAIRE projectID 
 *
 *}
<!-- OpenAIRE -->
<div id="openAIRE">
<h3>{translate key="plugins.generic.openAIRE.metadata"}</h3>
<table class="data">
<tr>
	<td rowspan="2" class="label">{fieldLabel name="projectID" key="plugins.generic.openAIRE.projectID"}</td>
	<td class="value"><input type="text" class="textField" name="projectID" id="projectID" value="{$projectID|escape}" size="5" maxlength="10" /></td>
</tr>
<tr>
	<td><span class="instruct">{translate key="plugins.generic.openAIRE.projectID.description"}</span></td>
</tr>
</table>
</div>
<div class="separator"></div>
<!-- /OpenAIRE -->

