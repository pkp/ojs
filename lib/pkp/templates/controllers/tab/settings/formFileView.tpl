{**
 * controllers/tab/settings/formImageView.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form file view.
 *
 *}

<div class="pkp_form_file_view">

	<div class="data">
		<span class="title">
			{translate key="common.fileName"}
		</span>
		<span class="value">
			<a href="{$publicFilesDir}/{$file.uploadName|escape:"url"}?{$file.dateUploaded}" class="file">{$file.name|escape}</a>
		</span>
		<span class="title">
			{translate key="common.uploadedDate"}
		</span>
		<span class="value">
			{$file.dateUploaded|date_format:$datetimeFormatShort}
		</span>

		<div id="{$deleteLinkAction->getId()}" class="actions">
			{include file="linkAction/linkAction.tpl" action=$deleteLinkAction contextId=$fileSettingName}
		</div>
	</div>
</div>
