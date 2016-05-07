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

    var MLRichEditor = function(element, options) {
        this.options   = options
        this.$el       = $(element)
        this.$textarea = $(options.textareaElement)
        this.$richeditor = $('[data-control=richeditor]', this.$el)

        // Init
        this.init()

        this.$el.multiLingual()
    }

    MLRichEditor.DEFAULTS = {
    }

    MLRichEditor.prototype.init = function() {
        var $el = this.$el,
            $textarea = this.$textarea,
            $richeditor = this.$richeditor

        $el.on('setLocale.oc.multilingual', function(e, locale, localeValue) {
            if (typeof localeValue === 'string' && $richeditor.data('oc.richEditor')) {
                $textarea.redactor('code.set', localeValue);
            }
        })

        $textarea.on('sanitize.oc.richeditor', function() {
            $el.multiLingual('setLocaleValue', this.value)
        })
    }

    // MLRICHEDITOR PLUGIN DEFINITION
    // ============================

    var old = $.fn.mlRichEditor

    $.fn.mlRichEditor = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result
        this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.mlRichEditor')
            var options = $.extend({}, MLRichEditor.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.mlRichEditor', (data = new MLRichEditor(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : this
    }

    $.fn.mlRichEditor.Constructor = MLRichEditor

    // MLRICHEDITOR NO CONFLICT
    // =================

    $.fn.mlRichEditor.noConflict = function () {
        $.fn.mlRichEditor = old
        return this
    }

    // MLRICHEDITOR DATA-API
    // ===============

    $(document).render(function () {
        $('[data-control="mlricheditor"]').mlRichEditor()
    })

}(window.jQuery);
