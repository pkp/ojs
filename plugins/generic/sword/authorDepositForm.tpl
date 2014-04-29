{**
 * plugins/generic/sword/authorDepositForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of deposit points.
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.sword.displayName"}
{include file="common/header.tpl"}
{/strip}

{if !empty($depositPoints)}
{translate key="plugins.generic.sword.authorDepositDescription" submissionTitle=$article->getLocalizedTitle()}

<div id="depositPoints">
<form method="post" action="{url path="index" path=$article->getId()|to_array:"save"}">

{include file="common/formErrors.tpl"}

<table class="listing" width="100%">
	<tr><td colspan="3" class="headseparator">&nbsp;</td></tr>
	<tr class="heading">
		<td width="5%">&nbsp;</td>
		<td{if !$hasFlexible} colspan="2"{/if}>{translate key="plugins.generic.sword.depositPoints.name"}</td>
		{if $hasFlexible}
			<td width="30%">{translate key="plugins.importexport.sword.depositPoint"}</td>
		{/if}{* $hasFlexible *}
	</tr>
	<tr><td colspan="3" class="headseparator">&nbsp;</td></tr>
{foreach from=$depositPoints item=depositPoint key=depositPointKey name="depositPoints"}
	<tr valign="top">
		<td><input type="checkbox" name="depositPoint[{$depositPointKey|escape}][enabled]" id="depositPoint-{$depositPointKey|escape}-enabled"></td>
		{if $depositPoint.type == $smarty.const.SWORD_DEPOSIT_TYPE_OPTIONAL_SELECTION}
			<td>{fieldLabel name="depositPoint-$depositPointKey-enabled" label=$depositPoint.name}</td>
			<td>
				<select name="depositPoint[{$depositPointKey|escape}][depositPoint]" id="depositPoint-{$depositPointKey|escape}-depositPoint" class="selectMenu">
					{html_options options=$depositPoint.depositPoints}
				</select>
			</td>
		{elseif $depositPoint.type == $smarty.const.SWORD_DEPOSIT_TYPE_OPTIONAL_FIXED}
			<td colspan="2">{fieldLabel name="depositPoint-$depositPointKey-enabled" label=$depositPoint.name}</td>
		{/if}{* $depositPoint.type *}
	</tr>
	<tr>
		<td colspan="6" class="{if $smarty.foreach.depositPoints.last}end{/if}separator">&nbsp;</td>
	</tr>
{/foreach}
</table>
{/if}{* !empty($depositPoints) *}

{if $allowAuthorSpecify}
{translate key="plugins.generic.sword.authorCustomDepositDescription" submissionTitle=$article->getLocalizedTitle()}
<table class="data" width="100%">
	<tr>
		<td class="label"><label for="authorDepositUrl">{translate key="plugins.importexport.sword.depositUrl"}</label></td>
		<td class="value"><input type="text" name="authorDepositUrl" id="authorDepositUrl" class="textField" size="40" maxlength="120" value="{$authorDepositUrl|escape}" /></td>
	</tr>
	<tr>
		<td class="label"><label for="authorDepositUsername">{translate key="user.username"}</label></td>
		<td class="value"><input type="text" name="authorDepositUsername" id="authorDepositUsername" class="textField" size="20" maxlength="120" value="{$authorDepositUsername|escape}" /></td>
	</tr>
	<tr>
		<td class="label"><label for="authorDepositPassword">{translate key="user.password"}</label></td>
		<td class="value"><input type="password" name="authorDepositPassword" id="authorDepositPassword" class="textField" size="20" maxlength="120" /></td>
	</tr>
</table>
{/if}{* $allowAuthorSpecify *}

<br />

<input type="submit" class="button defaultButton" value="{translate key="plugins.importexport.sword.deposit"}" />

</form>
</div>{* depositPoints *}

{include file="common/footer.tpl"}
