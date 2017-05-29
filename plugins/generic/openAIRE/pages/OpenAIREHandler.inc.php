<?php

/**
 * @file plugins/generic/openAIRE/OpenAIREHandler.inc.php
 *
 * Copyright (c) 2015-2017 University of Pittsburgh
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2 or later. For full terms see the file docs/COPYING.
 *
 * Contributed by 4Science (http://www.4science.it).
 * 
 * @class OpenAIREHandler
 * @ingroup plugins_generic_openAIRE
 *
 * @brief Handle openAIRE search requests
 */

import('classes.handler.Handler');

class OpenAIREHandler extends Handler {

	/**
	 * Search for project by grantId or name.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function searchProject($args, &$request) {
		$plugin =& PluginRegistry::getPlugin('generic', 'openaireplugin');
		$templateMgr =& TemplateManager::getManager($request);

		switch (Request::getUserVar('targetOp')) {
			case 'form':
				$templateMgr->display($plugin->getTemplatePath() . 'projectIDSearch.tpl');
				break;
			case 'search': 
				$searchType = $request->getUserVar('searchBy');
				$searchValue = $request->getUserVar('searchValue');
				$projectsPage = $request->getUserVar('projectsPage');

				$itemsPerPage = Config::getVar('interface', 'items_per_page');
				$searchResults = array();
				$totalResults = 0;

				if ($searchValue) {
					$queryParams = array('page'   => $projectsPage,
										 'size'   => $itemsPerPage);
					if ($searchType == 'id') {
						$query = http_build_query(array_merge(array('grantID' => $searchValue), $queryParams));
					} else {
						$query = http_build_query(array_merge(array('name' => $searchValue), $queryParams));
					}
					// Get cURL resource
					$curl = curl_init();
					// Set some options - we are passing in a useragent too here
					curl_setopt_array($curl, array(
						CURLOPT_FAILONERROR    => true,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_URL            => $url = OPENAIRE_API_URL . OPENAIRE_SEARCH_PROJECTS . '?' . $query
					));  
					// Send the request & save response to $resp
					$curlResult = curl_exec($curl);
					// Close request to clear up some resources
					curl_close($curl);
					if ($curlResult) {
						// Parsing response
						$xmlDoc = new DOMDocument();
						if($xmlDoc->loadXML($curlResult)){ 
							$xpath = new DOMXpath($xmlDoc);
							$xpathResult = $xpath->query('/response/header/total');  
							$totalResults = (int) $xpathResult->item(0)->nodeValue;
							if ($totalResults > 0) {
								$resultsList = $xpath->query('/response/results/result');
								foreach ($resultsList as $result) {
									$projectID = false;
									$projectTitle = false;
									$projectFunder = false;
									$projectFundingProgram = false;
									
									// Elaborating data
									$codeEl = $result->getElementsByTagName('code');
									$projectID = ($codeEl->length > 0)?$codeEl->item(0)->nodeValue:false;                                    
									$titleEl = $result->getElementsByTagName('title');
									$projectTitle = ($titleEl->length > 0)?$titleEl->item(0)->nodeValue:false;

									$fundingtreeEl = $result->getElementsByTagName('fundingtree')->item(0);
									$nodeList = $fundingtreeEl->getElementsByTagName('funder');
									if ($nodeList->length > 0) {
										$funderEl = $nodeList->item(0);
										$nodeList = $funderEl->getElementsByTagName('shortname');
										$projectFunder = ($nodeList->length > 0)?$nodeList->item(0)->nodeValue:false;
										$nodeList = $funderEl->getElementsByTagName('id');
										$funderId = ($nodeList->length > 0)?$nodeList->item(0)->nodeValue:false;
										// Check if the project funded by the EC
										if ($funderId == 'ec__________::EC') {
											$nodeList = $fundingtreeEl->getElementsByTagName('funding_level_0');
											if ($nodeList->length > 0) {
												$nodeList = $nodeList->item(0)->getElementsByTagName('name');
												$projectFundingProgram = ($nodeList->length > 0)?$nodeList->item(0)->nodeValue:false;   
											}
										} else {
											$projectFundingProgram = false;
										}
										$searchResults[] = Array('projectID'      => $projectID,
																 'title'          => $projectTitle,
																 'funder'         => $projectFunder,
																 'fundingProgram' => $projectFundingProgram);  
									}
								}
							}
						}
					}
				}

				// Paginate results.
				// the number of total results returned by openAIRE is limited to 10,000
				$totalResults = (($totalResults > 10000)?10000:$totalResults);
				$rangeInfo = Handler::getRangeInfo('projects');
				if ($rangeInfo->isValid()) {                                    
					// Instantiate article iterator.
					import('lib.pkp.classes.core.VirtualArrayIterator');
					$iterator = new VirtualArrayIterator($searchResults, $totalResults, $projectsPage, $itemsPerPage);

					// Prepare and display the article template.
					$templateMgr->assign_by_ref('searchType', $searchType);
					$templateMgr->assign_by_ref('searchValue', $searchValue);
					$templateMgr->assign_by_ref('openaireSearchResults', $iterator);
					$templateMgr->display($plugin->getTemplatePath() . 'projectIDSearchResults.tpl');
				}
				break;
			default: assert(false);
		}
	}

}