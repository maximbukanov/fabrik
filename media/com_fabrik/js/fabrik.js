/**
 * Various Fabrik JS classes
 *
 * @copyright: Copyright (C) 2005-2015, fabrikar.com - All rights reserved.
 * @license:   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

/*jshint mootools: true */
/*global Fabrik:true, fconsole:true, Joomla:true, $H:true, FbForm:true */

var $ = jQuery;
/**
 * Console.log wrapper
 */

function fconsole() {
	if (typeof (window.console) !== 'undefined') {
		var str = '', i;
		for (i = 0; i < arguments.length; i++) {
			str += arguments[i] + ' ';
		}
		console.log(str);
	}
}

/**
 * This class is temporarily required until this patch makes it into the CMS
 * code: https://github.com/joomla/joomla-platform/pull/1209/files Its purpose
 * is to queue ajax requests so they are not all fired at the same time - which
 * result in db session errors.
 *
 * Currently this is called from: fabriktables.js
 *
 */

var RequestQueue = my.Class({

	queue: {}, // object of xhr objects

	constructor: function () {
		var self = this;
		this.periodical = setInterval(function () {
			self.processQueue.call(self, true);
		}, 500);
	},

	add: function (xhr) {
		var k = xhr.options.url + Object.toQueryString(xhr.options.data) + Math.random();
		if (!this.queue[k]) {
			this.queue[k] = xhr;
		}
	},

	processQueue: function () {
		if (Object.keys(this.queue).length === 0) {
			return;
		}
		var running = false;

		// Remove successfully completed xhr
		jQuery.each(this.queue, function (k, xhr) {
			if (xhr.isSuccess()) {
				delete (this.queue[k]);
				running = false;
			}
		}.bind(this));

		// Find first xhr not run and completed to run
		jQuery.each(this.queue, function (k, xhr) {
			if (!xhr.isRunning() && !xhr.isSuccess() && !running) {
				xhr.send();
				running = true;
			}
		});
	},

	empty: function () {
		return Object.keys(this.queue).length === 0;
	}
});

Request.HTML = my.Class(Request, {

	options: {
		update: false,
		append: false,
		evalScripts: true,
		filter: false,
		headers: {
			Accept: 'text/html, application/xml, text/xml, */*'
		}
	},

	success: function (text) {
		var options = this.options, response = this.response;

		response.html = text.stripScripts(function (script) {
			response.javascript = script;
		});

		var match = response.html.match(/<body[^>]*>([\s\S]*?)<\/body>/i);
		if (match) {
			response.html = match[1];
		}
		var temp = $('<div />').html(response.html);

		response.tree = temp.childNodes;
		response.elements = temp.getElements(options.filter || '*');

		if (options.filter) {
			response.tree = response.elements;
		}
		if (options.update) {
			var update = document.id(options.update).empty();
			if (options.filter) {
				update.append(response.elements);
			} else {

				update.html(response.html);
			}
		} else if (options.append) {
			var append = document.id(options.append);
			if (options.filter) {
				response.elements.reverse().inject(append);
			} else {
				append.append(temp.getChildren());
			}
		}
		if (options.evalScripts) {
			Browser.exec(response.javascript);
		}

		this.onSuccess(response.tree, response.elements, response.html, response.javascript);
	}
});

/**
 * Keeps the element position in the centre even when scroll/resizing
 */

/*Element.implement({
	keepCenter: function () {
		this.makeCenter();
		window.addEvent('scroll', function () {
			this.makeCenter();
		}.bind(this));
		window.addEvent('resize', function () {
			this.makeCenter();
		}.bind(this));
	},
	makeCenter: function () {
		var l = window.getWidth() / 2 - this.getWidth() / 2;
		var t = window.getScrollTop() + (window.getHeight() / 2 - this.getHeight() / 2);
		this.setStyles({
			left: l,
			top: t
		});
	}
});*/

/**
 * Extend the Array object
 *
 * @param candid
 *            The string to search for
 * @returns Returns the index of the first match or -1 if not found
 */
Array.prototype.searchFor = function (candid) {
	var i;
	for (i = 0; i < this.length; i++) {
		if (this[i].indexOf(candid) === 0) {
			return i;
		}
	}
	return -1;
};

