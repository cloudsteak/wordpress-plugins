/**
 * CloudMentor Events - Frontend JavaScript
 *
 * @package CloudMentor_Events
 */

(function($) {
    'use strict';

    /**
     * CloudMentor Events Handler
     */
    var CMEEvents = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initAccessibility();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Click handler for event headers
            $(document).on('click', '.cme-event-header', this.toggleEvent);

            // Keyboard handler for accessibility
            $(document).on('keydown', '.cme-event-header', this.handleKeyboard);
        },

        /**
         * Toggle event details
         *
         * @param {Event} e Click event
         */
        toggleEvent: function(e) {
            e.preventDefault();

            var $header = $(this);
            var $item = $header.closest('.cme-event-item');
            var $details = $item.find('.cme-event-details');
            var isExpanded = $item.hasClass('is-expanded');

            // Update ARIA attributes
            $header.attr('aria-expanded', !isExpanded);
            $details.attr('aria-hidden', isExpanded);

            // Toggle class
            $item.toggleClass('is-expanded');

            // Trigger custom event for extensibility
            $(document).trigger('cme:toggle', {
                item: $item,
                expanded: !isExpanded
            });
        },

        /**
         * Handle keyboard navigation
         *
         * @param {Event} e Keydown event
         */
        handleKeyboard: function(e) {
            // Enter or Space to toggle
            if (e.keyCode === 13 || e.keyCode === 32) {
                e.preventDefault();
                $(this).trigger('click');
            }

            // Arrow keys for navigation
            var $current = $(this).closest('.cme-event-item');
            var $target = null;

            if (e.keyCode === 38) { // Up arrow
                $target = $current.prev('.cme-event-item');
            } else if (e.keyCode === 40) { // Down arrow
                $target = $current.next('.cme-event-item');
            }

            if ($target && $target.length) {
                e.preventDefault();
                $target.find('.cme-event-header').focus();
            }
        },

        /**
         * Initialize accessibility features
         */
        initAccessibility: function() {
            // Set initial ARIA states
            $('.cme-event-header').each(function() {
                var $header = $(this);
                var $item = $header.closest('.cme-event-item');
                var $details = $item.find('.cme-event-details');
                var isExpanded = $item.hasClass('is-expanded');

                // Generate unique ID for details
                var detailsId = 'cme-details-' + $item.data('event-id');
                $details.attr('id', detailsId);

                // Set ARIA attributes
                $header.attr({
                    'aria-expanded': isExpanded,
                    'aria-controls': detailsId
                });

                $details.attr('aria-hidden', !isExpanded);
            });
        },

        /**
         * Expand all events
         */
        expandAll: function() {
            $('.cme-event-item').each(function() {
                var $item = $(this);
                if (!$item.hasClass('is-expanded')) {
                    $item.find('.cme-event-header').trigger('click');
                }
            });
        },

        /**
         * Collapse all events
         */
        collapseAll: function() {
            $('.cme-event-item.is-expanded').each(function() {
                $(this).find('.cme-event-header').trigger('click');
            });
        }
    };

    // Initialize on DOM ready
    $(document).ready(function() {
        CMEEvents.init();
    });

    // Expose to global scope for external access
    window.CMEEvents = CMEEvents;

})(jQuery);
