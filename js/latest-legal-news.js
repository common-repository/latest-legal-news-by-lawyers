// initialize the widget
//jQuery(document).ready(function() {
(function ( $ ) { 
	/*! Copyright (c) 2011 Brandon Aaron (http://brandonaaron.net)
	 * Licensed under the MIT License (LICENSE.txt).
	 * Thanks to: http://adomas.org/javascript-mouse-wheel/ for some pointers.
	 * Version: 3.0.6
	 * Requires: 1.2.2+
	 */
	(function(a){function d(b){var c=b||window.event,d=[].slice.call(arguments,1),e=0,f=!0,g=0,h=0;return b=a.event.fix(c),b.type="mousewheel",c.wheelDelta&&(e=c.wheelDelta/120),c.detail&&(e=-c.detail/3),h=e,c.axis!==undefined&&c.axis===c.HORIZONTAL_AXIS&&(h=0,g=-1*e),c.wheelDeltaY!==undefined&&(h=c.wheelDeltaY/120),c.wheelDeltaX!==undefined&&(g=-1*c.wheelDeltaX/120),d.unshift(b,e,g,h),(a.event.dispatch||a.event.handle).apply(this,d)}var b=["DOMMouseScroll","mousewheel"];if(a.event.fixHooks)for(var c=b.length;c;)a.event.fixHooks[b[--c]]=a.event.mouseHooks;a.event.special.mousewheel={setup:function(){if(this.addEventListener)for(var a=b.length;a;)this.addEventListener(b[--a],d,!1);else this.onmousewheel=d},teardown:function(){if(this.removeEventListener)for(var a=b.length;a;)this.removeEventListener(b[--a],d,!1);else this.onmousewheel=null}},a.fn.extend({mousewheel:function(a){return a?this.bind("mousewheel",a):this.trigger("mousewheel")},unmousewheel:function(a){return this.unbind("mousewheel",a)}})})(jQuery);


	var scrollLegalItemsMethods = {
		// global scroll pause
		pause_scroll : false,

		init : function() {
			return this.each(function() {
				var $this = $(this),
					data = $this.data('data');

				// init defaults / events
				if ( !data ) {

					$(this).data('data', {
						target : $this,
						items_total : $this.find('li').size(),
						items_limit : 4,
						error : false
					});
					data = $this.data('data');

					if ( data.items_limit>=data.items_total )
						$this.find('.prev, .next').addClass('hide');
					else
						$this.find('.next').removeClass('hide');

					if ( data.items_total>data.items_limit ) {
						// set scroll/pagination event
						$this.mousewheel( scrollLegalItemsMethods.mousewheelScrollItems );
						$this.find('.next').click( {target:$this}, scrollLegalItemsMethods.onClickNext );
						$this.find('.prev').click( {target:$this}, scrollLegalItemsMethods.onClickPrev );
					}

				} // if !data
			});
		},

		mousewheelScrollItems : function(event, delta) {
			var $this = jQuery(this);

			if ( delta<0 )
				$this.find('.next').click();
			else
				$this.find('.prev').click();
			if ( scrollLegalItemsMethods.pause_scroll!==1 )
				event.preventDefault();
		},

		onClickNext : function(event) {
			var $this = event.data.target,
				data = $this.data('data');
			elm = event.target;

			if ( scrollLegalItemsMethods.pause_scroll )
				return false;
			var last_current_index = $this.find('li:visible:last').index();
			var last_index = $this.find('li:last').index();
			if ( last_current_index >= last_index ) {
				//alert('You have reached the end of the list.');
				return false;
			}

			scrollLegalItemsMethods.pause_scroll = true;
			var new_event = {target:$this.find('li:visible:last').next(), data:{target:$this}};
			scrollLegalItemsMethods.showNextItems( new_event, false );

			if ( last_index === $this.find('li:visible:last').index() )
				$(elm).addClass('hide');
			$this.find('.prev').removeClass('hide');
			return true;
		},

		onClickPrev : function(event) {
			var $this = event.data.target,
				data = $this.data('data');
			elm = event.target;

			if ( scrollLegalItemsMethods.pause_scroll )
				return false;
			var first_current_index = $this.find('li:visible:first').index();
			var first_index = $this.find('li:first').index();
			if ( first_current_index <= first_index ) {
				//alert('You have reached the end of the list.');
				return false;
			}

			scrollLegalItemsMethods.pause_scroll = true;
			var new_event = {target:$this.find('li:visible:first').prev(), data:{target:$this}};
			scrollLegalItemsMethods.showNextItems( new_event, true );

			if ( first_index === $this.find('li:visible:first').index() )
				$(elm).addClass('hide');
			$this.find('.next').removeClass('hide');
			return true;
		},

		showNextItems : function(event, prev) {
			var $this = event.data.target,
				data = $this.data('data'),
				elm = event.target;

			$this.find('li').slideUp(400, function(){ $(this).removeClass('show'); });
			for (x=0; x<data.items_limit; x++) {
				if ( elm.size()!=1)
					break;
				if ( elm.is('li') )
					elm.slideDown(400, function(){ $(this).addClass('show'); scrollLegalItemsMethods.pause_scroll=false; });
				else
					x--;
				if ( prev )
					elm = elm.prev();
				else
					elm = elm.next();
			}
		},
	};

	$.fn.scrollLegalItems = function( method ) {
		// Method calling logic
		if ( scrollLegalItemsMethods[method] ) {
			return scrollLegalItemsMethods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || !method ) {
			return scrollLegalItemsMethods.init.apply( this, arguments );
		} else {
			console.log(method + ' doesn\'t exist');
		}	
	};


	var navigation = '<div class="legal_navigation"><span class="prev hide">&lt;Prev</span><span class="next hide">Next&gt;</span></div>';
	jQuery('.legal_news_headlines_block').append( navigation );
	jQuery('.legal_news_headlines_block').scrollLegalItems();
//});
}(jQuery));








