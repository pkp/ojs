<?php

/**
 * @file plugins/generic/sehl/SehlPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SehlPlugin
 * @ingroup plugins_generic_sehl
 *
 * @brief Search Engine HighLighting plugin
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class SehlPlugin extends GenericPlugin {
	/** @var $queryTerms string */
	var $queryTerms;

	function register($category, $path) {
		if (parent::register($category, $path)) {
			$journal =& Request::getJournal();
			$journalId = $journal?$journal->getId():0;

			HookRegistry::register('TemplateManager::display',array(&$this, 'displayTemplateCallback'));
			return true;
		}
		return false;
	}

	function parse_quote_string($query_string) {
		/* urldecode the string and setup variables */
		$query_string = urldecode($query_string);
		$quote_flag = false;
		$word = '';
		$terms = array();

		/* loop through character by character and move terms to an array */
		for ($i=0; $i<strlen($query_string); $i++) {
			$char = substr($query_string, $i, 1);
			if ($char == '"') {
				if ($quote_flag) $quote_flag = false;
				else $quote_flag = true;
			}
			if (($char == ' ') && (!($quote_flag))) {
				$terms[] = $word;
				$word = '';
			} else {
				if (!($char == '"')) $word .= $char;
			}
		}
		$terms[] = $word;
		/* return the fully parsed array */
		return $terms;
	}

	function displayTemplateCallback($hookName, $args) {
		$templateMgr =& $args[0];
		$template =& $args[1];

		if ($template != 'article/article.tpl') return false;

		// Determine the query terms to use.
		$queryVariableNames = array(
			'q', 'p', 'ask', 'searchfor', 'key', 'query', 'search',
			'keyword', 'keywords', 'qry', 'searchitem', 'kwd',
			'recherche', 'search_text', 'search_term', 'term',
			'terms', 'qq', 'qry_str', 'qu', 's', 'k', 't', 'va'
		);
		$this->queryTerms = array();
		if (($referer = getenv('HTTP_REFERER')) == '') return false;
		$urlParts = parse_url($referer);
		if (!isset($urlParts['query'])) return false;

		$queryArray = explode('&', $urlParts['query']);
		foreach ($queryArray as $var) {
			$varArray = explode('=', $var);
			if (in_array($varArray[0], $queryVariableNames) && !empty($varArray[1])) {
				$this->queryTerms += $this->parse_quote_string($varArray[1]);
			}
		}

		if (empty($this->queryTerms)) return false;

		$templateMgr->addStylesheet(Request::getBaseUrl() . '/' . $this->getPluginPath() . '/sehl.css');
		$templateMgr->register_outputfilter(array(&$this, 'outputFilter'));


		return false;
	}

	function outputFilter($output, &$smarty) {
		// Cannot trust strpos to accept a full string for the needle.
		$fromDiv = strstr($output, '<body');
		if ($fromDiv === false) return $output;

		$endOfBodyTagOffset = strpos($fromDiv, '>');
		$startIndex = strlen($output) - strlen($fromDiv) + $endOfBodyTagOffset + 1;
		$scanPart = substr($output, $startIndex);

		foreach ($this->queryTerms as $q) {
			// Thanks to Brian Suda http://suda.co.uk/projects/SEHL/
			$newOutput = '';
			$pat = '/((<[^!][\/]*?[^<>]*?>)([^<]*))|<!---->|<!--(.*?)-->|((<!--[ \r\n\t]*?)(.*?)[ \r\n\t]*?-->([^<]*))/si';
			preg_match_all($pat, $scanPart, $tag_matches);

			for ($i=0; $i< count($tag_matches[0]); $i++) {
				if (
					(preg_match('/<!/i', $tag_matches[0][$i])) ||
					(preg_match('/<textarea/i', $tag_matches[2][$i])) ||
					(preg_match('/<script/i', $tag_matches[2][$i]))
				) {
					$newOutput .= $tag_matches[0][$i];
				} else {
					$newOutput .= $tag_matches[2][$i];
					$holder = preg_replace('/(.*?)(\W)('.preg_quote($q,'/').')(\W)(.*?)/iu',"\$1\$2<span class=\"sehl\">\$3</span>\$4\$5",' '.$tag_matches[3][$i].' ');
					$newOutput .= substr($holder,1,(strlen($holder)-2));
				}
			}
			$scanPart = $newOutput;
		}
		return (substr($output, 0, $startIndex) . $newOutput);
	}

	function getDisplayName() {
		return __('plugins.generic.sehl.name');
	}

	function getDescription() {
		return __('plugins.generic.sehl.description');
	}
}

?>
