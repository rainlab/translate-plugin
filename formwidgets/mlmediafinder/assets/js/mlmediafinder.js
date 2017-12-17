/**
 * multilingual media finder
 */
+function($) { "use strict";

    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype

    // MLRICHEDITOR CLASS DEFINITION
    // ============================

    var MLMediaFinder = function(element, options) {
        this.options   = options
        this.$el       = $(element)
        this.$mediafinder = $('[data-control=mediafinder]', this.$el)
        this.$findValue = $('[data-find-value]', this.$el)

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
        if (this.options.isMulti === null) {
            this.options.isMulti = this.$mediafinder.hasClass('is-multi')
        }

        if (this.options.isPreview === null) {
            this.options.isPreview = this.$mediafinder.hasClass('is-preview')
        }

        if (this.options.isImage === null) {
            this.options.isImage = this.$mediafinder.hasClass('is-image')
        }
        this.$el.multiLingual()
        this.$el.on('setLocale.oc.multilingual', this.proxy(this.onSetLocale))
        this.$el.one('dispose-control', this.proxy(this.dispose))

        // Stop here for preview mode
        if (this.options.isPreview)
            return

        this.$el.on('click', '.find-button', this.proxy(this.onClickFindButton))
        this.$el.on('click', '.find-remove-button', this.proxy(this.onClickRemoveButton))

        this.updateLayout();
    }

    MLMediaFinder.prototype.dispose = function() {
        this.$el.off('setLocale.oc.multilingual', this.proxy(this.onSetLocale))
        this.$el.off('click', '.find-button', this.proxy(this.onClickFindButton))
        this.$el.off('click', '.find-remove-button', this.proxy(this.onClickRemoveButton))
        this.$el.off('dispose-control', this.proxy(this.dispose))
        this.$el.removeData('oc.mlMediaFinder')

        this.$findValue = null
        this.$mediafinder = null;
        this.$el = null

        // In some cases options could contain callbacks,
        // so it's better to clean them up too.
        this.options = null

        BaseProto.dispose.call(this)
    }

    MLMediaFinder.prototype.onClickFindButton = function() {
        var self = this

        new $.oc.mediaManager.popup({
            alias: 'ocmediamanager',
            cropAndInsertButton: true,
            onInsert: function(items) {
                if (!items.length) {
                    alert('Please select image(s) to insert.')
                    return
                }

                if (items.length > 1) {
                    alert('Please select a single item.')
                    return
                }

                var path = items[0].path

                //self.evalIsPopulated()
                self.setPath(path);

                this.hide()
            }
        })
    }

    MLMediaFinder.prototype.onClickRemoveButton = function(e) {
        this.setPath('')
    }

    MLMediaFinder.prototype.onSetLocale = function(e, locale, localeValue) {
        this.setPath(localeValue)
    }

    MLMediaFinder.prototype.setPath = function(localeValue) {
        if (typeof localeValue === 'string') {
            this.$findValue = localeValue;

            var path = localeValue ? this.options.mediaPath + localeValue : ''
            //if(this.options.isImage) {
                $('[data-find-image]', this.$mediafinder).attr('src', path)
            //}
            $('[data-find-file-name]', this.$mediafinder).text(localeValue.substring(1))

            // if value is present display image/file, else display open icon for media manager
            this.$mediafinder.toggleClass('is-populated', !!localeValue)

            this.$el.multiLingual('setLocaleValue', localeValue);
        }
    }

    MLMediaFinder.prototype.updateLayout = function() {
        var $btn = $('.ml-btn[data-active-locale]:first', this.$el),
            $dropdown = $('.ml-dropdown-menu[data-locale-dropdown]:first', this.$el)

        $btn.css('top', -28)
        $btn.css('right', 4)
        $dropdown.css('top', 0)
    }

    // MLRICHEDITOR PLUGIN DEFINITION
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

    // MLRICHEDITOR NO CONFLICT
    // =================

    $.fn.mlMediaFinder.noConflict = function () {
        $.fn.mlMediaFinder = old
        return this
    }

    // MLRICHEDITOR DATA-API
    // ===============

    $(document).render(function () {
        $('[data-control="mlmediafinder"]').mlMediaFinder()
    })


}(window.jQuery);