/**
 * Loading animation class, either inline next to an element or full screen
 * Paul 20130809 Adding functionality to handle multiple simultaneous spinners
 * on same field.
 */
var Loader = my.Class({

	constructor: function (options) {
		this.spinners = {};
		this.spinnerCount = {};
	},

	start: function (inline, msg) {
		if (typeOf(document.id(inline)) === 'null') {
			inline = false;
		}
		inline = inline ? inline : document.body;
		msg = msg ? msg : Joomla.JText._('COM_FABRIK_LOADING');
		if (!this.spinners[inline]) {
			this.spinners[inline] = new Spinner(inline, {
				'message': msg
			});
		}
		if (!this.spinnerCount[inline]) {
			this.spinnerCount[inline] = 1;
		} else {
			this.spinnerCount[inline]++;
		}
		// If field is hidden we will get a TypeError
		if (this.spinnerCount[inline] === 1) {
			try {
				this.spinners[inline].position().show();
			} catch (err) {
				// Do nothing
			}
		}
	},

	stop: function (inline) {
		if (typeOf(document.id(inline)) === 'null') {
			inline = false;
		}
		inline = inline ? inline : document.body;
		if (!this.spinners[inline] || !this.spinnerCount[inline]) {
			return;
		}
		if (this.spinnerCount[inline] > 1) {
			this.spinnerCount[inline]--;
			return;
		}

		var s = this.spinners[inline];

		// Don't keep the spinner once stop is called - causes issue when loading
		// ajax form for 2nd time
		if (Browser.ie && Browser.version < 9) {

			// Well ok we have to in ie8 ;( otherwise it give a js error
			// somewhere in FX
			s.hide();
		} else {
			s.destroy();
			delete this.spinnerCount[inline];
			delete this.spinners[inline];
		}
	}
});

/**
 * Create the Fabrik name space
 */

