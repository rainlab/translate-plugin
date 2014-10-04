(function ($) {
    'use strict';

    window.RedactorPlugins = window.RedactorPlugins || {}

    var MLSwitcher = function (redactor) {
        this.redactor = redactor
        this.init()
    }

    MLSwitcher.prototype = {

        init: function () {

        }

    }

    window.RedactorPlugins.mlswitcher = {
        init: function () {
            this.mlswitcher = new MLSwitcher(this)

            // This is a work in progress
            this.buttonAddBefore('video', 'image', 'MLSwitcher', $.proxy(function () {

                alert('hi')

            }, this))

            this.buttonGet('mlswitcher')
                .addClass('redactor_btn_image')
                .removeClass('redactor-btn-image')
        }
    }

}(jQuery));