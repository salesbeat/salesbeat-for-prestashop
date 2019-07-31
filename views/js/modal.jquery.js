/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    Goryachev Dmitry    <dariusakafest@gmail.com>
 * @copyright 2007-2018 Goryachev Dmitry
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

(function () {
    var classStageModalJQuery = 'stage_modal_jquery';
    var classModalJQuery = 'modal_jquery';
    var uid = 1;

    $.fn.setCenterPosAbsBlockModal = function ()
    {
        var $this = $(this);
        centerPosAbs($this);
        $(window).resize(function () {
            centerPosAbs($this);
        });

        function centerPosAbs($this) {
            var offsetElemTop = 20;
            var scrollTop = $(document).scrollTop();
            var elemWidth = $this.width();
            var windowWidth = $(window).width();
            $this.css({
                top: ($this.height() > $(window).height()
                    ? scrollTop + offsetElemTop
                    : scrollTop + (($(window).height()-$this.height())/2)),
                left: ((windowWidth-elemWidth)/2)
            });
        }
    };

    function getUID() {
        return ++uid;
    }

    $.createModalSB = function (options) {
        var uid = getUID();

        var defaults = {
            content: '',
            title: '',
            buttons: {},
            onLoad: function (functions) {

            },
            onClose: function (functions) {

            }
        };
        defaults = $.extend(defaults, options);

        var functions = {
            modalClose: function () {
                defaults.onClose(functions);
                $('.' + classModalJQuery+'[data-uid="'+uid+'"]').remove();
                $('.' + classStageModalJQuery+'[data-uid="'+uid+'"]').remove();
            },
            pos: function () {
                $('.'+classModalJQuery).setCenterPosAbsBlockModal();
            }
        };

        (function () {
            var $stageModalJQuery = $('<div></div>').addClass(classStageModalJQuery);
            $stageModalJQuery.attr('data-uid', uid);

            var $form = $('<div></div>').addClass(classModalJQuery);
            $form.attr('data-uid', uid);

            var $formHeader = '<a href="#" data-uid="'+uid+'" class="modal_close">' +
                '<i class="icon-remove"></i>' +
                '</a>';
            if (defaults.title) {
                $formHeader = $('<div class="modal_header">' +
                    (defaults.title ? '<h3>'+defaults.title+'</h3>' : '') +
                    '<a href="#" class="modal_close" data-uid="'+uid+'">' +
                    '<i class="icon-remove"></i>' +
                    '</a>' +
                    '</div>');
            }

            var $formContent = $('<div class="modal_content"></div>');
            $formContent.html(defaults.content);

            var $formFooter = '';

            var eventListeners = [];
            for (var name  in defaults.buttons) {
                if (!$formContent) {
                    $formFooter = $('<div class="modal_footer text-right"></div>');
                }

                var date = new Date();
                var id = 'button' + date.getTime();
                $formFooter.append('<button type="button" id="'+id+'" class="btn btn-default">'+name+'</button>');
                eventListeners.push(function () {
                    $('#'+id).click(defaults.buttons[name]);
                });
            }

            $form.append($formHeader).append($formContent).append($formFooter);

            $('body').append($stageModalJQuery).append($form);
            functions.pos();

            $('.modal_close[data-uid="'+uid+'"], .'+classStageModalJQuery+'[data-uid="'+uid+'"]').live('click', function (e) {
                e.preventDefault();
                functions.modalClose();
            });

            $.each(eventListeners, function (index, func) {
                func();
            });
            defaults.onLoad(functions);
        })(uid);

        return functions;
    };
})();