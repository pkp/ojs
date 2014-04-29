/**
 * plugins/pubIds/urn/js/checkNumber.js
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Function for determining the check number for URNs
 */

/**
 * Get the last, check number.
 * Algorithm (s. http://www.persistent-identifier.de/?link=316):
 *  every URN character is replaced with a number according to the conversion table,
 *  every number is multiplied by it's position/index (beginning with 1),
 *  the numbers' sum is calculated,
 *  the sum is devided by the last number,
 *  the last number of the quotient before the decimal point is the check number.
 */
function calculateCheckNo(urnPrefix) {
	var urnSuffix = document.getElementById('urnSuffix').value
    var urn = urnPrefix+urnSuffix;
    urn = urn.toLowerCase();
    
    var conversionTable = {'9': '41', '8': '9', '7': '8', '6': '7', '5': '6', '4': '5', '3': '4', '2': '3', '1': '2', '0': '1', 'a': '18', 'b': '14', 'c': '19', 'd': '15', 'e': '16', 'f': '21', 'g': '22', 'h': '23', 'i': '24', 'j': '25', 'k': '42', 'l': '26', 'm': '27', 'n': '13', 'o': '28', 'p': '29', 'q': '31', 'r': '12', 's': '32', 't': '33', 'u': '11', 'v': '34', 'w': '35', 'x': '36', 'y': '37', 'z': '38', '-': '39', ':': '17', '_': '43', '/': '45', '.': '47', '+': '49' }; 
    
    var newURN = '';
    for (var i = 0; i < urn.length; i++) {
    	char = urn.charAt(i);
    	newURN += conversionTable[char];		
    }
    var sum = 0;
    for (var j = 1; j <= newURN.length; j++) { 
	    sum = sum + (newURN.charAt(j-1) * j);
    }     
    var lastNumber = newURN.charAt(newURN.length-1);   
    var quot = sum / lastNumber;   
    var quotRound = Math.floor(quot);   
    var quotString = quotRound.toString();   
    document.getElementById('urnSuffix').value = urnSuffix + quotString.charAt(quotString.length-1);
}
