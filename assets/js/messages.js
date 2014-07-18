/*
 * Scripts for the Messages controller.
 */
+function ($) { "use strict";

    var TranslateMessages = function() {

        this.$form = null
        this.toInput = null
        this.fromInput = null
        this.gridElement = null

        this.setGridElement = function(el) {
            this.gridElement = el
            this.$form = $('#messagesForm')
            this.fromInput = this.$form.find('input[name=locale_from]')
            this.toInput = this.$form.find('input[name=locale_to]')
        }

        this.toggleDropdown = function(el) {
            setTimeout(function(){ $(el).dropdown('toggle') }, 1)
            return false
        }

        this.setLanguage = function(type, code) {
            if (type == 'to')
                this.toInput.val(code)
            else if (type == 'from')
                this.fromInput.val(code)

            this.refreshGrid()
            return false
        }

        this.swapLanguages = function() {
            var from = this.fromInput.val(),
                to  = this.toInput.val()

            this.fromInput.val(to)
            this.toInput.val(from)
            this.refreshGrid()
        }

        this.refreshGrid = function() {
            this.gridElement.dataGrid('setData', [])
            this.$form.request('onRefresh')
        }

    }

    $.translateMessages = new TranslateMessages;

}(window.jQuery);