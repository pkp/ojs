{**
 * submitterEdit.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for changing submitter of an article
 *}
{strip}
{translate|assign:"pageTitleTranslated" key="submission.editSubmitter" articleId=$articleId}
{include file="common/header.tpl"}
{/strip}

<form name="submitter" method="post" action="{url op="saveSubmitter"}" enctype="multipart/form-data">
<input type="hidden" name="articleId" value="{$articleId|escape}" />
{include file="common/formErrors.tpl"}

<div id="chooseSubmitter">

<p>Only <strong>one author</strong> (or submitter) may interact with a manuscript using the author role and tools. By default, the person who originally submitted the manuscript has this permission. You may select a different user for this permission below:</p>
<table width="100%" class="data">
	<tr>
		<td width="5%" class="label" valign="top">&nbsp;</td>
		<td>
			<!-- current submitter -->
			{if !$submitterIsAuthor}
			<input type="radio" name="submitter" value="{$submitterId}" checked="checked" /> {$submitterFirstName} {$submitterMiddleName} {$submitterLastName} ({$submitterUsername})<br/>	
			{/if}
			<!-- currently logged in user --> 
			{if $sessionUserId != $submitterId}
			<input type="radio" name="submitter" value="{$sessionUserId}" /> {$sessionUserFirstName} {$sessionUserMiddleName} {$sessionUserLastName} ({$sessionUsername})<br/>
			{/if}
			<!-- author(s) -->
			{foreach name=authors from=$authors key=authorIndex item=author}
			{url|assign:"editMetadataUrl" op="viewMetadata" path=$articleId}
			<input type="radio" name="submitter" value="{$author.userId}" {if $author.isSubmitter} checked="checked"{/if}{if !$author.userId}disabled{/if}/> 
			{if !$author.userId}<font color="#A4A4A4"><em>{/if}
			{$author.firstName} {$author.middleName} {$author.lastName} {if $author.email}({$author.email}){else}(<a href="{$editMetadataUrl}">Add author email address.</a>){/if} {if $primaryContact == $authorIndex}[primary contact author]{/if} </em></font>{if !$author.userId}{assign var=hasUnregisteredAuthor value=1}*{/if}<br/>
			{/foreach}
		</td>
	</tr>
</table>

<!-- submit and cancel buttons -->
<p>
        <input type="submit" value="{translate key="common.save"}" class="button defaultButton"/>
        <input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1)" />
</p>

</form>

<!-- instructions -->
{if $hasUnregisteredAuthor}
	<p>* Indicates an author who is not enrolled in this journal and therefore cannot be assigned as the submitter. To enable one of these authors as a submitter:</p>
	<ol>
		<li>Make sure that the author's name above has an email address next to it. If not, add one.</li>
		<li>
			{if $sessionUserIsJournalManager}
				{url|assign:"changeSubmitterUrl" page="editor" op="changeSubmitter" path=$articleId}
				Click <a href="{url page="manager" op="createUser" roleId="65536" source=$changeSubmitterUrl}">here</a> to create an account for the author, making sure that the email address used to create the account matches the email address listed next to their name in the list above.
			{else}
				Contact your journal manager to request a new account for the author, making sure that the email address used to create the account matches the email address listed next to their name in the list above.
			{/if}
		</li>
		<li>Return to this screen and select the radio button next to the author's name, then click the Save button.</li>
	</ol>
{/if}

</div>

{include file="common/footer.tpl"}

