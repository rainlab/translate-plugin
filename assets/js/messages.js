/*
 * Scripts for the Messages controller.
 */
+function ($) { "use strict";

    var TranslateMessages = function() {
        var self = this

        this.$form = null

        /*
         * Input with the "from" locale value
         */
        this.fromInput = null

        /*
         * Template for the "from" header (title)
         */
        this.fromHeader = null

        /*
         * Input with the "to" locale value
         */
        this.toInput = null

        /*
         * Template for the "to" header (title)
         */
        this.toHeader = null

        /*
         * The element with .dataGrid() bound to it
         */
        this.tableElement = null

        /*
         * Hide translated strings (show only from the empty data set)
         */
        this.hideTranslated = false

        /*
         * Data sets, complete and untranslated (empty)
         */
        this.emptyDataSet = null
        this.dataSet = null

        $(document).on('change', '#hideTranslated', function(){
            self.toggleTranslated($(this).is(':checked'))
            self.filterDataSet()
        });

        this.toggleTranslated = function(isHide) {
            // this.tableElement.dataGrid('deselect')
            this.hideTranslated = isHide
            this.setTitleContents()
        }

        this.filterDataSet = function() {
            if (!this.hideTranslated) {
                if (this.dataSet) this.tableElement.dataGrid('setData', this.dataSet)
                return
            }

            this.dataSet = this.tableElement.dataGrid('getData')
            this.emptyDataSet = $.grep(this.dataSet, function(obj, index){
                return !obj.to
            })

            this.tableElement.dataGrid('setData', this.emptyDataSet)
        }

        this.setTitleContents = function(fromEl, toEl) {
            if (fromEl) this.fromHeader = $(fromEl)
            if (toEl) this.toHeader = $(toEl)
            if (!this.tableElement) return

            var $headers = $('table.headers th', this.tableElement)
            $headers.eq(0).html(this.fromHeader.html())
            $headers.eq(1).html(Mustache.render(this.toHeader.html(), { hideTranslated: this.hideTranslated } ))
        }

        this.setTableElement = function(el) {
            this.tableElement = $(el)
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

            this.toggleTranslated(false)
            this.fromInput.val(to)
            this.toInput.val(from)
            this.refreshGrid()
        }

        this.refreshGrid = function() {
            // this.tableElement.dataGrid('setData', [])
            this.$form.request('onRefresh')
        }

    }

    $.translateMessages = new TranslateMessages;

}(window.jQuery);