{**
 * plugins/generic/openAIRE/projectIDEdit.tpl
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * Contributed by 4Science (http://www.4science.it).
 *
 * Edit OpenAIRE projectID 
 *
 *}
<!-- OpenAIRE -->

<script type="text/javascript">

	function searchProject() {ldelim}
		var oauthWindow = window.open("{url|escape:"javascript" page="openaireapi" op="searchProject" targetOp="form" escape=false}", "_blank", "location=no, titlebar=no, status=no, menubar=no, toolbar=yes, scrollbars=yes, width=700, height=600, top=100, left=500");
		oauthWindow.opener = self;
		return false;
	{rdelim}

	function clearFields() {ldelim}
		document.getElementById('projectID').value = '';  
		document.getElementById('projectTitle').value = '';  
		document.getElementById('projectFunder').value = '';  
		document.getElementById('projectFundingProgram').value = '';  
		return false;
	{rdelim}

</script>  

<div id="openAIRE">
	<h3>{translate key="plugins.generic.openAIRE.metadata"}</h3>
	<table width="100%" class="data">
		<tr>
			<td width="20%" class="label"></td>
			<td td width="80%" class="value">
				<button id="openAIRE_FindByID" tabindex="1" onclick="return searchProject();">{translate key="plugins.generic.openAIRE.search"}</button>
				<button id="openAIRE_clear" tabindex="1" onclick="return clearFields();">{translate key="plugins.generic.openAIRE.clear"}</button>                
			</td>
		</tr>
		<tr valign="top">
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>   
		<tr valign="top">
			<td rowspan="2" width="20%" class="label">{fieldLabel name="projectID" key="plugins.generic.openAIRE.projectID"}</td>
			<td width="80%" class="value">
				<input type="text" class="textField" name="projectID" id="projectID" value="{$projectID|escape}" size="20" maxlength="50" readonly />
			</td>
		</tr>
		<tr valign="top">
			<td><span class="instruct">{translate key="plugins.generic.openAIRE.projectID.description"}</span></td>
		</tr>
		<tr valign="top">
			<td width="20%" class="label">{fieldLabel name="projectTitle" key="plugins.generic.openAIRE.projectTitle"}</td>
			<td width="80%" class="value">
				<input type="text" class="textField" name="projectTitle" id="projectTitle" value="{$projectTitle|escape}" size="60" maxlength="1000" readonly />
			</td>
		</tr>
		<tr valign="top">
			<td width="20%" class="label">{fieldLabel name="funder" key="plugins.generic.openAIRE.projectFunder"}</td>
			<td width="80%" class="value">
				<input type="text" class="textField" name="projectFunder" id="projectFunder" value="{$projectFunder|escape}" size="20" maxlength="10" readonly />
			</td>
		</tr>
		<tr valign="top">
			<td width="20%" class="label">{fieldLabel name="fundingProgram" key="plugins.generic.openAIRE.projectFundingProgram"}</td>
			<td width="80%" class="value">
				<input type="text" class="textField" name="projectFundingProgram" id="projectFundingProgram" value="{$projectFundingProgram|escape}" size="20" maxlength="10" readonly />
			</td>
		</tr>
	</table>
</div>
<div class="separator"></div>
<!-- /OpenAIRE -->