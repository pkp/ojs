/**
 * @file plugins/pkpTags/plugin.js
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief TinyMCE PKP tags plugin
 */
tinymce.PluginManager.add('pkpTags', function(editor, url) {
	editor.on('init', function() {
		var cssURL = url + '/styles/editor.css';
		if(document.createStyleSheet){
			document.createStyleSheet(cssURL);
		} else {
			cssLink = editor.dom.create('link', {
				rel: 'stylesheet',
				href: cssURL
			});
			document.getElementsByTagName('head')[0].
			appendChild(cssLink);
		}
	});

        editor.addButton('pkpTags', {
		icon: 'nonbreaking', // FIXME: This looks OK, but might be inappropriate
                type: 'panelbutton',
                panel: {
			icon: 'nonbreaking',
                        autohide: true,
			html: function() {
				var variableMap = $.pkp.classes.TinyMCEHelper.prototype.getVariableMap('#' + editor.id),
						markup = '<ul>';
				if (variableMap.length === 0) {
					markup += '<li>No tags are available.</li>';
				}
				$.each(variableMap, function(variable, value) {
					var $anchor = $('<a>').attr('href', '#' + variable).text(value);
					var $li = $('<li/>').append($anchor);
					var $container = $('<span>').append($li);
					markup += $container.html();
				});
				markup += '</ul>';
				return markup;
			},
                        onclick: function(e) {
                                var linkElm = editor.dom.getParent(e.target, 'a');

                                if (linkElm) {
					$.pkp.classes.TinyMCEHelper.prototype.getVariableElement(linkElm.hash.substring(1), $(linkElm).text());
					editor.insertContent(
							$.pkp.classes.TinyMCEHelper.prototype.getVariableElement(linkElm.hash.substring(1), $(linkElm).text()).html());
                                        this.hide();
                                }
                        }
                },
                tooltip: 'Insert Tag'
        });
});
