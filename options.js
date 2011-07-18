jQuery(document).ready(function($) {

	// open external links in new tab
	$('#wpbody-content a[rel*="external"]').attr('target', '_tab');

	// register custom tooltips for variable previews
	$('#humanstxt-vars li.has-result').humansTooltip();

	// make star rating clickable if the metabox is displayed
	var $humanstxtRateIt = $('#humanstxt-metabox .text-rateit a');
	if ($humanstxtRateIt.length) {
		$('#humanstxt-metabox .star-holder, #humanstxt-metabox .text-votes').css('cursor', 'pointer').attr('title', $humanstxtRateIt.attr('title')).click(function() {
			window.location.href = $humanstxtRateIt.attr('href');
		});
	}

	// humans.txt textarea auto-grow and tab key support
	var $humanstxtEditor = $('#humanstxt_content');
	var humanstxtEditorHeight = $humanstxtEditor.val().split("\n").length + 2;
	var humanstxtEditorMinHeight = $humanstxtEditor.attr('rows');

	if (humanstxtEditorHeight > humanstxtEditorMinHeight) {
		$humanstxtEditor.attr('rows', humanstxtEditorHeight);
	}

	$humanstxtEditor.humansAutoGrow();

	// taken from /wp-admin/js/common.dev.js
	$humanstxtEditor.keydown(function(e) {
		if ( e.keyCode != 9 )
			return true;

		var el = e.target, selStart = el.selectionStart, selEnd = el.selectionEnd, val = el.value, scroll, sel;

		try {
			this.lastKey = 9;
		} catch(err) {}

		if ( document.selection ) {
			el.focus();
			sel = document.selection.createRange();
			sel.text = '\t';
		} else if ( selStart >= 0 ) {
			scroll = this.scrollTop;
			el.value = val.substring(0, selStart).concat('\t', val.substring(selEnd) );
			el.selectionStart = el.selectionEnd = selStart + 1;
			this.scrollTop = scroll;
		}

		if ( e.stopPropagation )
			e.stopPropagation();
		if ( e.preventDefault )
			e.preventDefault();
	});

	$humanstxtEditor.blur(function(e) {
		if ( this.lastKey && 9 == this.lastKey )
			this.focus();
	});

});

(function($) {

	$.fn.humansTooltip = function() {

		// add tooltip div
		$humanstxtTooltip = $('#humansTooltip');		
		if ($humanstxtTooltip.length < 1) {
			$humanstxtTooltip = $('<div id="humansTooltip"></div>').appendTo('body');
		}

		return this.each(function() {

			var $element = jQuery(this);
			var elementTitle = this.title;

			this.title = ""; // prevent default browser tooltip

			$element.hover(
				function() {
					humanstxtTooltipInterval = setInterval(function() {
						clearInterval(humanstxtTooltipInterval);
						showTooltip();
					}, 200);
				},
				function() {
					clearInterval(humanstxtTooltipInterval);
					$humanstxtTooltip.fadeOut(150);
				}
			);

			var showTooltip = function() {
				$humanstxtTooltip.html(elementTitle); // set tooltip to original title attribute
				var elementOffset = $element.offset();
				var tooltipHeight = $humanstxtTooltip.height();				
				$humanstxtTooltip.css({
					top: (elementOffset.top - tooltipHeight - 15) + 'px',
					left: (elementOffset.left - 15) + 'px'
				}).fadeIn(200);
			}			

		});

	}

	/**
	 * Autogrow Textarea Plugin Version v2.0
	 * http://www.technoreply.com/autogrow-textarea-plugin-version-2-0
	 *
	 * Copyright 2011, Jevin O. Sewaruth
	 *
	 * Date: March 13, 2011
	 */
	$.fn.humansAutoGrow = function() {
		return this.each(function() {

			var colsDefault = this.cols;
			var rowsDefault = this.rows;

			var grow = function() {
				growByRef(this);
			}

			var growByRef = function(obj) {
				var linesCount = 0;
				var lines = obj.value.split('\n');

				for (var i=lines.length-1; i>=0; --i) {
					linesCount += Math.floor((lines[i].length / colsDefault) + 1);
				}

				if (linesCount >= rowsDefault)
					obj.rows = linesCount + 1;
				else
					obj.rows = rowsDefault;
			}

			var characterWidth = function (obj) {
				var characterWidth = 0;
				var temp1 = 0;
				var temp2 = 0;
				var tempCols = obj.cols;

				obj.cols = 1;
				temp1 = obj.offsetWidth;
				obj.cols = 2;
				temp2 = obj.offsetWidth;
				characterWidth = temp2 - temp1;
				obj.cols = tempCols;

				return characterWidth;
			}

			this.style.width = "auto";
			this.style.height = "auto";
			this.style.overflow = "hidden";
			this.style.width = ((characterWidth(this) * this.cols) + 6) + "px";
			this.onkeyup = grow;
			this.onkeypress = grow;
			this.onfocus = grow;
			this.onblur = grow;
			growByRef(this);
		});
	};

})(jQuery);