/*
 * MLNestedForm plugin
 *
 * Data attributes:
 * - data-control="mlnestedform" - enables the plugin on an element
 *
 * JavaScript API:
 * $('a#someElement').mlNestedForm({ option: 'value' })
 *
 */

+function ($) { "use strict";

    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype

    // MLNESTEDFORM CLASS DEFINITION
    // ============================

    var MLNestedForm = function(element, options) {
        this.options   = options
        this.$el       = $(element)
        this.$selector = $('[data-locale-dropdown]', this.$el)
        this.$locale   = $('[data-nestedform-active-locale]', this.$el)
        this.locale    = options.defaultLocale

        $.oc.foundation.controlUtils.markDisposable(element)
        Base.call(this)

        // Init
        this.init()
    }

    MLNestedForm.prototype = Object.create(BaseProto)
    MLNestedForm.prototype.constructor = MLNestedForm

    MLNestedForm.DEFAULTS = {
        switchHandler: null,
        defaultLocale: 'en'
    }

    MLNestedForm.prototype.init = function() {
        this.$el.multiLingual()

        this.$el.on('setLocale.oc.multilingual', this.proxy(this.onSetLocale))

        this.$el.one('dispose-control', this.proxy(this.dispose))
    }

    MLNestedForm.prototype.dispose = function() {
        this.$el.off('setLocale.oc.multilingual', this.proxy(this.onSetLocale))

        this.$el.off('dispose-control', this.proxy(this.dispose))

        this.$el.removeData('oc.mlNestedForm')

        this.$selector = null
        this.$locale = null
        this.locale = null
        this.$el = null

        this.options = null

        BaseProto.dispose.call(this)
    }

    MLNestedForm.prototype.onSetLocale = function(e, locale, localeValue) {
        var self = this,
            previousLocale = this.locale

        this.$el
            .addClass('loading-indicator-container size-form-field')
            .loadIndicator()

        this.locale = locale
        this.$locale.val(locale)

        this.$el.request(this.options.switchHandler, {
            data: {
                _nestedform_previous_locale: previousLocale,
                _nestedform_locale: locale
            },
            success: function(data) {
                self.$el.multiLingual('setLocaleValue', data.updateValue, data.updateLocale)
                self.$el.loadIndicator('hide')
                this.success(data)
            }
        })
    }

    // MLNESTEDFORM PLUGIN DEFINITION
    // ============================

    var old = $.fn.mlNEstedForm

    $.fn.mlNestedForm = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result
        this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.mlNestedForm')
            var options = $.extend({}, MLNestedForm.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.mlNestedForm', (data = new MLNestedForm(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : this
    }

    $.fn.mlNestedForm.Constructor = MLNestedForm

    // MLNESTEDFORM NO CONFLICT
    // =================

    $.fn.mlNestedForm.noConflict = function () {
        $.fn.MLNestedForm = old
        return this
    }

    // MLNESTEDFORM DATA-API
    // ===============

    $(document).render(function () {
        $('[data-control="mlnestedform"]').mlNestedForm()
    })

}(window.jQuery);
