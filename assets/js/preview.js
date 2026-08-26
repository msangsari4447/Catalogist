/**
 * Catalogist Preview JavaScript
 *
 * Handles preview interactions: orientation toggle, print, close.
 *
 * @package Catalogist
 */

(function ($) {
    'use strict';

    var CatalogistPreview = {
        orientation: 'portrait',
        paperEl: null,
        orientationBtn: null,

        /**
         * Initialize preview interactions.
         */
        init: function () {
            this.paperEl = $('.catalogist-preview-paper');
            this.orientationBtn = $('.catalogist-preview-btn-orientation');

            if (this.paperEl.length) {
                this.orientation = this.paperEl.hasClass('catalogist-preview-paper-landscape') ? 'landscape' : 'portrait';
            }

            this.bindEvents();
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function () {
            // Orientation toggle.
            this.orientationBtn.on('click', $.proxy(this.toggleOrientation, this));

            // Print button.
            $('.catalogist-preview-btn-print').on('click', $.proxy(this.triggerPrint, this));

            // Close button.
            $('.catalogist-preview-btn-close').on('click', $.proxy(this.closePreview, this));

            // Keyboard shortcuts.
            $(document).on('keydown', $.proxy(this.handleKeyboard, this));
        },

        /**
         * Toggle between portrait and landscape.
         */
        toggleOrientation: function () {
            var self = this;
            var newOrientation = 'portrait' === self.orientation ? 'landscape' : 'portrait';
            var $paper = self.paperEl;

            $paper.removeClass('catalogist-preview-paper-portrait catalogist-preview-paper-landscape')
                  .addClass('catalogist-preview-paper-' + newOrientation);

            // Update data attribute on wrapper.
            $('.catalogist-preview-page').attr('data-preview-orientation', newOrientation);

            // Update button text.
            self.orientationBtn.attr('data-current', newOrientation);
            self.orientationBtn.text('Switch to ' + (newOrientation.charAt(0).toUpperCase() + newOrientation.slice(1)));

            self.orientation = newOrientation;

            // Trigger browser resize to recalculate pagination hints.
            $(window).trigger('resize');
        },

        /**
         * Trigger browser print dialog.
         */
        triggerPrint: function () {
            window.print();
        },

        /**
         * Close preview and go back.
         */
        closePreview: function () {
            window.history.back();
        },

        /**
         * Handle keyboard shortcuts.
         *
         * @param {KeyboardEvent} e
         */
        handleKeyboard: function (e) {
            // P for print (when not in input).
            if (e.key === 'p' && !$(e.target).is('input, textarea, select')) {
                e.preventDefault();
                this.triggerPrint();
                return;
            }

            // Esc to close.
            if (e.key === 'Escape') {
                e.preventDefault();
                this.closePreview();
                return;
            }

            // L for landscape toggle.
            if (e.key === 'l' && !$(e.target).is('input, textarea, select')) {
                e.preventDefault();
                this.toggleOrientation();
                return;
            }
        }
    };

    $(document).ready(function () {
        CatalogistPreview.init();
    });

})(jQuery);
