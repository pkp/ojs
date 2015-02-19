<?php

/**
 * @file classes/journal/SocialMedia.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SocialMedia
 * @ingroup context
 * @see SocialMediaDAO
 *
 * @brief Describes basic SocialMedia properties.
 */

import('lib.pkp.classes.context.PKPSocialMedia');

class SocialMedia extends PKPSocialMedia {
	/**
	 * Constructor.
	 */
	function SocialMedia() {
		parent::PKPSocialMedia();
	}

	/**
	 * Replace various variables in the code template with data
	 * relevant to the assigned article.
	 * @param PublishedArticle $publishedArticle
	 */
	function replaceCodeVars($publishedArticle = null) {

		$application = Application::getApplication();
		$request = $application->getRequest();
		$router = $request->getRouter();
		$context = $request->getContext();

		$code = $this->getCode();

		$codeVariables = array(
				'journalUrl' => $router->url($request, null, 'index'),
				'journalName' => $context->getLocalizedName(),
			);

		if (isset($publishedArticle)) {
			$codeVariables = array_merge($codeVariables, array(
				'articleUrl' => $router->url($request, null, 'article', 'view', $publishedArticle->getId()),
				'articleTitle' => $publishedArticle->getLocalizedTitle(),
			));
		}

		// Replace variables in message with values
		foreach ($codeVariables as $key => $value) {
			if (!is_object($value)) {
				$code = str_replace('{$' . $key . '}', $value, $code);
			}
		}

		$this->setCode($code);
	}
}

?>
