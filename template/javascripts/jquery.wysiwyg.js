(function($){
	'use strict';
	$.fn.wysiwyg = function(userOptions){
		var editor = this.find(userOptions.container),
			$toolbarTooltip = this.find('.toolbar-tooltip'),
			$toolbarNormal = this.find('.toolbar-normal'),
			selectedRange,
			selectedX = 0,
			selectedY = 0,
			options,
			currentModal,
			updateToolbar = function(){
				$toolbarTooltip.find($('button')).each(function(){
					var command = $(this).data('edit');
					if(document.queryCommandState(command)){
						$(this).addClass('active');
					} else {
						$(this).removeClass('active');
					}
				});
			},
			clearSelection = function(){
				if(document.selection){
					document.selection.empty();
				} else if(window.getSelection){
					window.getSelection().removeAllRanges();
				}
			},
			execCommand = function(commandWithArgs, valueArg){
				var commandArr = commandWithArgs.split(' '),
					command = commandArr.shift(),
					args = commandArr.join(' ') + (valueArg || '');
				document.execCommand(command, 0, args);
				updateToolbar();
				clearSelection();
			},
			saveSelection = function(){
				var selection = window.getSelection();

				if(selection.getRangeAt && selection.rangeCount){
					selectedRange = selection.getRangeAt(0);
					
					var temporaryRange = selection.getRangeAt(0);	
					temporaryRange.collapse(true);
						
					var temporary = document.createElement('span');
					temporaryRange.insertNode(temporary);

					var rect = $(temporary).position();
					selectedX = rect.left;
					selectedY = rect.top;
					temporary.parentNode.removeChild(temporary);
					
					if(selection.toString().length > 0){
						$toolbarTooltip.css({ 
							'display': 'inline-block',
							'top': (selectedY - $toolbarTooltip.outerHeight())  + 'px', 
							'left': selectedX + 'px' });
						restoreSelection();
					} else {
						$toolbarTooltip.hide();
					}
				}
			},
			restoreSelection = function(){
				var selection = window.getSelection();
				if(selectedRange){
					try{
						selection.removeAllRanges();
					} catch(ex){
						document.body.createTextRange().select();
						document.selection.empty();
					}

					selection.addRange(selectedRange);
				}
			},
			updateContentEditable = function(){
				editor.find('div').each(function(index){
					if($(this).find('br').length > 0){
						$(this).addClass('extra');
					} else {
						$(this).removeClass('extra');
					}
				});
			},
			bindToolbar = function(toolbar, options){
				toolbar.find('button[data-edit]').on('click', function(){
					restoreSelection();
					editor.focus();
					execCommand($(this).data('edit'));
					saveSelection();
					$toolbarTooltip.hide();
				});
				
				toolbar.find('button[data-insert="h1"]').on('click', function(){
					restoreSelection();
					editor.focus();
					execCommand('insertHTML', '<h2>' + window.getSelection() + '</h2>');
					saveSelection();
					$toolbarTooltip.hide();
				});
				
				toolbar.find('button[data-insert="h2"]').on('click', function(){
					restoreSelection();
					editor.focus();
					execCommand('insertHTML', '<h3>' + window.getSelection() + '</h3>');
					saveSelection();
					$toolbarTooltip.hide();
				});
				
				toolbar.find('button[data-insert="bold"]').on('click', function(){
					restoreSelection();
					editor.focus();
					execCommand('insertHTML', '<strong>' + window.getSelection() + '</strong>');
					saveSelection();
					$toolbarTooltip.hide();
				});
				
				toolbar.find('button[data-insert="italic"]').on('click', function(){
					restoreSelection();
					editor.focus();
					execCommand('insertHTML', '<em>' + window.getSelection() + '</em>');
					saveSelection();
					$toolbarTooltip.hide();
				});
				
				toolbar.find('button[data-insert="blockquote"]').on('click', function(){
					restoreSelection();
					editor.focus();
					execCommand('insertHTML', '<blockquote>' + ((window.getSelection() == '') ? 'content...' : window.getSelection()) + '</blockquote>');
					saveSelection();
					$toolbarTooltip.hide();
				});
				
				toolbar.find('button[data-insert="page-break"]').on('click', function(){
					restoreSelection();
					editor.focus();
					execCommand('insertHTML', '<hr />');
					console.log('da');
					saveSelection();
					$toolbarTooltip.hide();
				});
				
				toolbar.find('button[data-toggle="link"]').on('click', function(){
					currentModal = $(document).modal({ content: 'URL: <input type="text" name="link" /><button name="submit-link">Add link</button>' });
					restoreSelection();
				});
				
				toolbar.find('button[data-toggle="image"]').on('click', function(){
					currentModal = $(document).modal({ content: 'Image: <form method="post" action="" enctype="multipart/form-data" id="form-image"><input type="file" name="image" /><button name="submit-image">Upload image</button></form>' });
					restoreSelection();
				});
			}
			
		options = $.extend({}, $.fn.wysiwyg.defaults, userOptions);
		
		if(options.working){
			bindToolbar($toolbarTooltip, options);
			bindToolbar($toolbarNormal, options);
			
			updateContentEditable();
			
			editor.attr('contenteditable', true).on('mouseup keyup', function(){
				saveSelection();
				updateToolbar();
				
				updateContentEditable();
			});
			$('body').on('mouseup', function(event){
				if(!editor.is(event.target) && editor.has(event.target).length === 0){
					$toolbarTooltip.hide();
				}
			});
			
			$(document).on('click', 'button[name="submit-link"]', function(){
				if($('input[name="link"]').val() != '' && /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/.test($('input[name="link"]').val())){
					restoreSelection();
					execCommand('createLink', $('input[name="link"]').val());
					currentModal.close();
					close();
				}
			});
			
			$(document).on('click', 'button[name="submit-image"]', function(event){
				event.preventDefault();
				
				var formData = new FormData($('form#form-image')[0]);
				$.ajax({
					url: _url + 'action/uploadImage',
					type: 'POST',
					success: function(html){
						restoreSelection();
						if(html['status'] == 1){
							execCommand('insertImage', html['image-url']);
							currentModal.close();
						}
						close();
					},
					error: function(){
						close();
					},
					data: formData,
					contentType: false,
					processData: false
				});
			});
		}
		
		return this;
	};
	
	$.fn.wysiwyg.defaults = {
		working: true,
		container: '.text'
	};
}(window.jQuery));