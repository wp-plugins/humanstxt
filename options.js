jQuery(document).ready(function($) {

	// open links in new tab
	$('#humanstxt a.external, #humanstxt-metabox .text-rateit a, #contextual-help-wrap a').attr('target', '_tab');

	// make star rating clickable
	var humanstxt_rateit_text = $('#humanstxt-metabox .text-rateit a').attr('title');
	$('#humanstxt-metabox .star-holder, #humanstxt-metabox .text-votes').css('cursor', 'pointer').attr('title', humanstxt_rateit_text).click(function() {
		window.location.href = 'http://wordpress.org/extend/plugins/humanstxt/';
	})

	// humans.txt textarea auto-grow and tab key support
	$humanstxt_textarea = $('#humanstxt_content').attr('rows', $(this).val().split("\n").length + 2).autoGrow();

	// taken from /wp-admin/js/common.dev.js
	$humanstxt_textarea.keydown(function(e) {
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

	$humanstxt_textarea.blur(function(e) {
		if ( this.lastKey && 9 == this.lastKey )
			this.focus();
	});

});

/*!
 * Autogrow Textarea Plugin Version v2.0
 * http://www.technoreply.com/autogrow-textarea-plugin-version-2-0
 *
 * Copyright 2011, Jevin O. Sewaruth
 *
 * Date: March 13, 2011
 */
jQuery.fn.autoGrow = function(){
	return this.each(function(){
		// Variables
		var colsDefault = this.cols;
		var rowsDefault = this.rows;
		
		//Functions
		var grow = function() {
			growByRef(this);
		}
		
		var growByRef = function(obj) {
			var linesCount = 0;
			var lines = obj.value.split('\n');
			
			for (var i=lines.length-1; i>=0; --i)
			{
				linesCount += Math.floor((lines[i].length / colsDefault) + 1);
			}

			if (linesCount >= rowsDefault)
				obj.rows = linesCount + 1;
			else
				obj.rows = rowsDefault;
		}
		
		var characterWidth = function (obj){
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
		
		// Manipulations
		this.style.width = "auto";
		this.style.height = "auto";
		this.style.overflow = "hidden";
		this.style.width = ((characterWidth(this) * this.cols) + 6) + "px";
		this.onkeyup = grow;
		this.onfocus = grow;
		this.onblur = grow;
		growByRef(this);
	});
};