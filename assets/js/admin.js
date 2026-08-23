/**
 * Catalogist Admin Scripts
 *
 * @package Catalogist
 */

(function ($) {
    'use strict';

    var CatalogistAdmin = {
        init: function () {
            this.bindEvents();
        },

        bindEvents: function () {
            $(document).on('click', '.catalogist-notice .notice-dismiss', this.dismissNotice);
        },

        dismissNotice: function (e) {
            e.preventDefault();
            $(this).closest('.catalogist-notice').fadeOut(300, function () {
                $(this).remove();
            });
        },

        showNotice: function (message, type) {
            type = type || 'info';
            var notice = $('<div class="notice catalogist-notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').first().after(notice);
            return notice;
        },

        ajaxRequest: function (action, data, callback) {
            data = data || {};
            data.action = action;
            data.nonce = catalogistAdmin.nonce;

            $.post(catalogistAdmin.ajaxUrl, data, function (response) {
                if (typeof callback === 'function') {
                    callback(response);
                }
            });
        }
    };

    $(document).ready(function () {
        CatalogistAdmin.init();
    });

})(jQuery);