// /**
//  * legal-news-headlines.js - Javascript for the widget.
//  * @package Legal News Headlines
//  */

// jQuery(document).ready(function($) {
//     // $() will work as an alias for jQuery() inside of this function

// 	/*
// 	* vertical news ticker
// 	* Tadas Juozapaitis ( kasp3rito [eta] gmail (dot) com )
// 	* http://www.jugbit.com/jquery-vticker-vertical-news-ticker/
// 	*/
// 	$.fn.vTicker = function(options) {
// 		var defaults = {
// 			speed: 700,
// 			pause: 4000,
// 			showItems: 3,
// 			animation: '',
// 			mousePause: true,
// 			isPaused: false,
// 			direction: 'up',
// 			height: 0
// 		};

// 		var options = $.extend(defaults, options);

// 		moveUp = function(obj2, height, options){
// 			if(options.isPaused)
// 				return;
			
// 			var obj = obj2.children('ul');
			
// 	    	var clone = obj.children('li:first').clone(true);
			
// 			if(options.height > 0)
// 			{
// 				height = obj.children('li:first').height();
// 			}		
			
// 	    	obj.stop(true).animate({top: '-=' + height + 'px'}, options.speed, function() {
// 	        	$(this).children('li:first').remove();
// 	        	$(this).css('top', '0px');
// 	        });
			
// 			if(options.animation == 'fade')
// 			{
// 				obj.children('li:first').fadeOut(options.speed);
// 				if(options.height == 0)
// 				{
// 				obj.children('li:eq(' + options.showItems + ')').hide().fadeIn(options.speed).show();
// 				}
// 			}

// 	    	clone.appendTo(obj);
// 		};
		
// 		moveDown = function(obj2, height, options){
// 			if(options.isPaused)
// 				return;
			
// 			var obj = obj2.children('ul');
			
// 	    	var clone = obj.children('li:last').clone(true);
			
// 			if(options.height > 0)
// 			{
// 				height = obj.children('li:first').height();
// 			}
			
// 			obj.css('top', '-' + height + 'px')
// 				.prepend(clone);
				
// 	    	obj.stop(true).animate({top: 0}, options.speed, function() {
// 	        	$(this).children('li:last').remove();
// 	        });
			
// 			if(options.animation == 'fade')
// 			{
// 				if(options.height == 0)
// 				{
// 					obj.children('li:eq(' + options.showItems + ')').fadeOut(options.speed);
// 				}
// 				obj.children('li:first').hide().fadeIn(options.speed).show();
// 			}
// 		};
		
// 		return this.each(function() {
// 			var obj = $(this);
// 			var maxHeight = 0;

// 			obj.css({overflow: 'hidden', position: 'relative'})
// 				.children('ul').css({position: 'absolute', margin: 0, padding: 0})
// 				.children('li').css({margin: 0});

// 			if(options.height == 0)
// 			{
// 				obj.children('ul').children('li').each(function(){
// 					if($(this).height() > maxHeight)
// 					{
// 						maxHeight = $(this).height();
// 					}
// 				});

// 				obj.children('ul').children('li').each(function(){
// 					$(this).height(maxHeight);
// 				});

// 				obj.height(maxHeight * options.showItems);
// 			}
// 			else
// 			{
// 				obj.height(options.height);
// 			}
			
// 	    	var interval = setInterval(function(){ 
// 				if(options.direction == 'up')
// 				{ 
// 					moveUp(obj, maxHeight, options); 
// 				}
// 				else
// 				{ 
// 					moveDown(obj, maxHeight, options); 
// 				} 
// 			}, options.pause);
			
// 			if(options.mousePause)
// 			{
// 				obj.bind("mouseenter",function(){
// 					options.isPaused = true;
// 				}).bind("mouseleave",function(){
// 					options.isPaused = false;
// 				});
// 			}
// 		});
// 	};

// 	// find our widgets and apply the scroller addition, above...
// 	// we force the height setting to be the current height of the box. using other settings 
// 	// is possible, but it adds spaces between list items in order to make them regular
// 	$('div.legal_news_headlines_list_container').each(function() {
// 		$(this).vTicker({height: $(this).height()});
// 	});

// });