// Default settings for ajax
$.ajaxSetup({
	type: 'POST',
	dataType: 'json',
	cache: false
});

// Editable content Placeholder aka Default Value
$('[contenteditable=true][data-placeholder]')
	.html(function(i, data) {
		if (data == '') {
			$(this).addClass('js-placeholder')
				   .html($(this).data('placeholder'));
		}
	})
	.focus(function() {
		if ($(this).html() == $(this).data('placeholder')) {
			$(this).removeClass('js-placeholder')
				   .html('');
		}
	})
	.blur(function() {
		if ($(this).html() == '') {
			$(this).addClass('js-placeholder')
				   .html($(this).data('placeholder'));
		}
	});

// Mustache Show Template function
$.fn.appendTpl = function(template, view) {
	view['siteUrl'] = _url;
	$(this).append(Mustache.render(template, view));
};

// Get template for Mustache
function getTpl(tpl) {
	var template;
	$.ajax({
		url: _url + 'template/mustache/' + tpl + '.mustache',
		dataType: 'html',
		async: false
	}).done(function(data) {
		template = data;
	});

	return template;
}

// Get posts list
function getPosts(action) {
	var offset  = 0,
		loading = 0,
		template = null,
		putBookmarks = function() {
			$('.post-list-meta')
				.hover(function() {
					$('.reading-time', this).toggle();
					$('.bookmark',     this).toggle();
				})
				.click(function() {
					var $sel = $(this);
					$.ajax({
						url: _url + 'action/bookmark',
						data: {id: $sel.data('id')}
					}).done(function(data) {
						if (data['bookmarked'] == 1) {
							$sel.addClass('bookmark-set');
						} else {
							$sel.removeClass('bookmark-set');
						}
					});
				});
		},
		getList = function() {
			if (loading) {
				return;
			}

			loading = 1;
			$.ajax({
				url: _url + 'action/' + action,
				data: {username: _username,
					   offset:   offset}
			}).done(function(view) {
				if (view['status']) {
					$('.post-list').appendTpl(template, view);
					putBookmarks();
					loading = 0;
					offset++;
				}
			});
		},
		onScroll = function() {
			// Check if we're within 100 pixels of the bottom edge of the broser window.
			var winHeight = window.innerHeight ? window.innerHeight : $(window).height(), // iphone fix
			    closeToBottom = ($(window).scrollTop() + winHeight > $(document).height() - 100);
			
			if (closeToBottom) {
			  	getList();
			}
		};

	_username = typeof _username !== 'undefined' ? _username : -1;

	template = getTpl('postList');
	Mustache.parse(template);

	getList();
	$(window).bind('scroll.post-list', onScroll);
}

// Message close
$('.msg-close').on('click', function() {
	$('.msg').fadeOut();
});