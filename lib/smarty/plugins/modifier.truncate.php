<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty truncate modifier plugin
 *
 * Type:     modifier<br>
 * Name:     truncate<br>
 * Purpose:  Truncate a string to a certain length if necessary,
 *           optionally splitting in the middle of a word, and
 *           appending the $etc string or inserting $etc into the middle.
 * @link http://smarty.php.net/manual/en/language.modifier.truncate.php
 *          truncate (Smarty online manual)
 * @author   Monte Ohrt <monte at ohrt dot com> with modifications by Matthew Crider (mcrider at sfu dot ca)
 * @param string
 * @param integer
 * @param string
 * @param boolean
 * @param boolean
 * @param boolean
 * @return string
 */
function smarty_modifier_truncate($string, $length = 80, $etc = '...',
                                  $break_words = false, $middle = false, $skip_tags = true)
{
    if ($length == 0)
        return '';

    if (strlen($string) > $length) {
    	$originalLength = strlen($string);
		if($skip_tags) {
			if ($middle) {
				$tagsReverse = array();
				remove_tags($string, $tagsReverse, true, $length);
			}
			$tags = array();
			$string = remove_tags($string, $tags, false, $length);
		}
        $length -= min($length, strlen($etc));
        
        if (!$middle) {        	
        	if(!$break_words) {
	            $string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length+1));
    		} else $string = substr($string, 0, $length+1);
    		
    		if($skip_tags) $string = reinsert_tags($string, $tags);
    		return close_tags($string) . $etc;
        }
        else {
        	$firstHalf = substr($string, 0, $length/2);
			$secondHalf = substr($string, -$length/2);

            if($break_words) {
            	if($skip_tags) {
            		$firstHalf = reinsert_tags($firstHalf, $tags);
            		$secondHalf = reinsert_tags($secondHalf, $tagsReverse, true);
            	
            		return close_tags($firstHalf) . $etc . close_tags($secondHalf, true);
            	} else {
            		return $firstHalf . $etc . $secondHalf;
            	}
            } else {
				for($i=$length/2; $string[$i] != ' '; $i++) {
					$firstHalf = substr($string, 0, $i+1);
				}
				for($i=$length/2; substr($string, -$i, 1) != ' '; $i++) {
					$secondHalf = substr($string, -$i-1);
				}

				if($skip_tags) {
					$firstHalf = reinsert_tags($firstHalf, $tags);
					$secondHalf = reinsert_tags($secondHalf, $tagsReverse, strlen($string));
					return close_tags($firstHalf) . $etc . close_tags($secondHalf, true);  
				} else {
					return $firstHalf . $etc . $secondHalf;
				}
            }
            
        }
    } else {
        return $string;
    }
}



/**
 * Helper function: Remove XHTML tags and insert them into a global array along with their position
 * @author Matt Crider
 * @param string
 * @param array
 * @param boolean
 * @param int
 * @return string
 */
function remove_tags($string, &$tags, $reverse = false, $length) {
	if($reverse) {
		return remove_tags_aux_reverse($string, 0, &$tags, $length);
	} else return remove_tags_aux($string, 0, &$tags, $length);
}

/**
 * Helper function: Recursive function called by remove_tags
 * @author Matt Crider
 * @param string
 * @param int
 * @param array
 * @param int
 * @return string
 */
function remove_tags_aux($string, $loc, &$tags, $length) {
	if(strlen($string) > 0 && $length > 0) {
		$length--;
		if($string[0] == '<') {
			$closeBrack = strpos($string, '>')+1;
			if($closeBrack) {
				$tags[] = array(substr($string, 0, $closeBrack), $loc);
				return remove_tags_aux(substr($string, $closeBrack), $loc+$closeBrack, $tags, $length);
			}
		}
		return $string[0] . remove_tags_aux(substr($string, 1), $loc+1, $tags, $length);
	}
}

/**
 * Helper function: Recursive function called by remove_tags
 * Removes tags from the back of the string and keeps a record of their position from the back
 * @author Matt Crider
 * @param string
 * @param int loc Keeps track of position from the back of original string 
 * @param array
 * @param int
 * @return string
 */
function remove_tags_aux_reverse($string, $loc, &$tags, $length) {
	$backLoc = strlen($string)-1;
	if($backLoc >= 0 && $length > 0) {
		$length--;
		if($string[$backLoc] == '>') {
			$tag = '>';
			$openBrack = 1;
			while ($string[$backLoc-$openBrack] != '<') {
				$tag = $string[$backLoc-$openBrack] . $tag;
				$openBrack++;
			}
			$tag = '<' . $tag;
			$openBrack++;
			
			$tags[] = array($tag, $loc);
			//echo "loc: " . $loc . "\n";
			//echo "openBrack: " . $openBrack . "\n";
			return remove_tags_aux_reverse(substr($string, 0, -$openBrack), $loc+$openBrack, $tags, $length);
			
		}
		return remove_tags_aux_reverse(substr($string, 0, -1), $loc+1, $tags, $length) . $string[$backLoc];
	}
}


/**
 * Helper function: Reinsert tags from the tag array into their original position in the string
 * @author Matt Crider
 * @param string
 * @param array
 * @param boolean Set to true to reinsert tags starting at the back of the string
 * @return string
 */
function reinsert_tags($string, &$tags, $reverse = false) {
	if(empty($tags)) return $string;
	
	for($i = 0; $i < count($tags); $i++) {
		$length = strlen($string);
		if ($tags[$i][1] < strlen($string)) {
			if ($reverse) {
				if ($tags[$i][1] == 0) { // Cannot use -0 as the start index (its same as +0)
					$string = substr_replace($string, $tags[$i][0], $length, 0);
				} else {
					$string = substr_replace($string, $tags[$i][0], -$tags[$i][1], 0);
				}
			} else {
				$string = substr_replace($string, $tags[$i][0], $tags[$i][1], 0);
			}
		}
	}
	
	return $string;
}

/**
 * Helper function: Closes all dangling XHTML tags in a string
 * Modified from http://milianw.de/code-snippets/close-html-tags
 *  by Milian Wolff <mail@milianw.de> 
 * @param string
 * @return string
 */
function close_tags($string, $open = false){
	//put all opened tags into an array
	preg_match_all("#<([a-z]+)( .*)?(?!/)>#iU",$string,$result);
	$openedtags=$result[1];
	
	//put all closed tags into an array
	preg_match_all("#</([a-z]+)>#iU",$string,$result);
	$closedtags = $result[1];
	$len_opened = count($openedtags);
	$len_closed = count($closedtags);
	// all tags are closed
	if(count($closedtags) == $len_opened){
		return $string;
	}

	$openedtags = array_reverse($openedtags);
	$closedtags = array_reverse($closedtags);

	if ($open) {
		//open tags
		for($i=0; $i < $len_closed; $i++) {
			if (!in_array($closedtags[$i],$openedtags)){
				$string = '<'.$closedtags[$i].'>' . $string;
			} else {
				unset($openedtags[array_search($closedtags[$i],$openedtags)]);
			}
		}
		return $string;
	} else {
		// close tags
		for($i=0; $i < $len_opened; $i++) {
			if (!in_array($openedtags[$i],$closedtags)){
				$string .= '</'.$openedtags[$i].'>';
			} else {
				unset($closedtags[array_search($openedtags[$i],$closedtags)]);
			}
		}
		return $string;
	}
}

/* vim: set expandtab: */

?>
