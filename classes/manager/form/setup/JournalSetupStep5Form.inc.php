<?php

/**
 * JournalSetupStep5Form.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 *
 * Form for Step 5 of journal setup.
 *
 * $Id$
 */

import("manager.form.setup.JournalSetupForm");

class JournalSetupStep5Form extends JournalSetupForm {
	
	function JournalSetupStep5Form() {
		parent::JournalSetupForm(
			5,
			array(
				'headerTitleType' => 'int',
				'journalHeaderTitle' => 'string',
				'journalHeaderTitleImage' => 'string',
				'navItems' => 'object'
				
			)
		);
	}
	
	//update locale.xml with Navigation Bar Items
	function execute() {
	 	$locale = Locale::getLocale();
	 	$localeFile = "locale/$locale/locale.xml";
		$file = file_get_contents($localeFile);
		$navItems =  $this->getData('navItems');
		
		//first delete all navigation.bar.* lines form locale.xml
		if (is_writable($localeFile)) {
			$file = String::regexp_replace($pattern = "@\t<message key=\"navigation\.bar\..*\">.*</message>\n@",
										   null, 
										   $file,
										   -1);
											  
			//then update navigation.bar.key=>key in <!-- Navigation --> section
			for($i=0; $i<count($navItems)-1; $i++) {
				$key = trim($navItems["$i"]['name']);
				$file = String::regexp_replace("@<!-- Navigation -->\n@",
										       "<!-- Navigation -->\n\t<message key=\"navigation.bar.{$key}\">{$key}</message>\n",
										       $file,
										       1);
			}
		
			//update locale.xml
			fwrite($res=fopen($localeFile, 'w'), $file);
			fclose($res);
			Locale::loadLocale();
			parent::execute();
		}
	}
}

?>