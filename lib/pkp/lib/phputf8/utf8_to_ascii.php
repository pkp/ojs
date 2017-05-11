<?php
/**
* US-ASCII transliterations of Unicode text
* @version $Id$
* @package utf8_to_ascii
*/

if ( !defined('UTF8_TO_ASCII_DB') ) {
    define('UTF8_TO_ASCII_DB',dirname(__FILE__).'/db');
}

//--------------------------------------------------------------------
/**
* US-ASCII transliterations of Unicode text
* Ported Sean M. Burke's Text::Unidecode Perl module (He did all the hard work!)
* Warning: you should only pass this well formed UTF-8!
* Be aware it works by making a copy of the input string which it appends transliterated
* characters to - it uses a PHP output buffer to do this - it means, memory use will increase,
* requiring up to the same amount again as the input string
* @see http://search.cpan.org/~sburke/Text-Unidecode-0.04/lib/Text/Unidecode.pm
* @param string UTF-8 string to convert
* @param string (default = ?) Character use if character unknown
* @return string US-ASCII string
* @package utf8_to_ascii
*/
function utf8_to_ascii($str, $unknown = '?') {
    
    # The database for transliteration stored here
    static $UTF8_TO_ASCII = array();
    
    # Variable lookups faster than accessing constants
    $UTF8_TO_ASCII_DB = UTF8_TO_ASCII_DB;
    
    if ( strlen($str) == 0 ) { return ''; }
    
    $len = strlen($str);
    $i = 0;
    
    # Use an output buffer to copy the transliterated string
    # This is done for performance vs. string concatenation - on my system, drops
    # the average request time for the example from ~0.46ms to 0.41ms
    # See http://phplens.com/lens/php-book/optimizing-debugging-php.php
    # Section  "High Return Code Optimizations"
    ob_start();
    
    while ( $i < $len ) {
        
        $ord = NULL;
        $increment = 1;
        
        $ord0 = ord($str{$i});
        
        # Much nested if /else - PHP fn calls expensive, no block scope...
        
        # 1 byte - ASCII
        if ( $ord0 >= 0 && $ord0 <= 127 ) {
            
            $ord = $ord0;
            $increment = 1;
            
        } else {
            
            # 2 bytes
            $ord1 = ord($str{$i+1});
            
            if ( $ord0 >= 192 && $ord0 <= 223 ) {
                
                $ord = ( $ord0 - 192 ) * 64 + ( $ord1 - 128 );
                $increment = 2;
                
            } else {
                
                # 3 bytes
                $ord2 = ord($str{$i+2});
                
                if ( $ord0 >= 224 && $ord0 <= 239 ) {
                    
                    $ord = ($ord0-224)*4096 + ($ord1-128)*64 + ($ord2-128);
                    $increment = 3;
                    
                } else {
                    
                    # 4 bytes
                    $ord3 = ord($str{$i+3});
                    
                    if ($ord0>=240 && $ord0<=247) {
                        
                        $ord = ($ord0-240)*262144 + ($ord1-128)*4096 
                            + ($ord2-128)*64 + ($ord3-128);
                        $increment = 4;
                        
                    } else {
                        
                        ob_end_clean();
                        trigger_error("utf8_to_ascii: looks like badly formed UTF-8 at byte $i");
                        return FALSE;
                        
                    }
                    
                }
                
            }
            
        }
        
        $bank = $ord >> 8;
        
        # If we haven't used anything from this bank before, need to load it...
        if ( !array_key_exists($bank, $UTF8_TO_ASCII) ) {
            
            $bankfile = UTF8_TO_ASCII_DB. '/'. sprintf("x%02x",$bank).'.php';
            
            if ( file_exists($bankfile) ) {
                
                # Load the appropriate database
                if ( !include  $bankfile ) {
                    ob_end_clean();
                    trigger_error("utf8_to_ascii: unable to load $bankfile");
                }
                
            } else {
                
                # Some banks are deliberately empty
                $UTF8_TO_ASCII[$bank] = array();
                
            }
        }
        
        $newchar = $ord & 255;
        
        if ( array_key_exists($newchar, $UTF8_TO_ASCII[$bank]) ) {
            echo $UTF8_TO_ASCII[$bank][$newchar];
        } else {
            echo $unknown;
        }
        
        $i += $increment;
        
    }
    
    $str = ob_get_contents();
    ob_end_clean();
    return $str;
    
}
