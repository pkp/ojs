/**
 * @defgroup plugins_pubIds_urn_js
 */
/**
 * @file plugins/pubIds/urn/js/checkNumber.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Function for determining and adding the check number for URNs
 */
(function($) {

	/**
	 * Add method to the pkp namespace
	 */
	$.pkp.plugins.generic.urn = {

		/**
		 * Get the last, check number.
		 * Algorithm (s. http://www.persistent-identifier.de/?link=316):
		 *  every URN character is replaced with a number
		 *  according to the conversion table,
		 *  every number is multiplied by
		 *  it's position/index (beginning with 1),
		 *  the numbers' sum is calculated,
		 *  the sum is divided by the last number,
		 *  the last number of the quotient
		 *  before the decimal point is the check number.
		 *
		 * @param {string} urn
		 * @param {string} urnPrefix
		 */
		getCheckNumber: function(urn, urnPrefix) {
			var newURN = '',
					conversionTable = {
						'9': '41', '8': '9', '7': '8', '6': '7',
						'5': '6', '4': '5', '3': '4', '2': '3',
						'1': '2', '0': '1', 'a': '18', 'b': '14',
						'c': '19', 'd': '15', 'e': '16', 'f': '21',
						'g': '22', 'h': '23', 'i': '24', 'j': '25',
						'k': '42', 'l': '26', 'm': '27', 'n': '13',
						'o': '28', 'p': '29', 'q': '31', 'r': '12',
						's': '32', 't': '33', 'u': '11', 'v': '34',
						'w': '35', 'x': '36', 'y': '37', 'z': '38',
						'-': '39', ':': '17', '_': '43', '/': '45',
						'.': '47', '+': '49'
					},
					i, j, char, sum, lastNumber, quot, quotRound, quotString, newSuffix;

			suffix = urn.replace(urnPrefix, '').toLowerCase();
			for (i = 0; i < suffix.length; i++) {
				char = suffix.charAt(i);
				newURN += conversionTable[char];
			}
			sum = 0;
			for (j = 1; j <= newURN.length; j++) {
				sum = sum + (newURN.charAt(j - 1) * j);
			}
			lastNumber = newURN.charAt(newURN.length - 1);
			quot = sum / lastNumber;
			quotRound = Math.floor(quot);
			quotString = quotRound.toString();
			return parseInt(quotString.charAt(quotString.length - 1));
		}
	};

	// Apply the check number when the button is clicked
	$('#checkNo').click(function() {
		var urnPrefix = $('[id^="urnPrefix"]').val(),
				urnSuffix = $('[id^="urnSuffix"]').val();
		urn = urnPrefix + urnSuffix;
		$('[id^="urnSuffix"]').val(urnSuffix + $.pkp.plugins.generic.urn.getCheckNumber(urn, urnPrefix));
	});

}(jQuery));
