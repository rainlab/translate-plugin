/*
 * MLRepeater plugin
 * 
 * Data attributes:
 * - data-control="mlrepeater" - enables the plugin on an element
 * - data-textarea-element="textarea#id" - an option with a value
 *
 * JavaScript API:
 * $('a#someElement').mlRepeater({ option: 'value' })
 *
 */

+function ($) { "use strict";

    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype

    // MLREPEATER CLASS DEFINITION
    // ============================

    var MLRepeater = function(element, options) {
        this.options   = options
        this.$el       = $(element)
        // this.$textarea = $(options.textareaElement)
        this.$repeater = $('> .field-repeater', this.$el)
        this.$mlButton = $('> .ml-btn', this.$el)
        this.$selector = $('[data-locale-dropdown]', this.$el)
        this.$activeItem = null
        this.$activeButton = null
        this.$mapInput = $('[data-repeater-map]', this.$el)
        this.indexMap = {}

        $.oc.foundation.controlUtils.markDisposable(element)
        Base.call(this)

        // Init
        this.init()
    }

    MLRepeater.prototype = Object.create(BaseProto)
    MLRepeater.prototype.constructor = MLRepeater

    MLRepeater.DEFAULTS = {
        // textareaElement: null,
        // placeholderField: null,
        switchHandler: null,
        defaultLocale: 'en'
    }

    MLRepeater.prototype.init = function() {
        this.$el.multiLingual()
        this.$mlButton.hide()
        this.addButtonsToItems()
        this.rebuildIndexMap()

        $(document).on('render', this.proxy(this.onRender))

        this.$el.on('setLocale.oc.multilingual', this.proxy(this.onSetLocale))

        this.$el.one('dispose-control', this.proxy(this.dispose))
    }

    MLRepeater.prototype.dispose = function() {

        $(document).off('render', this.proxy(this.onRender))

        this.$el.off('setLocale.oc.multilingual', this.proxy(this.onSetLocale))

        this.$el.off('dispose-control', this.proxy(this.dispose))

        this.$el.removeData('oc.mlRepeater')

        // this.$textarea = null
        this.$repeater = null
        this.$buttonTemplate = null
        this.$el = null

        this.options = null

        BaseProto.dispose.call(this)
    }

    MLRepeater.prototype.onRender = function() {
        this.addButtonsToItems()
        this.rebuildIndexMap()
    }

    MLRepeater.prototype.cloneMlButton = function() {
        var self = this,
            $btn = this.$mlButton.clone()

        $btn
            .on('click', function() {
                self.$activeButton = $btn
                self.$activeItem = $btn.closest('.field-repeater-item')
                self.$selector.appendTo(self.$activeItem)
            })
            .show()

        return $btn
    }

    MLRepeater.prototype.rebuildIndexMap = function() {
        var self = this

        this.indexMap = {}

        $('>ul>li.field-repeater-item', this.$repeater).each(function() {
            self.indexMap[$(this).data('repeater-index')] = $(this).data('repeater-active-locale')
        })

        this.$mapInput.val(JSON.stringify(this.indexMap))
    }

    MLRepeater.prototype.addButtonsToItems = function() {
        var self = this

        $('>ul>li.field-repeater-item:not(.dropdown)', this.$repeater).each(function() {
            $(this)
                .addClass('dropdown')
                .append(self.cloneMlButton())
                .data('repeater-active-locale', self.options.defaultLocale)
        })

        // this.$mlButton.hide()
    }

    MLRepeater.prototype.onSetLocale = function(e, locale, localeValue) {
        var self = this,
            index = this.$activeItem.data('repeater-index'),
            previousLocale = this.$activeItem.data('repeater-active-locale')

        this.$activeItem.data('repeater-active-locale', locale)
        this.$activeButton.text(locale)
        this.rebuildIndexMap()

        this.$activeItem.request(this.options.switchHandler, {
            data: {
                repeater_index: index,
                repeater_locale: locale,
                repeater_previous_locale: previousLocale
            },
            success: function(data) {
                $('.field-repeater-form', self.$activeItem).html(data.formFields)

                self.$el.multiLingual('setLocaleValue', data.updateValue, data.updateLocale)
            }
        })
    }

    // MLRepeater.prototype.onSyncContent = function(ev, richeditor, value) {
    //     this.$el.multiLingual('setLocaleValue', value.html)
    // }

    // MLREPEATER PLUGIN DEFINITION
    // ============================

    var old = $.fn.mlRepeater

    $.fn.mlRepeater = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result
        this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.mlRepeater')
            var options = $.extend({}, MLRepeater.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.mlRepeater', (data = new MLRepeater(this, options)))
            if (typeof option == 'string') result = data[option].apply(data, args)
            if (typeof result != 'undefined') return false
        })

        return result ? result : this
    }

    $.fn.mlRepeater.Constructor = MLRepeater

    // MLREPEATER NO CONFLICT
    // =================

    $.fn.mlRepeater.noConflict = function () {
        $.fn.mlRepeater = old
        return this
    }

    // MLREPEATER DATA-API
    // ===============

    $(document).render(function () {
        $('[data-control="mlrepeater"]').mlRepeater()
    })

}(window.jQuery);
