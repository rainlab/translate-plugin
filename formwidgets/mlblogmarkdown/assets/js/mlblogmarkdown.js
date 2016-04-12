/*
 * MLRichEditor plugin
 * 
 * Data attributes:
 * - data-control="mlricheditor" - enables the plugin on an element
 * - data-textarea-element="textarea#id" - an option with a value
 *
 * JavaScript API:
 * $('a#someElement').mlRichEditor({ option: 'value' })
 *
 */

+function ($) { "use strict";

    // MLRICHEDITOR CLASS DEFINITION
    // ============================

    var MLBlogMarkdown = function(element, options) {
        this.options   = options
        this.$el       = $(element)
        this.$textarea = $(options.textareaElement)
        this.$form = $('#post-form')
        this.$markdownEditor = $('[data-field-name=content] [data-control=markdowneditor]:first', this.$form)

        // Init
        this.init()

        this.$el.multiLingual()
    }

    MLBlogMarkdown.DEFAULTS = {
    }

    MLBlogMarkdown.prototype.init = function() {
        var $el = this.$el,
            $textarea = this.$textarea,
            editor = this.$markdownEditor.markdownEditor('getEditorObject');

        $el.on('setLocale.oc.multilingual', function(e, locale, localeValue) {
            if (typeof localeValue === 'string') {
                editor.getSession().setValue(localeValue)
            }
        });

        editor.on('change', function() {
            console.log('textarea' + $textarea.val());
            console.log('editor' + editor.getSession().getValue());
            $el.multiLingual('setLocaleValue', editor.getSession().getValue())
        })
    }
    
    var old = $.fn.mlBlogMarkdown

    $.fn.mlBlogMarkdown = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result

        this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.mlBlogMarkdown')
            var options = $.extend({}, MLBlogMarkdown.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.mlBlogMarkdown', (data = new MLBlogMarkdown(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : this
    }

    $.fn.mlBlogMarkdown.Constructor = MLBlogMarkdown;

    $.fn.mlBlogMarkdown.noConflict = function () {
        $.fn.mlBlogMarkdown = old
        return this
    }

    $(document).render(function (){
        $('[data-control="mlblogmarkdown"]').mlBlogMarkdown()
    })


}(window.jQuery);
