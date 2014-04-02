{**
 * plugins/generic/openAIRE/projectIDEdit.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Edit OpenAIRE projectID 
 *
 *}
<!-- OpenAIRE -->
<div id="openAIRE">
<h3>{translate key="plugins.generic.openAIRE.metadata"}</h3>
<table width="100%" class="data">
<tr valign="top">
	<td rowspan="2" width="20%" class="label">{fieldLabel name="projectID" key="plugins.generic.openAIRE.projectID"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="projectID" id="projectID" value="{$projectID|escape}" size="5" maxlength="10" /></td>
</tr>
<tr valign="top">
	<td><span class="instruct">{translate key="plugins.generic.openAIRE.projectID.description"}</span></td>
</tr>
</table>
</div>
<div class="separator"></div>
<!-- /OpenAIRE -->

