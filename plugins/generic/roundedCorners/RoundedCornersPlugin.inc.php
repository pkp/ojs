<?php

/**
 * RoundedCornersPlugin.inc.php
 *
 * Copyright (c) 2007 Juan Pablo Alperin, Gunther Eysenbach
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Rounded Corners plugin class
 *
 */
 
import('classes.plugins.GenericPlugin');

class RoundedCornersPlugin extends GenericPlugin {

	function getName() {
		return 'RoundedCornersPlugin';
	}
	
	function getDisplayName() {
		return Locale::translate('plugins.generic.roundedcorners.displayName');
	}
    
	function getDescription() {
		return Locale::translate('plugins.generic.roundedcorners.description');
	}   

	function register($category, $path) {
		if (!Config::getVar('general', 'installed')) return false;
		if (parent::register($category, $path)) {	
			if ( $this->getEnabled() ) {	
				HookRegistry::register('TemplateManager::display',array(&$this, 'roundCorners')); 
			}
			$this->addLocaleData();
			return true;
		}
		return false;
	}
		
	/**
	 * Determine whether or not this plugin is enabled.
	 */
	function getEnabled() {
		$journal = &Request::getJournal();
		if (!$journal) return false;
		return $this->getSetting($journal->getJournalId(), 'enabled');
	}

	/**
	 * Set the enabled/disabled state of this plugin
	 */
	function setEnabled($enabled) {
		$journal = &Request::getJournal();
		if ($journal) {
			$this->updateSetting($journal->getJournalId(), 'enabled', $enabled ? true : false);
			return true;
		}
		var_dump($enabled);
		return false;
	}
	
	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array(
				'disable',
				Locale::translate('manager.plugins.disable')
			);
		} else {
			$verbs[] = array(
				'enable',
				Locale::translate('manager.plugins.enable')
			);
		}
		return $verbs;
	}
	
	/**
	 * Perform management functions
	 */
	function manage($verb, $args) {
		$returner = false;
		
		$enabled = ( $verb == 'enable' );
		$this->setEnabled($enabled);
		return $returner;		
	}
	
	/**
	 * Register the output filter and add the stylesheet
	 */
	function roundCorners( $hookName, $args) {
		$templateMgr =& $args[0];
		
		$baseUrl = $templateMgr->get_template_vars('baseUrl');
		$roundedCornerCssUrl = $baseUrl . '/plugins/generic/roundedCorners/roundedcorners.css';
		$templateMgr->addStyleSheet($roundedCornerCssUrl);
		$templateMgr->register_outputfilter(array(&$this, 'mainOutputFilter'));
	}
	
	/** 
	 * Do the work of adding in the <span> blocks
	 */	
	function mainOutputFilter($output, &$smarty) {
		$top = '<span class="rtop"><span class="r1"></span><span class="r2"></span><span class="r3"></span><span class="r4"></span></span><div style="padding-left: 1em;">';
		$bottom = '</div><span class="rbottom"><span class="r4"></span><span class="r3"></span><span class="r2"></span><span class="r1"></span></span>';
		$newOutput = $output;
		
		$classes = array('block');
		foreach ( $classes as $class ) {
			$matches = $this->_getDivs($newOutput, $class);
			if ( count($matches) > 0 ) {
				foreach ($matches as $match) {	
					if ( preg_match('/<div[^>]+(class|id)\=\"'.$class.'\">(\s*)(<\/div>[^<]*)$/', $match) > 0 ) continue;
					
					$newBlock = preg_replace('/(<div[^>]+(class|id)\=\"'.$class.'\">)/is', "\\1$top", $match, PREG_OFFSET_CAPTURE);
					$newBlock = preg_replace('/([^>]*)(<\/div>[^<]*)$/', "\\1$bottom\\2", $newBlock);
					
					$newOutput = str_replace($match, $newBlock, $newOutput);
				}
			}
		}
		$smarty->unregister_outputfilter('mainOutputFilter');
		
		return $newOutput;
	}
	
	/**
	 * look for the opening and closing divs with a particular $class in the $subject 
	 * Have to count opening and closing divs since regexes are not so good matching opening and closing tags
	 */
	function _getDivs($subject, $class)
	{
	        preg_match_all("/<div[^>]+class\=\"$class\">/is", $subject, $matches, PREG_OFFSET_CAPTURE);
	        
	        $matches = $matches[0];
	        for($i=0; $i<count($matches); $i++)
	        {
	                $dopen = 0;
	                $dclose = 0;
	                $div_close_pos = 0;
	                $pos_divs = array();
	                preg_match_all("/<\/?div[^>]*>/is", $subject, $pos_divs, PREG_OFFSET_CAPTURE, $matches[$i][1]);
	                $pos_divs = $pos_divs[0];//parr($pos_divs);
	                for($i2=0; $i2<count($pos_divs); $i2++)
	                {
	                        if(eregi("\/", $pos_divs[$i2][0]))
	                        {
	                                $dclose++;// echo "Dclose: $dclose";
	                        }
	                        else
	                        {
	                                $dopen++;// echo " | Dopen: $dopen";
	                        }
	                        ///////////////////
	                        if($dclose > $dopen-1)
	                        {
	                                $div_close_pos = $pos_divs[$i2][1];
	                                $div_len = $div_close_pos+6 - $matches[$i][1];
	                                $divs[$i] = substr($subject, $matches[$i][1], $div_len);
	                                break;
	                        }
	                }
	        }
	        return $divs;
	}

}
?>
