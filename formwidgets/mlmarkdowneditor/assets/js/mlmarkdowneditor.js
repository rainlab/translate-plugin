/*
 * MLMarkdownEditor plugin
 * 
 * Data attributes:
 * - data-control="mlmarkdowneditor" - enables the plugin on an element
 * - data-textarea-element="textarea#id" - an option with a value
 *
 * JavaScript API:
 * $('a#someElement').mlRichEditor({ option: 'value' })
 *
 */

+function ($) { "use strict";

    // MLMarkdownEditor CLASS DEFINITION
    // ============================

    var MLMarkdownEditor = function(element, options) {
        this.options   = options
        this.$el       = $(element)
        this.$markdownEditor = $('[data-control=markdowneditor]:first', this.$el)
        this.$form = this.$el.closest('form')

        // Init
        this.init()

        this.$el.multiLingual()
    }

    MLMarkdownEditor.DEFAULTS = {
    }

    MLMarkdownEditor.prototype.init = function() {
        var $el = this.$el,
            $markdownEditor = this.$markdownEditor,
            $form = this.$form,
            editor = this.$markdownEditor.markdownEditor('getEditorObject');

        $el.on('setLocale.oc.multilingual', function(e, locale, localeValue) {
            if (typeof localeValue === 'string') {
                editor.getSession().setValue(localeValue)
            }
        });

        editor.on('change', function() {
            $el.multiLingual('setLocaleValue', editor.getSession().getValue())
        });
    }

    var old = $.fn.mlMarkdownEditor

    $.fn.mlMarkdownEditor = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result

        this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.mlMarkdownEditor')
            var options = $.extend({}, MLMarkdownEditor.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.mlMarkdownEditor', (data = new MLMarkdownEditor(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : this
    }

    $.fn.mlMarkdownEditor.Constructor = MLMarkdownEditor;

    $.fn.mlMarkdownEditor.noConflict = function () {
        $.fn.mlMarkdownEditor = old
        return this
    }

    $(document).render(function (){
        $('[data-control="mlmarkdowneditor"]').mlMarkdownEditor()
    })


}(window.jQuery);
