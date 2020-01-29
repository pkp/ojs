<?php

/**
 * @file plugins/importexport/medra/MedraExportPlugin.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MedraExportPlugin
 * @ingroup plugins_importexport_medra
 *
 * @brief mEDRA Onix for DOI (O4DOI) export/registration plugin.
 */

import('classes.plugins.DOIPubIdExportPlugin');

// O4DOI schemas.
define('O4DOI_ISSUE_AS_WORK', 0x01);
define('O4DOI_ISSUE_AS_MANIFESTATION', 0x02);
define('O4DOI_ARTICLE_AS_WORK', 0x03);
define('O4DOI_ARTICLE_AS_MANIFESTATION', 0x04);

class MedraExportPlugin extends DOIPubIdExportPlugin {

	/**
	 * @see Plugin::getName()
	 */
	function getName() {
		return 'MedraExportPlugin';
	}

	/**
	 * @see Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.importexport.medra.displayName');
	}

	/**
	 * @see Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.importexport.medra.description');
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getSubmissionFilter()
	 */
	function getSubmissionFilter() {
		return 'article=>medra-xml';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getIssueFilter()
	 */
	function getIssueFilter() {
		return 'issue=>medra-xml';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getRepresentationFilter()
	 */
	function getRepresentationFilter() {
		return 'galley=>medra-xml';
	}

	/**
	 * @copydoc ImportExportPlugin::getPluginSettingsPrefix()
	 */
	function getPluginSettingsPrefix() {
		return 'medra';
	}

	/**
	 * @copydoc DOIPubIdExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName() {
		return 'MedraSettingsForm';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::getExportDeploymentClassName()
	 */
	function getExportDeploymentClassName() {
		return 'MedraExportDeployment';
	}

	/**
	 * @copydoc PubObjectsExportPlugin::depositXML()
	 */
	function depositXML($objects, $context, $filename) {
		// Use a different endpoint for testing and
		// production.
		$this->import('classes.MedraWebservice');
		// New endpoint introduced: if the user select the checkbox to deposit also in Crossref, the relative and correct endpoint is choosen. it could be the staging endpoint o production endpoint
		$endpoint = ($this->isTestMode($context) ? ($this->_request->getUserVar('crEnabled') == 'on' ? MEDRA2CR_WS_ENDPOINT_DEV : MEDRA_WS_ENDPOINT_DEV) : ($this->_request->getUserVar('crEnabled') == 'on' ? MEDRA2CR_WS_ENDPOINT : MEDRA_WS_ENDPOINT));

		// Get credentials.
		$username = $this->getSetting($context->getId(), 'username');
		$password = $this->getSetting($context->getId(), 'password');
		// Retrieve the XML.
		assert(is_readable($filename));
		$xml = file_get_contents($filename);
		assert($xml !== false && !empty($xml));
		
		// Select the language
		$lang = 'eng';
		if(substr($this->_request->getUserVar('language'), 0, 2) == 'it'){
		    $lang = 'ita';
		} else if(substr($this->_request->getUserVar('language'), 0, 2) == 'de'){
		    $lang = 'ger';
		}

		// Instantiate the mEDRA web service wrapper.
		$ws = new MedraWebservice($endpoint, $username, $password);
		// Register the XML with mEDRA.
		//$result = $ws->upload($xml);
		//The selected checkbox determines the only deposit on mEDRA or the further deposit also in Crossref 
		$result = $this->_request->getUserVar('crEnabled') == 'on' ? $ws->deposit($xml, $lang) : $ws->upload($xml);

		if ($result === true) {
			// Mark all objects as registered.
			foreach($objects as $object) {
				$object->setData($this->getDepositStatusSettingName(), EXPORT_STATUS_REGISTERED);
				$this->saveRegisteredDoi($context, $object);
			}
		} else {
			// Handle errors. There are validations before sending the request of submission to Crossref endpoint, and there is a need to throw a readable exception to the end user
			//the exception are shown in a table as represented in the code below.
		    if(!assert(PKPString::regexp_match('#<returnCode>success</returnCode>#', $result))){
		        $doc = new DOMDocument();
		        $doc->loadXML($result);
		        
		        $charset = Config::getVar('i18n', 'client_charset');
		        header('Content-type: text/html; charset=' . $charset);
		        echo '<html><body>';
		        echo '<h2>' . __('plugins.importexport.common.validationErrors') . '</h2>';
		        if($doc->getElementsByTagName('statusCode')->item(0)->textContent == 'FAILED'){
		            $numberError = $doc->getElementsByTagName('errorsNumber')->item(0)->textContent;
		            if($numberError > 0){
		                if($lang == 'it'){
		                    echo '<div> mEDRA - Messaggio di errore. </div> <br/>';
		                    echo '<div> Motivo: file non valido rispetto ai requisiti di mEDRA e/o Crossref. </div> <br/>';
		                    echo '<div> Numero di errori: ' . $numberError . '</div> <br/>';
		                    echo '<div> Non sono stati rispettati i seguenti requisiti: </div> <br/>';
		                } else {
		                    echo '<div> mEDRA - Error Message. </div> <br/>';
		                    echo '<div> Cause: file not valid in respect to the mEDRA and/or Crossref requirements. </div> <br/>';
		                    echo '<div> The following requirements have not been met: </div> <br/>';
		                    echo '<div> Number of errors: ' . $numberError . '</div> <br/>';
		                }
		                echo "<table style='border-collapse:collapse; font-size:18;'>";
		                
		                echo "<tr>";
		                
		                if(substr($this->_request->getUserVar('language'), 0, 2) == 'it'){
		                    echo "<td style='border:1px solid black; font-size:18; font-weight:bold; padding:10px;'>CODICE</td>";
		                    echo "<td style='border:1px solid black; font-size:18; font-weight:bold; padding:10px;'>ELEMENTO</td>";
		                    echo "<td style='border:1px solid black; font-size:18; font-weight:bold; padding:10px;'>DESCRIZIONE</td>";
		                } else {
		                    echo "<td style='border:1px solid black; font-size:18; font-weight:bold; padding:10px;'>CODE</td>";
		                    echo "<td style='border:1px solid black; font-size:18; font-weight:bold; padding:10px;'>ELEMENT</td>";
		                    echo "<td style='border:1px solid black; font-size:18; font-weight:bold; padding:10px;'>DESCRIPTION</td>";
		                }
		                
		                echo '</tr>';
		            }
		            
		            $nodeList = $doc->getElementsByTagName('error');
		            $length = $nodeList->length;
		            for ($i = 0; $i < $length; $i++) {
		                
		                $doc->getElementsByTagName('code')->item($i)->textContent;
		                if($i % 2 == 0){
		                    echo "<tr style='background-color: #ABCDEF;'>";
		                } else {
		                    echo "<tr style='background-color: #BDB9AA;'>";
		                }
		                
		                echo "<td style='border:1px solid black; font-size:18; padding:10px;'>" .
		  		                '<a style="text-decoration:none;" href="' . substr($endpoint, 0, strrpos($endpoint, "servlet")) . 'en/crossrefErrorList.htm#' . $doc->getElementsByTagName('code')->item($i)->textContent . '">' .
		  		                $doc->getElementsByTagName('code')->item($i)->textContent  .
		  		                '</a>' .
		  		                '</td>';
		  		                echo "<td style='border:1px solid black; font-size:18; padding:10px;'>" . $doc->getElementsByTagName('reference')->item($i)->textContent  . '</td>';
		  		                echo "<td style='border:1px solid black; font-size:18; padding:10px;'>" . $doc->getElementsByTagName('description')->item($i)->textContent  . '</td>';
		  		                echo '</tr>';
		  		                
		            }
		            
		            if($numberError > 0){
		                echo '</table>';
		            }
		            
		        }
		        
		        libxml_clear_errors();
		        echo '<h3>' . __('plugins.importexport.common.invalidXML') . '</h3>';
		        echo '<p><pre>' . htmlspecialchars($xml) . '</pre></p>';
		        echo '</body></html>';
		    } else
		        if (is_string($result)) {
		            
		            error_log($result);
		            $doc = new DOMDocument();
		            $doc->loadXML($result);
		            $resultCode = $doc->getElementsByTagName('statusCode')->item(0)->nodeValue;
		            
		            $result = array(
		                array('plugins.importexport.common.register.error.mdsError', $resultCode)
		            );
		        } else {
		            
		            $result = false;
			     }
		}
		return $result;
	}

}


