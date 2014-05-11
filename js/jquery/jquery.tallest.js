/**
 *	Tallest.
 *	Given a jQuery result set, this set of functions will return the:
 *	- tallest()		(biggest height)
 *	- shortest()	(smallest height)
 *	- widest()		(biggest width)
 *	- thinnest()	(smallest width)
 *	Add "Size" onto the end of those functions (eg: "tallestSize()") and it will
 *	return just the pixel size, not the element.
 *
 *	@author	nickf
 *	@date	2009-08-19
 *	@version 1.0 $Id: jquery.tallest.js 100 2009-08-19 00:40:09Z spadgos $
 */
jQuery(function($) {

	$.fn.tallest = function()       { return this._extremities({ 'aspect' : 'height', 'max' : true  })[0] };
	$.fn.tallestSize = function()   { return this._extremities({ 'aspect' : 'height', 'max' : true  })[1] };
	$.fn.shortest = function()      { return this._extremities({ 'aspect' : 'height', 'max' : false })[0] };
	$.fn.shortestSize = function()  { return this._extremities({ 'aspect' : 'height', 'max' : false })[1] };
	$.fn.widest = function()        { return this._extremities({ 'aspect' : 'width',  'max' : true  })[0] };
	$.fn.widestSize = function()    { return this._extremities({ 'aspect' : 'width',  'max' : true  })[1] };
	$.fn.thinnest = function()      { return this._extremities({ 'aspect' : 'width',  'max' : false })[0] };
	$.fn.thinnestSize = function()  { return this._extremities({ 'aspect' : 'width',  'max' : false })[1] };

	/**
	 *	Returns an array: the first item is the matched element, and the second item is the dimension
	 */
	$.fn._extremities = function(options) {
		var defaults = {
			aspect : 'height', // or 'width'
			max : true	// or false to find the min
		};
		options = $.extend(defaults, options);

		if (this.length < 2) {
			return [this, this[options.aspect]()];
		}

		var bestIndex = 0,
			bestSize = this.eq(0)[options.aspect](),
			thisSize
		;
		for (var i = 1; i < this.length; ++i) {
			thisSize = this.eq(i)[options.aspect]();
			if ((options.max && thisSize > bestSize) || (!options.max && thisSize < bestSize)) {
				bestSize = thisSize;
				bestIndex = i;
			}
		}

		return [ this.eq(bestIndex), bestSize ];
	};
});