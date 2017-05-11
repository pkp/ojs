/*
 * jQuery.splitter.js - animated splitter plugin
 *
 * version 1.0 (2010/01/02) 
 * 
 * Dual licensed under the MIT and GPL licenses: 
 *   http://www.opensource.org/licenses/mit-license.php 
 *   http://www.gnu.org/licenses/gpl.html 
 */

/**
 * jQuery.splitter() plugin implements a two-pane resizable animated window,
 * using existing DIV elements for layout.
 * 
 * For more details and demo of original author's code: http://krikus.com/js/splitter
 * 
 * @example $("#splitterContainer").splitter({splitVertical:true, A:$('#leftPane'), B:$('#rightPane')});
 * @desc Create a vertical splitter with toggle button
 * 
 * @name splitter
 * @type jQuery
 * @param Object options Options for the splitter (required)
 * @cat Plugins/Splitter
 * @return jQuery
 * 
 * @author Kristaps Kukurs (contact@krikus.com) - original author
 * @author jerico (jerico.dev@gmail.com) - bug fixes, formatting, documentation, removed unused code
 */

(function($) {
	$.fn.splitter = function(args) {
		args = args || {};
		return this.each(function() {
			var _ghost; // splitbar ghosted element
			var _initPos; // initial mouse position
			var _perc; // current percentage

			// Default opts
			var direction = (args.splitHorizontal ? 'h' : 'v');
			var opts = $.extend({
					minAsize : 0, // minimum width/height in PX of the first
									// (A) div.
					maxAsize : 0, // maximum width/height in PX of the first
									// (A) div.
					minBsize : 0, // minimum width/height in PX of the second
									// (B) div.
					maxBsize : 0, // maximum width/height in PX of the second
									// (B) div.
					ghostClass : 'working',// class name for _ghosted splitter
											// and hovered button
					invertClass : 'invert',// class name for invert splitter
											// button
					animSpeed : 100 // animation speed in ms
				},
				{
					v : { // Vertical
						moving : "left",
						sizing : "width",
						eventPos : "pageX",
						splitbarClass : "splitbarV",
						buttonClass : "splitbuttonV",
						cursor : "e-resize"
					},
					h : { // Horizontal
						moving : "top",
						sizing : "height",
						eventPos : "pageY",
						splitbarClass : "splitbarH",
						buttonClass : "splitbuttonH",
						cursor : "n-resize"
					}
				}[direction],
				args
			);

			// Setup elements
			var splitter = $(this);
			var mychilds = $(">*", splitter[0]);
			var A = args.A; // left/top frame
			var B = args.B; // right/bottom frame
			
			// Reduce the splitter to an integer size to avoid
			// float problems with a non-integer width property.
			splitter.css(opts.sizing, Math.floor(splitter[opts.sizing]())-1); 
			
			// Create splitbar
			var C = $('<div><span></span></div>');
			A.after(C);
			C.attr({
				"class" : opts.splitbarClass,
				unselectable : "on"
			}).css({
				"cursor" : opts.cursor,
				"user-select" : "none",
				"-webkit-user-select" : "none",
				"-khtml-user-select" : "none",
				"-moz-user-select" : "none"
			}).bind("mousedown", startDrag);

			// Set initial size.
			var perc = ((C.position()[opts.moving] / splitter[opts.sizing]()) * 100).toFixed(1);
			splitTo(perc);
			
			/**
			 * Event handler: C.onmousedown=startDrag
			 * @param e Event
			 */
			function startDrag(e) {
				if (e.target != this)
					return;
				_ghost = _ghost || C.clone(false).insertAfter(A);
				splitter._initPos = C.position();
				splitter._initPos[opts.moving] -= C[opts.sizing]();
				_ghost.addClass(opts.ghostClass)
						.css('position', 'absolute').css('z-index', '250')
						.css("-webkit-user-select", "none")
						.width(C.width()).height(C.height())
						.css(opts.moving, splitter._initPos[opts.moving]);
				// Safari selects A/B text on a move
				mychilds.css("-webkit-user-select", "none");
				A._posSplit = e[opts.eventPos];

				$(document).bind("mousemove", performDrag).bind("mouseup",
						endDrag);
			}
			
			/**
			 * Event handler: document.onmousemove=performDrag
			 * @param e Event
			 */
			function performDrag(e) {
				if (!_ghost || !A)
					return;
				var incr = e[opts.eventPos] - A._posSplit;
				_ghost.css(opts.moving, splitter._initPos[opts.moving]
						+ incr);
			}
			
			/**
			 * Event handler: C.onmouseup=endDrag
			 * @param e Event
			 */
			function endDrag(e) {
				var p = _ghost.position();
				_ghost.remove();
				_ghost = null;
				
				// Let Safari select text again
				mychilds.css("-webkit-user-select", "text");
				$(document).unbind("mousemove", performDrag).unbind("mouseup", endDrag);
				var perc = ((p[opts.moving] / splitter[opts.sizing]()) * 100).toFixed(1);
				splitTo(perc);
				splitter._initPos = 0;
			}

			/**
			 * Actual splitting.
			 * @param perc float the split percentage
			 */
			function splitTo(perc) {
				if (perc == undefined) return; // Fixes MSIE problem
				_perc = perc;

				var barsize = C[opts.sizing]()
						+ (2 * parseInt(C.css('border-' + opts.moving + '-width')));
				var splitsize = splitter[opts.sizing]();

				var percpx = Math.max(
						parseInt((splitsize / 100) * perc),
						opts.minAsize);
				
				if (opts.maxAsize)
					percpx = Math.min(percpx, opts.maxAsize);

				if (opts.maxBsize) {
					if ((splitsize - percpx) > opts.maxBsize)
						percpx = splitsize - opts.maxBsize;
				}

				if (opts.minBsize) {
					if ((splitsize - percpx) < opts.minBsize)
						percpx = splitsize - opts.minBsize;
				}

				var sizeA = Math.max(0, (percpx - barsize - (2 * parseInt(A.css('border-' + opts.moving + '-width')))));
				var sizeB = Math.max(0, (splitsize - percpx - (2 * parseInt(B.css('border-' + opts.moving + '-width')))));

				A.css(opts.sizing, sizeA + 'px');
				B.css(opts.sizing, sizeB + 'px');
			}
			
			// Custom resize event to be triggered from outside.
			splitter.bind('splitterRecalc', function() {
				// Resize to the same percentage as before.
				splitter.css(opts.sizing, Math.floor(splitter[opts.sizing]())-1);
				splitTo(_perc);
			});
		});
	};
})(jQuery);