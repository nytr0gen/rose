(function($){
	'use strict';
	$.fn.modal = function(userOptions){
		var options,
			$modalOverlay = $('<div id="overlay"></div>'),
			$modalBox = $('<div id="modal"></div>'),
			$modalContent = $('<div class="content"></div>'),
			$modalClose = $('<div class="close"></div>'),
			center = function(){
				var top, left;

				top = Math.max($(window).height() - $modalBox.outerHeight(), 0) / 2;
				left = Math.max($(window).width() - $modalBox.outerWidth(), 0) / 2;

				$modalBox.css({
					top: top + $(window).scrollTop(), 
					left: left + $(window).scrollLeft()
				});
			},
			open = function(options){
				$modalBox.hide();
				$modalOverlay.hide();
				$modalBox.append($modalContent, $modalClose);
				$('body').append($modalOverlay, $modalBox);						
				$modalClose.on('click', function(){
					close();
				});
				$modalOverlay.on('click', function(){
					close();
				});
				
				$modalContent.empty().append(options.content);

				$modalBox.css({
					width: 'auto', 
					height: 'auto'
				});

				center();
				$(window).bind('resize.modal', center);
				$modalBox.show();
				$modalOverlay.show();
			},
			close = function(){
				$modalBox.hide();
				$modalOverlay.hide();
				
				$modalContent.empty();
				$(window).unbind('resize.modal');
			};
			
		options = $.extend({}, $.fn.modal.defaults, userOptions);
		
		open(options);
		
		return {
			close: function(){
				close();
			}		
		};
	};
	
	$.fn.modal.defaults = {
		content: ''
	};
}(window.jQuery));