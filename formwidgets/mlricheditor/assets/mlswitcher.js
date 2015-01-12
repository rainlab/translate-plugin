
function initMLRichEditor(el, textarea) {

    var $el = $(el),
        $textarea = $(textarea)

    $el.on('codeSet.oc.richeditor', function(e, locale, localeValue){
        if (typeof localeValue === 'string') {
            $textarea.redactor('code.set', localeValue);
        }
    })

    $textarea.on('keyup', function(){
        $el.multiLingual('setLocaleValue', this.value)
    })

}



// (function ($) {
//     'use strict';

//     window.RedactorPlugins = window.RedactorPlugins || {}

//     var MLSwitcher = function (redactor) {
//         this.redactor = redactor
//         this.init()
//     }

//     MLSwitcher.prototype = {

//         init: function () {

//         }

//     }

//     window.RedactorPlugins.mlswitcher = {
//         init: function () {
//             this.mlswitcher = new MLSwitcher(this)

//             // This is a work in progress
//             this.buttonAddBefore('video', 'image', 'MLSwitcher', $.proxy(function () {

//                 alert('hi')

//             }, this))

//             this.buttonGet('mlswitcher')
//                 .addClass('redactor_btn_image')
//                 .removeClass('redactor-btn-image')
//         }
//     }

// }(jQuery));