{**
 * step3.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 3 of author submit.
 *
 * $Id$
 *}

{assign var="pageTitle" value="author.submit"}
{include file="common/header.tpl"}

<div><a href="{$pageUrl}/author/submit/2">&lt;&lt; {translate key="manager.setup.previousStep"}</a> | <a href="{$pageUrl}/author/submit/4">{translate key="manager.setup.nextStep"} &gt;&gt;</a></div>

<br />

<div class="subTitle">{translate key="manager.setup.stepNumber" step=3}: {translate key="author.submit.upload"}</div>

<br />

<form method="post" action="{$pageUrl}/manager/saveSetup/3" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

<div class="formSectionTitle">3.1 {translate key="author.submit.upload"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="author.submit.uploadInstructions"}</div>

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="upload"}{translate key="common.upload"}:{/formLabel}</td>
	<td class="formField"><input type="file" name="upload" /><input type="submit" value="{translate key="common.upload"}" /></td>
</tr>
</table>


</div>

<br />


</form>

{include file="common/footer.tpl"}