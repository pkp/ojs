<?php

/**
 * @file ContentManager.inc.php
 *
 * Copyright (c) 2006-2007 Gunther Eysenbach, Juan Pablo Alperin, MJ Suhonos
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContentManager
 * @ingroup plugins_generic_cms
 *
 * @brief Form for journal managers to modify Cms Plugin content
 * 
 */

import('file.JournalFileManager');

define('CONTENT_FILE_HEADER', '<html xmlns="http://www.w3.org/1999/xhtml"><body>');
define('CONTENT_FILE_FOOTER', '</body></html>');

class ContentManager {

	/* the contents file being used */
	var $filePath;

	/* the contents array */
	var $fileContent;

	/* the default (first element) */
	var $defaultHeading;

	/*
	 * Constructor
	 */
	function ContentManager() {
		$journal = &Request::getJournal();
		$journalFileManager =& new JournalFileManager($journal);

		// try to current locale
		$lang = Locale::getLocale();
		$this->filePath = $journalFileManager->filesDir.'content/'.$lang.'/content.xhtml';

		$this->loadContents();
	} 

	function loadContents() {
		$journal = &Request::getJournal();
		$journalFileManager =& new JournalFileManager($journal);

		$filePath = $this->filePath;
		// if that doesn't work, use the journal's primary locale 
		// and if that is not defined, then use use en_US		
		if ( !$journalFileManager->fileExists($this->filePath) ) {
			if ( $journal->getPrimaryLocale() ) 
				$lang = $journal->getPrimaryLocale();
			else
				$lang = 'en_US';
				
			$filePath = $journalFileManager->filesDir.'content/'.$lang.'/content.xhtml';
		}

		// get the current file contents into memory and strip out
		// characters for easier regex'ing
		$this->fileContent = $journalFileManager->readFile($filePath );		
		$this->fileContent = preg_replace("/(\r\n|\r|\n)+/","",$this->fileContent);

		// grab the first heading so we can default to it
		preg_match('/<h([1-3])>(.*)<\/h[1-3]>/U', $this->fileContent, $matches);
		if ( count($matches) > 0 )
			$this->defaultHeading = array( $matches[1], $this->webSafe($matches[2]), $matches[2]);
		else
			$this->defaultHeading = array( 0, null, null );
	}

	/* write new content to the file */
	function saveContents( &$content ) {
		$journal = &Request::getJournal();
		$journalFileManager =& new JournalFileManager($journal);

		$content = CONTENT_FILE_HEADER.$content.CONTENT_FILE_FOOTER;

		$journalFileManager->writeFile($this->filePath, $content );	

		$this->loadContents();		
	}

	/*
	 * Parses the entire file to create the table of contents open at $current
	 * modifies $headings and $content to hold arrays of equal length
	 * $headings array of ( heading level, websafe name, display name )
	 * $content array indexed by websafe name of ( content )
	 */
	function parseContents ( &$headings, &$content, $current = null ) {
		$fileContent = $this->fileContent;

		preg_match_all('/<h([1-3])>(.*)<\/h[1-3]>/U', $fileContent, $headMatches);
		preg_match_all('/<\/h([1-3])>(.*)<(h[1-3]|\/body)>/U', $fileContent, $contentMatches);

		$i = -1;
		$count = count($headMatches[2]);
		$current = explode(":", $current);		

		while ( $i + 1 < $count ) {
			$i++;			

			// only consider 1st level headings until we have a match for where to open
			if ( $headMatches[1][$i] == 1 ) {
				// insert 1st level heading
				$webSafe = $this->websafe($headMatches[2][$i]);
				$headings[$i] = array(1, $webSafe, $headMatches[2][$i]);
				$content[$webSafe] = $contentMatches[2][$i];

				// if we are going to open at this heading, look for subheadings
				if ( $webSafe == $current[0] ) {
					while ( $i + 1 < $count && $headMatches[1][$i + 1] > 1 ) {
						$i++;

						// insert all 2nd level headings
						if ( $headMatches[1][$i] == 2 ) {
							// insert a 2nd level heading
							$webSafe = $this->websafe($headMatches[2][$i]);
							$headings[$i] = array(2, $current[0].":".$webSafe, $headMatches[2][$i]);
							$content[$current[0].":".$webSafe] = $contentMatches[2][$i];

							// if we are open at this heading, look for subheadings
							if ( count($current) >= 2 && $webSafe == $current[1] ) {
								// and insert all 3rd level headings
								while ( $i + 1 < $count  && $headMatches[1][$i+1] > 2 ) {
									$i++;
									// insert 3rd level heading
									$webSafe = $this->websafe($headMatches[2][$i]);
									$headings[$i] = array(3, $current[0].":".$current[1].":".$webSafe, $headMatches[2][$i]);
									$content[$current[0].":".$current[1].":".$webSafe] = $contentMatches[2][$i];
								}
							} 
						}
					}
				}
			} 
		}	

		return true; 
	}