if (typeof (Fabrik) === 'undefined') {

	if (typeof (jQuery) !== 'undefined') {
		jQuery(document).on('click', '.popover button.close', function (event, target) {
			var popover = '#' + $(this).data('popover');
			var pEl = $(popover);
			jQuery(popover).popover('hide');

			if (typeOf(pEl) !== 'null' && pEl.prop('tagName') === 'INPUT') {
				pEl.prop('checked', false);
			}
		});
	}
	Fabrik = {};
	Fabrik.events = {};

	/**
	 * Get the bootstrap version. Returns either 2.x of 3.x
	 * @param {string} pluginName Optional plugin name to search fof VERSION property
	 * @returns {*}
	 */
	Fabrik.bootstrapVersion = function (pluginName) {
		pluginName = pluginName || 'modal';
		var pluginFn = jQuery.fn[pluginName];
		if (pluginFn) {
			if (pluginFn.VERSION) {
				return pluginFn.VERSION;
			}
			if (pluginName === 'modal') {
				// Bootstrap 2 doesn't use namespace on modal data (at least for now...)
				return pluginFn.toString().indexOf('bs.modal') === -1 ? '2.x' : '3.x';
			}
		}
	};

	Fabrik.Windows = {};
	Fabrik.loader = new Loader();
	Fabrik.blocks = {};
	Fabrik.periodicals = {};
	Fabrik.addBlock = function (blockid, block) {
		Fabrik.blocks[blockid] = block;
		Fabrik.trigger('fabrik.block.added', [block, blockid]);
	};

	/**
	 * Search for a block
	 *
	 * @param string
	 *            blockid Block id
	 * @param bool
	 *            exact Exact match - default false. When false, form_8 will
	 *            match form_8 & form_8_1
	 * @param function
	 *            cb Call back function - if supplied a periodical check is set
	 *            to find the block and once found then the cb() is run, passing
	 *            the block back as an parameter
	 *
	 * @return mixed false if not found | Fabrik block
	 */
	Fabrik.getBlock = function (blockid, exact, cb) {
		cb = cb ? cb : false;
		if (cb) {
			Fabrik.periodicals[blockid] = setInterval(function () {
				Fabrik._getBlock(this, [blockid, exact, cb]);
			}, 500);
		}
		return Fabrik._getBlock(blockid, exact, cb);
	};

	/**
	 * Private Search for a block
	 *
	 * @param string
	 *            blockid Block id
	 * @param bool
	 *            exact Exact match - default false. When false, form_8 will
	 *            match form_8 & form_8_1
	 * @param function
	 *            cb Call back function - if supplied a periodical check is set
	 *            to find the block and once found then the cb() is run, passing
	 *            the block back as an parameter
	 *
	 * @return mixed false if not found | Fabrik block
	 */
	Fabrik._getBlock = function (blockid, exact, cb) {
		var foundBlockId;
		exact = exact ? exact : false;
		if (Fabrik.blocks[blockid] !== undefined) {

			// Exact match
			foundBlockId = blockid;
		} else {
			if (exact) {
				return false;
			}
			// Say we're editing a form (blockid = form_1_2) - but have simply
			// asked for form_1
			var keys = Object.keys(Fabrik.blocks), i = keys.searchFor(blockid);
			if (i === -1) {
				return false;
			}
			foundBlockId = keys[i];
		}
		if (cb) {
			clearInterval(Fabrik.periodicals[blockid]);
			cb(Fabrik.blocks[foundBlockId]);
		}
		return Fabrik.blocks[foundBlockId];
	};

	jQuery(document).on('click', '.fabrik_delete a, .fabrik_action a.delete, .btn.delete', function (e, target) {
		if (e.rightClick) {
			return;
		}
		Fabrik.watchDelete(e, target);
	});
	jQuery(document).on('click', '.fabrik_edit a, a.fabrik_edit', function (e, target) {
		if (e.rightClick) {
			return;
		}
		Fabrik.watchEdit(e, target);
	});
	jQuery(document).on('click', '.fabrik_view a, a.fabrik_view', function (e, target) {
		if (e.rightClick) {
			return;
		}
		Fabrik.watchView(e, target);
	});

	// Related data links
	jQuery(document).on('click', '*[data-fabrik-view]', function (e) {
		if (e.rightClick) {
			return;
		}
		var url, a, title;
		e.preventDefault();
		if ($(e.target).prop('tagName') === 'A') {
			a = e.target;
		} else {
			a = $(this).find('a').length > 0 ? $(this).find('a') : $(this).closest('a');
		}

		url = a.prop('href');
		url += url.contains('?') ? '&tmpl=component&ajax=1' : '?tmpl=component&ajax=1';

		// Only one edit window open at the same time.
		jQuery.each(Fabrik.Windows, function (key, win) {
			win.close();
		});
		title = a.get('title');
		if (!title) {
			title = Joomla.JText._('COM_FABRIK_VIEW');
		}

		var winOpts = {
			'id': 'view.' + url,
			'title': title,
			'loadMethod': 'xhr',
			'contentURL': url
		};
		Fabrik.getWindow(winOpts);
	});

	Fabrik.removeEvent = function (type, fn) {
		if (Fabrik.events[type]) {
			var index = Fabrik.events[type].indexOf(fn);
			if (index !== -1) {
				delete Fabrik.events[type][index];
			}
		}
	};

	// Events test: replacing window.addEvents as they are reset when you reload
	// mootools in ajax window.
	// need to load mootools in ajax window otherwise Fabrik classes don't
	// correctly load
	Fabrik.addEvent = function (type, fn) {
		if (!Fabrik.events[type]) {
			Fabrik.events[type] = [];
		}
		if (Fabrik.events[type].indexOf(fn) === -1) {
			Fabrik.events[type].push(fn);
		}
	};

	Fabrik.addEvents = function (events) {
		var event;
		for (event in events) {
			if (events.hasOwnProperty(event)) {
				Fabrik.addEvent(event, events[event]);
			}
		}
		return this;
	};

	Fabrik.trigger = function (type, args, delay) {
		var events = Fabrik.events;

		// An array of returned values from all events.
		this.eventResults = [];
		if (!events || !events[type]) {
			return this;
		}
		args = Array.from(args);
		events[type].each(function (fn) {
			if (delay) {
				this.eventResults.push(fn.delay(delay, this, args));
			} else {
				this.eventResults.push(fn.apply(this, args));
			}
		}, this);
		return this;
	};

	Fabrik.requestQueue = new RequestQueue();

	Fabrik.cbQueue = {
		'google': []
	};

	/**
	 * Load the google maps API once
	 *
	 * @param bool
	 *            s Sensor
	 * @param mixed
	 *            cb Callback method function or function name (assinged to
	 *            window)
	 *
	 */

	Fabrik.loadGoogleMap = function (s, cb) {

		var prefix = document.location.protocol === 'https:' ? 'https:' : 'http:';
		var src = prefix + '//maps.googleapis.com/maps/api/js?&sensor=' + s + '&libraries=places&callback=Fabrik.mapCb';

		// Have we previously started to load the Googlemaps script?
		var gmapScripts = Array.from(document.scripts).filter(function (f) {
			return f.src === src;
		});

		if (gmapScripts.length === 0) {
			// Not yet loaded so create a script dom node and inject it into the
			// page.
			var script = document.createElement('script');
			script.type = 'text/javascript';
			script.src = src;
			document.body.appendChild(script);

			// Store the callback into the cbQueue, which will be processed
			// after gmaps is loaded.
			Fabrik.cbQueue.google.push(cb);
		} else {
			// We've already added the Google maps js script to the document
			if (Fabrik.googleMap) {
				window[cb]();

				// $$$ hugh - need to fire these by hand, otherwise when
				// re-using a map object, like
				// opening a popup edit for the second time, the map JS will
				// never get these events.

				// window.fireEvent('google.map.loaded');
				// window.fireEvent('google.radius.loaded');

			} else {
				// We've started to load the Google Map code but the callback
				// has not been fired.
				// Cache the call back (it will be fired when Fabrik.mapCb is
				// run.
				Fabrik.cbQueue.google.push(cb);

			}
		}
	};

	/**
	 * Called once the google maps script has loaded, will run through any
	 * queued callback methods and fire them.
	 */
	Fabrik.mapCb = function () {
		Fabrik.googleMap = true;
		var fn, i;
		for (i = 0; i < Fabrik.cbQueue.google.length; i++) {
			fn = Fabrik.cbQueue.google[i];
			if (typeOf(fn) === 'function') {
				fn();
			} else {
				window[fn]();
			}
		}
		Fabrik.cbQueue.google = [];
	};

	/** Globally observe delete links * */

	Fabrik.watchDelete = function (e, target) {
		var l, ref, r;
		r = $(e.target).closest('.fabrik_row');
		if (!r) {
			r = Fabrik.activeRow;
		}
		if (r) {
			var chx = r.find('input[type=checkbox][name*=id]');
			if (chx.length > 0) {
				chx.checked = true;
			}
			ref = r.id.split('_');
			ref = ref.splice(0, ref.length - 2).join('_');
			l = Fabrik.blocks[ref];
		} else {
			// CheckAll
			ref = $(e.target).closest('.fabrikList');
			if (ref.length > 0) {
				// Embedded in list
				ref = ref.id;
				l = Fabrik.blocks[ref];
			} else {
				// Floating
				var wrapper = target.closest('.floating-tip-wrapper');
				if (wrapper.length > 0) {
					var refList = wrapper.data('list');
					ref = refList.id;
				} else {
					ref = target.data('listRef');
				}

				l = Fabrik.blocks[ref];
				// Deprecated in 3.1 // should only check all for floating tips
				if (l.options.actionMethod === 'floating' && !this.bootstrapped) {
					l.form.getElements('input[type=checkbox][name*=id], input[type=checkbox][name=checkAll]').each(function (c) {
						c.checked = true;
					});
				}
			}
		}
		// Get correct list block
		if (!l.submit('list.delete')) {
			e.stopPropagation();
		}
	};

	/**
	 * Globally watch list edit links
	 *
	 * @param event
	 *            e relayed click event
	 * @param domnode
	 *            target <a> link
	 *
	 * @since 3.0.7
	 */
	Fabrik.watchEdit = function (e, target) {
		Fabrik.openSingleView('form', e, target);
	};

	/**
	 * Globally watch list view links
	 *
	 * @param event
	 *            e relayed click event
	 * @param domnode
	 *            target <a> link
	 *
	 * @since 3.0.7
	 */

	Fabrik.watchView = function (e, target) {
		Fabrik.openSingleView('details', e, target);
	};

	/**
	 * Open a single details/form view
	 * @param view - details or form
	 * @param event
	 *            e relayed click event
	 * @param domnode
	 *            target <a> link
	 */
	Fabrik.openSingleView = function (view, e, target) {
		var url, loadMethod = 'xhr', a;
		var listRef = target.data('list');
		var list = Fabrik.blocks[listRef];
		if (!list.options.ajax_links) {
			return;
		}
		e.preventDefault();
		var row = list.getActiveRow(e);
		if (!row) {
			return;
		}
		list.setActive(row);
		var rowid = row.id.split('_').pop();

		if ($(e.target).prop('tagName') === 'A') {
			a = $(e.target);
		} else {
			a = $(e.target).find('a').length > 0 ? $(e.target).find('a') : $(e.target).closest('a');
		}
		url = a.prop('href');
		url += url.contains('?') ? '&tmpl=component&ajax=1' : '?tmpl=component&ajax=1';
		loadMethod = a.data('loadmethod');
		if (typeOf(loadMethod) === 'null') {
			loadMethod = 'xhr';
		}

		// Only one edit window open at the same time.
		jQuery(Fabrik.Windows, function (key, win) {
			win.close();
		});

		var winOpts = {
			'id': listRef + '.' + rowid,
			'title': list.options.popup_view_label,
			'loadMethod': loadMethod,
			'contentURL': url,
			'width': list.options.popup_width,
			'height': list.options.popup_height,
			'onClose': function (win) {
				var k = view +  '_' + list.options.formid + '_' + rowid;
				try {
					Fabrik.blocks[k].destroyElements();
					Fabrik.blocks[k].formElements = null;
					Fabrik.blocks[k] = null;
					delete (Fabrik.blocks[k]);
					var evnt = (view === 'details') ? 'fabrik.list.row.view.close' : 'fabrik.list.row.edit.close';
					Fabrik.trigger(evnt, [listRef, rowid, k]);
				} catch (e) {
					console.log(e);
				}
			}
		};
		winOpts.id = view === 'details' ? 'view.' + winOpts.id : 'add.' + winOpts.id;
		if (typeOf(list.options.popup_offset_x) !== 'null') {
			winOpts.offset_x = list.options.popup_offset_x;
		}
		if (typeOf(list.options.popup_offset_y) !== 'null') {
			winOpts.offset_y = list.options.popup_offset_y;
		}
		Fabrik.getWindow(winOpts);
	};

	Fabrik.form = function (ref, id, opts) {
		var form = new FbForm(id, opts);
		Fabrik.addBlock(ref, form);
		return form;
	};

	$(window).trigger('fabrik.loaded');
}

/**
 * Polyfills
 */

if (!Object.keys) Object.keys = function(o) {
	if (o !== Object(o))
		throw new TypeError('Object.keys called on a non-object');
	var k=[],p;
	for (p in o) if (Object.prototype.hasOwnProperty.call(o,p)) k.push(p);
	return k;
};

if (!Array.prototype.filter) {
	Array.prototype.filter = function(fun/*, thisArg*/) {
		'use strict';

		if (this === void 0 || this === null) {
			throw new TypeError();
		}

		var t = Object(this);
		var len = t.length >>> 0;
		if (typeof fun !== 'function') {
			throw new TypeError();
		}

		var res = [];
		var thisArg = arguments.length >= 2 ? arguments[1] : void 0;
		for (var i = 0; i < len; i++) {
			if (i in t) {
				var val = t[i];

				// NOTE: Technically this should Object.defineProperty at
				//       the next index, as push can be affected by
				//       properties on Object.prototype and Array.prototype.
				//       But that method's new, and collisions should be
				//       rare, so use the more-compatible alternative.
				if (fun.call(thisArg, val, i, t)) {
					res.push(val);
				}
			}
		}

		return res;
	};
}