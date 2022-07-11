/*
 * MLMediaFinder plugin
 *
 * Data attributes:
 * - data-control="mlmediafinder" - enables the plugin on an element
 * - data-option="value" - an option with a value
 *
 * JavaScript API:
 * $('a#someElement').mlMediaFinder({ option: 'value' })
 *
 * Dependences:
 * - mediafinder (mediafinder.js)
 */

+function($) { "use strict";
    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype

    // MLMEDIAFINDER CLASS DEFINITION
    // ============================

    var MLMediaFinder = function(element, options) {
        this.options   = options
        this.$el       = $(element)
        this.$mediafinder = $('[data-control=mediafinder]', this.$el)
        this.$dataLocker = $('[data-data-locker]', this.$el)
        this.isMulti = this.$mediafinder.hasClass('is-multi')

        $.oc.foundation.controlUtils.markDisposable(element)
        Base.call(this)
        this.init()
    }

    MLMediaFinder.prototype = Object.create(BaseProto)
    MLMediaFinder.prototype.constructor = MLMediaFinder

    MLMediaFinder.DEFAULTS = {
        placeholderField: null,
        defaultLocale: 'en',
        mediaPath: '/',
    }

    MLMediaFinder.prototype.init = function() {

        this.$el.multiLingual()
        this.$el.on('setLocale.oc.multilingual', this.proxy(this.onSetLocale))
        this.$el.one('dispose-control', this.proxy(this.dispose))

        // Listen for change event from mediafinder
        this.$dataLocker.on('change', this.proxy(this.setValue))

        // Stop here for preview mode
        if (this.options.isPreview) {
            return;
        }
    }

    MLMediaFinder.prototype.dispose = function() {
        this.$el.off('setLocale.oc.multilingual', this.proxy(this.onSetLocale));
        this.$el.off('dispose-control', this.proxy(this.dispose));
        this.$dataLocker.off('change', this.proxy(this.setValue));

        this.$el.removeData('oc.mlMediaFinder');

        this.$dataLocker = null;
        this.$mediafinder = null;
        this.$el = null;

        // In some cases options could contain callbacks,
        // so it's better to clean them up too.
        this.options = null;

        BaseProto.dispose.call(this)
    }

    MLMediaFinder.prototype.setValue = function(e) {
        var mediafinder = this.$mediafinder.data('oc.mediaFinder'),
            value = mediafinder.getValue();

        if (value) {
            if (this.isMulti) {
                value = JSON.stringify(value);
            }
            else {
                value = value[0];
            }
        }

        this.setPath(value);
    }

    MLMediaFinder.prototype.onSetLocale = function(e, locale, localeValue) {
        this.setPath(localeValue)
    }

    MLMediaFinder.prototype.setPath = function(localeValue) {
        if (typeof localeValue === 'string') {
            var self = this,
                isMulti = this.isMulti,
                mediaFinder = this.$mediafinder.data('oc.mediaFinder'),
                items = [],
                localeValueArr = [];

            try {
                localeValueArr = JSON.parse(localeValue);
                if (!$.isArray(localeValueArr)) {
                    localeValueArr = [localeValueArr];
                }
            }
            catch(e) {
                isMulti = false;
            }

            mediaFinder.$filesContainer.empty();

            if (isMulti) {
                $.each(localeValueArr, function(k, v) {
                    if (v) {
                        items.push({
                            path: v,
                            publicUrl: self.options.mediaPath + v,
                            thumbUrl: self.options.mediaPath + v,
                            title: v.substring(1)
                        });
                    }
                });
            }
            else {
                if (localeValue) {
                    items = [{
                        path: localeValue,
                        publicUrl: this.options.mediaPath + localeValue,
                        thumbUrl: this.options.mediaPath + localeValue,
                        title: localeValue.substring(1)
                    }];
                }
            }

            mediaFinder.addItems(items);
            mediaFinder.evalIsPopulated();
            mediaFinder.evalIsMaxReached();

            this.$el.multiLingual('setLocaleValue', localeValue);
        }
    }

    // MLMEDIAFINDER PLUGIN DEFINITION
    // ============================

    var old = $.fn.mlMediaFinder

    $.fn.mlMediaFinder = function (option) {
        var args = Array.prototype.slice.call(arguments, 1), result
        this.each(function () {
            var $this   = $(this)
            var data    = $this.data('oc.mlMediaFinder')
            var options = $.extend({}, MLMediaFinder.DEFAULTS, $this.data(), typeof option == 'object' && option)
            if (!data) $this.data('oc.mlMediaFinder', (data = new MLMediaFinder(this, options)))
            if (typeof option === 'string') result = data[option].apply(data, args)
            if (typeof result !== 'undefined') return false
        })

        return result ? result : this
    }

    $.fn.mlMediaFinder.Constructor = MLMediaFinder

    // MLMEDIAFINDER NO CONFLICT
    // =================

    $.fn.mlMediaFinder.noConflict = function () {
        $.fn.mlMediaFinder = old
        return this
    }

    // MLMEDIAFINDER DATA-API
    // ===============

    $(document).render(function () {
        $('[data-control="mlmediafinder"]').mlMediaFinder()
    });
}(window.jQuery);
