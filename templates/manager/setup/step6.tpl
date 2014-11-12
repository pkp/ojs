{**
 * step6.tpl
 *
 * Step 6 of journal setup.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.setup.customizingEScholarshipBrand"}
{include file="manager/setup/setupHeader.tpl"}

<form name="setupForm" method="post" action="{url op="saveSetup" path="6"}" enctype="multipart/form-data">

<div id="escholBrand">
<h3>6.1 eScholarship Brand</h3>

<div>The URL is: {$subiURL}</div>
<div>user is: {$user}</div>
<div>journal path is: {$journalPath}</div>
<iframe src="{$subiURL}" width="800" height="800"></iframe>

<div class="separator"></div>

<p><input type="submit" onclick="prepBlockFields()" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}

