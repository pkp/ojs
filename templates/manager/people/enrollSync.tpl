{**
 * enrollSync.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Synchronize user enrollment with another journal.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="manager.people.enrollment"}
{include file="common/header.tpl"}
{/strip}

<h3>{translate key="manager.people.syncUsers"}</h3>

<p><span class="instruct">{translate key="manager.people.syncUserDescription"}</span></p>

<form method="post" action="{url op="enrollSync"}">

<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label"><label for="rolePath">{translate key="manager.people.enrollSyncRole"}</label></td>
		<td width="80%" class="value">
			{if $rolePath}
				<input type="hidden" name="rolePath" value="{$rolePath|escape}" />
				{translate key=$roleName}
			{else}
				<select name="rolePath" id="rolePath" size="1" class="selectMenu">
					<option value=""></option>
					<option value="all">{translate key="manager.people.allUsers"}</option>
					<option value="manager">{translate key="user.role.manager"}</option>
					<option value="editor">{translate key="user.role.editor"}</option>
					<option value="sectionEditor">{translate key="user.role.sectionEditor"}</option>
					<option value="layoutEditor">{translate key="user.role.layoutEditor"}</option>
					<option value="reviewer">{translate key="user.role.reviewer"}</option>
					<option value="copyeditor">{translate key="user.role.copyeditor"}</option>
					<option value="proofreader">{translate key="user.role.proofreader"}</option>
					<option value="author">{translate key="user.role.author"}</option>
					<option value="reader">{translate key="user.role.reader"}</option>
					<option value="subscriptionManager">{translate key="user.role.subscriptionManager"}</option>
				</select>
			{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label"><label for="syncJournal">{translate key="manager.people.enrollSyncJournal"}</label></td>
		<td class="value">
			<select name="syncJournal" id="syncJournal" size="1" class="selectMenu">
				<option value=""></option>
				<option value="all">{translate key="manager.people.allJournals"}</option>
				{html_options options=$journalOptions}
			</select>
		</td>
	</tr>
</table>

<p><input type="submit" value="{translate key="manager.people.enrollSync"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1)" /></p>

</form>

{include file="common/footer.tpl"}