	/*
	 * Takes current content, finds the appropriate place in the file and inserts it
	 * changes are written to disk
	 * $content string - the content that has to be written
	 * $strCurrent 	- the websafe name of the current heading level ( : delimited heading levels)
	 * 				- this is modified since the current heading may change once something is inserted
	 */
	function insertContent ($content, &$strCurrent) {
		// default to the first heading
		if ( $strCurrent == '' ) {
			$strCurrent = $this->defaultHeading[1];
		}

		$fileContent = $this->fileContent;

		// parse
		preg_match_all('/<h([1-3])>(.*)<\/h[1-3]>/U', $fileContent, $headFileMatches);
		preg_match_all('/<\/h([1-3])>(.*)<(h[1-3]|\/body)>/U', $fileContent, $contentFileMatches);

		preg_match_all('/<h([1-3])>(.*)<\/h[1-3]>/U', $content, $headCurrentMatches);
		preg_match_all('/<\/h([1-3])>(.*)<(h[1-3]|\/body)>/U', $content."</body>", $contentCurrentMatches);

		/* 
		 * add all the websafe versions of the headers (for duplicate matching) 
		 * grab the greatest uniquifying number used so we can use that in future 
		 */
		$uniquifier = 0;
		for ( $i = 0; $i < count( $headFileMatches[2] ); $i++ ) {
			$headFileMatches[3][$i] = $this->webSafe($headFileMatches[2][$i]);
			preg_match('/.*_duplicate_([0-9]+)$/', $headFileMatches[3][$i], $dup);
			if ( count($dup) > 0 && (int) $dup[1] > $uniquifier )
				$uniquifier = (int) $dup[1];
		}
		for ( $i = 0; $i < count( $headCurrentMatches[2] ); $i++ ) {
			$headCurrentMatches[3][$i] = $this->webSafe($headCurrentMatches[2][$i]);
			preg_match('/.*_duplicate_([0-9]+)$/', $headCurrentMatches[3][$i], $dup);
			if ( count($dup) > 0 && (int) $dup[1] > $uniquifier )
				$uniquifier = (int) $dup[1];
		}
		$uniquifier++;

		$i = -1;
		$toWrite = "";

		// how many headings were present before insert
		$count = count($headFileMatches[2]);
		// an array of the heading levels up to the current
		$current = explode(":", $strCurrent);

		/* these two variables will serve as aides for ensure unique headings */
		$addedHeadings = array(1 => array(), 2 => array(), 3 => array() );
		$prev = 1;

		/* case when the file was empty
		 * must make sure we start with an h1 tag and if not, change heading levels where appropriate
		 * 
		 * (form validator can't check this, since it has no knowledge of the file contents,
		 * only the window contents
		 */
		if ( $count == 0 ) {
			/* first heading must be an <h1> heading */
			if ( $headCurrentMatches[1][0] != 1 ) {
				$headCurrentMatches[1][0] = 1;				
			}			

			for ( $i = 0; $i < count($headCurrentMatches[0]); $i++ ) {
				/* cannot go from an H1 to an H3 */
				if ( $headCurrentMatches[1][$i] > $prev + 1 )
					$headCurrentMatches[1][$i] = $prev + 1;

				/* prepare $prev for next loop */
				$prev = $headCurrentMatches[1][$i];

				/* remove duplicates - uniquify */
				if ( in_array($headCurrentMatches[3][$i], $addedHeadings[$headCurrentMatches[1][$i]]) ) {
					$headCurrentMatches[2][$i] = preg_replace('/^(.*)(_duplicate_[0-9]+)?$/', '$0_duplicate_'.$uniquifier,$headCurrentMatches[2][$i]);
					$headCurrentMatches[3][$i] = preg_replace('/^(.*)(_duplicate_[0-9]+)?$/', '$0_duplicate_'.$uniquifier,  $headCurrentMatches[3][$i]);
					$uniquifier++;

				}

				$toWrite .= "\n<h".$headCurrentMatches[1][$i].">".$headCurrentMatches[2][$i]."</h".$headCurrentMatches[1][$i].">\n";
				$toWrite .= $contentCurrentMatches[2][$i]."\n";
				/* keep track of added headings to ensure no duplicates */
				array_push($addedHeadings[$headCurrentMatches[1][$i]], $headCurrentMatches[3][$i]);
				if ( $headCurrentMatches[1][$i] < 3 )  
					$addedHeadings[3] = array();
				if ( $headCurrentMatches[1][$i] < 2 )  
					$addedHeadings[2] = array();

			}			 

		}

		/* 
		 * Case where going to append to an already existing file
		 */
		while ( $i + 1 < $count ) {
			$i++;	

			/* 
			 * if this is the place of the insert
			 * same heading name (websafe version) && the right heading level [1-3]
			 */
			if ( $this->webSafe($headFileMatches[2][$i]) == $current[count($current) - 1] 
				&& $headFileMatches[1][$i] == count($current) ) {
				// matched the heading name and level -- insert the first heading in the current content
				if ( count( $headCurrentMatches[0]) > 0 ) {
					/* remove duplicates - uniquify */
					if ( in_array($headCurrentMatches[3][0], $addedHeadings[$headCurrentMatches[1][0]]) ) {
						$headCurrentMatches[2][0] = preg_replace('/^(.*)(_duplicate_[0-9]+)?$/', '$0_duplicate_'.$uniquifier,$headCurrentMatches[2][0]);
						$headCurrentMatches[3][0] = preg_replace('/^(.*)(_duplicate_[0-9]+)?$/', '$0_duplicate_'.$uniquifier,$headCurrentMatches[3][0]);
						$uniquifier++;
					}					
					$toWrite .= "\n<h".$headCurrentMatches[1][0].">".$headCurrentMatches[2][0]."</h".$headCurrentMatches[1][0].">\n";
					$toWrite .= $contentCurrentMatches[2][0]."\n";
					array_push($addedHeadings[$headCurrentMatches[1][0]], $headCurrentMatches[3][0]);
					if ( $headCurrentMatches[1][0] < 3 )
						$addedHeadings[3] = array();
					if ( $headCurrentMatches[1][0] < 2 )
						$addedHeadings[2] = array();						

					/* now we have to write all the previously existing content that are 
					 * of a lower heading level (e.g. if we are inserting an H1 tag after another H1 tag
					 * then we need to write out all the H2 and H3 tags before we put in the next H1 tag )
					 */
					while ( $i + 1 < $count && count($headCurrentMatches[1]) > 1 && $headFileMatches[1][$i + 1] > $headCurrentMatches[1][1] ) {
						$i++;

						/* remove duplicates - uniquify */
						if ( in_array($headFileMatches[3][$i], $addedHeadings[$headFileMatches[1][$i]]) ) {
							$headFileMatches[2][$i] = preg_replace('/^(.*)(_duplicate_[0-9]+)?$/', '$0_duplicate_'.$uniquifier,$headFileMatches[2][$i]);
							$headFileMatches[3][$i] = preg_replace('/^(.*)(_duplicate_[0-9]+)?$/', '$0_duplicate_'.$uniquifier,$headFileMatches[3][$i]);
								$uniquifier++;
						}				
						$toWrite .= "\n<h".$headFileMatches[1][$i].">".$headFileMatches[2][$i]."</h".$headFileMatches[1][$i].">\n";
						$toWrite .= $contentFileMatches[2][$i]."\n";
						array_push($addedHeadings[$headFileMatches[1][$i]], $headFileMatches[3][$i]);
						if ( $headFileMatches[1][$i] < 3 )
							$addedHeadings[3] = array();
						if ( $headFileMatches[1][$i] < 2 )
							$addedHeadings[2] = array();
					}

					// and now just put the rest of the new content
					for ( $j = 1; $j < count($headCurrentMatches[0]); $j++ ) {
						/* remove duplicates - uniquify */
						if ( in_array($headCurrentMatches[3][$j], $addedHeadings[$headCurrentMatches[1][$j]]) ) {
							$headCurrentMatches[2][$j] = preg_replace('/^(.*)(_duplicate_[0-9]+)?$/', '$1_duplicate_'.$uniquifier,$headCurrentMatches[2][$j]);
							$headCurrentMatches[3][$j] = preg_replace('/^(.*)(_duplicate_[0-9]+)?$/', '$1_duplicate_'.$uniquifier,$headCurrentMatches[3][$j]);							
								$uniquifier++;
						}		
						$toWrite .= "\n<h".$headCurrentMatches[1][$j].">".$headCurrentMatches[2][$j]."</h".$headCurrentMatches[1][$j].">\n";
						$toWrite .= $contentCurrentMatches[2][$j]."\n";
						array_push($addedHeadings[$headCurrentMatches[1][$j]], $headCurrentMatches[3][$j]);
						if ( $headCurrentMatches[1][$j] < 3 )
							$addedHeadings[3] = array();
						if ( $headCurrentMatches[1][$j] < 2 )
							$addedHeadings[2] = array();						

					}
				}
			} else {
				/* else just go through and insert everythin as is */
				/* remove duplicates - uniquify */
				if ( in_array($headFileMatches[3][$i], $addedHeadings[$headFileMatches[1][$i]]) ) {
					$headFileMatches[2][$i] = preg_replace('/^(.*)(_duplicate_[0-9]+)?$/', '$0_duplicate_'.$uniquifier,$headFileMatches[2][$i]);
					$headFileMatches[3][$i] = preg_replace('/^(.*)(_duplicate_[0-9]+)?$/', '$0_duplicate_'.$uniquifier,$headFileMatches[3][$i]);
					$uniquifier++;
				}			

				// write all the content as is
				$toWrite .= "\n<h".$headFileMatches[1][$i].">".$headFileMatches[2][$i]."</h".$headFileMatches[1][$i].">\n";
				$toWrite .= $contentFileMatches[2][$i]."\n";
				array_push($addedHeadings[$headFileMatches[1][$i]], $headFileMatches[3][$i]);
				if ( $headFileMatches[1][$i] < 3 )
					$addedHeadings[3] = array();
				if ( $headFileMatches[1][$i] < 2 )
					$addedHeadings[2] = array();						


			}
		}		

		/* this mess of code here is to handle changes in the current heading name
		 * be it on rename or on delete. 
		 * $current has the array index so we can't get rid of it, but we need to change
		 * $strCurrent in order to let the calling page know what will be the array index
		 * on the next page call
		 */
		if ( count( $headCurrentMatches[0]) > 0 ) {
			// the heading is renamed
			$newCurrent = explode(":", $strCurrent);
			$newCurrent[count($newCurrent) - 1] = $this->webSafe($headCurrentMatches[2][0]);
			$strCurrent = implode(":", $newCurrent);
		} else {
			// the heading is deleted
			$newCurrent = explode(":", $strCurrent);
			unset($newCurrent[count($newCurrent) - 1]);
			$strCurrent = implode(":", $newCurrent);
		}

		$this->saveContents( $toWrite );	


		return true;
	}

	/* strip out spaces and semi colones in the name */
	function websafe ( $str ) {
		return urlencode(strtolower(str_replace(' ', '_', str_replace(':', '', $str))));
	}

	function cleanurl ( $str ) {
		return str_replace("%3A", ":", urlencode(strtolower(str_replace(' ', '_', $str))));
	}

}

?>
