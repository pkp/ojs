{**
 * controllers/tab/settings/formImageView.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form image view.
 *
 *}

<div class="pkp_form_file_view pkp_form_image_view">

	<div class="img">
		<img src="{$publicFilesDir}/{$file.uploadName|escape:"url"}?{$file.dateUploaded}" alt="{$commonAltText}" />
	</div>

	<div class="data">
		<span class="title">
			{translate key="common.fileName"}
		</span>
		<span class="value">
			{$file.name|escape}
		</span>
		<span class="title">
			{translate key="common.uploadedDate"}
		</span>
		<span class="value">
			{$file.dateUploaded|date_format:$datetimeFormatShort}
		</span>
		<span class="title">
			{translate key="common.altText"}
		</span>
		<span class="value">
			{$file.altText|escape}
		</span>

		<div id="{$deleteLinkAction->getId()}" class="actions">
			{include file="linkAction/linkAction.tpl" action=$deleteLinkAction contextId=$fileSettingName}
		</div>
	</div>
</div>
