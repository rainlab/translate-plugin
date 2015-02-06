
function initMLRichEditor(el, textarea) {

    var $el = $(el),
        $textarea = $(textarea)

    $el.on('setLocale.oc.multilingual', function(e, locale, localeValue){
        if (typeof localeValue === 'string' && $textarea.data('redactor')) {
            $textarea.redactor('code.set', localeValue);
        }
    })

    $textarea.on('sanitize.oc.richeditor', function(){
        $el.multiLingual('setLocaleValue', this.value)
    })

}
