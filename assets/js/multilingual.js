/*
 * Multi lingual control plugin
 * 
 * Data attributes:
 * - data-control="multilingual" - enables the plugin on an element
 * - data-default-field="#defaultField" - an element that contains the default value
 *
 * JavaScript API:
 * $('a#someElement').multiLingual({ option: 'value' })
 *
 * Dependences: 
 * - Nil
 */

+function ($) { "use strict";

    // MULTILINGUAL CLASS DEFINITION
    // ============================

    var MultiLingual = function(element, options) {
        var self       = this
        this.options   = options
        this.$el       = $(element)
    }

    MultiLingual.DEFAULTS = {
        defaultField: null
    }

    MultiLingual.prototype.xxx = function() {

    }

    // MULTILINGUAL PLUGIN DEFINITION
    // ============================

    var old = $.fn.multiLingual

    $.fn.multiLingual = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result
        this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.multilingual')
            var options = $.extend({}, MultiLingual.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.multilingual', (data = new MultiLingual(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : this
    }

    $.fn.multiLingual.Constructor = MultiLingual

    // MULTILINGUAL NO CONFLICT
    // =================

    $.fn.multiLingual.noConflict = function () {
        $.fn.multiLingual = old
        return this
    }

    // MULTILINGUAL DATA-API
    // ===============
    $(document).render(function () {
        $('[data-control="multilingual"]').multiLingual()
    })

}(window.jQuery);
alert('wo')