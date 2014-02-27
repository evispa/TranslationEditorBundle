$(document).ready(function () {

    $(document).on('click', '.translation_lang_select a', function () {
        var $a = $(this);
        var lang = $a.data('lang');
        $a.parents('div.translation_lang_select:first').find('a').removeClass('c0 a0 fwb').addClass('a2');
        $a.addClass('c0 a0 fwb').removeClass('a2');
        var $forms = $a.parents('.other_lang_wrap:first').find('.translation_lang_forms');
        if (!lang) {
            $forms.find('.form_wrap').removeClass('dn');
            $forms.find('.language_label').removeClass('dn');
        } else {
            $forms.find('.language_label').addClass('dn');
            $forms.find('.form_wrap').addClass('dn');
            $forms.find('.form_wrap[data-lang="' + lang + '"]').removeClass('dn');
        }
        $.cookie("current_selected_translation_lang", !lang ? '__all__' : lang, {path: '/', expires: 7});

        $('.translation_editor_all_errors').remove();
    });

    /*
     * Is translatable changer
     */
    var change_translations_locale_visibility = function ($wrap, is_translatable) {
        if (is_translatable === true) {
            $('.no_lang_wrap', $wrap).addClass('dn');
            $('.no_lang_wrap input', $wrap).val('');
            $('.other_lang_wrap', $wrap).removeClass('dn');
        } else {
            $('.no_lang_wrap', $wrap).removeClass('dn');
            $('.other_lang_wrap', $wrap).addClass('dn');
            $('.other_lang_wrap input', $wrap).val('');
        }
    };

    var translations_locale_visibility_trigger = function ($el) {
        var $wrap = $('#' + $el.data('translations-name') + '_wrap');

        if ($el.is(':checked')) {
            change_translations_locale_visibility($wrap, true);
        } else {
            change_translations_locale_visibility($wrap, false);
        }
    };

    $('input[data-is-translatable-changer=1]').each(function () {
        translations_locale_visibility_trigger($(this));
    }).change(function () {
        translations_locale_visibility_trigger($(this));
    });

    $('.translation_editor_remove_all_errors').click(function () {
        $('.translation_editor_all_errors').remove();
    });
});