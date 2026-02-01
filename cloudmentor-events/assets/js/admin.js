/**
 * CloudMentor Events - Admin JavaScript
 *
 * @package CloudMentor_Events
 */

(function($) {
    'use strict';

    /**
     * Admin functionality
     */
    var CMEAdmin = {

        /**
         * Initialize
         */
        init: function() {
            this.initDatepicker();
            this.initTitleValidation();
        },

        /**
         * Initialize jQuery UI Datepicker
         */
        initDatepicker: function() {
            if ($.fn.datepicker) {
                $('.cme-datepicker').datepicker({
                    dateFormat: 'yy.mm.dd.',
                    firstDay: 1,
                    changeMonth: true,
                    changeYear: true,
                    yearRange: '-1:+5',
                    minDate: new Date(),
                    showOtherMonths: true,
                    selectOtherMonths: true,
                    // Hungarian locale
                    monthNames: ['Január', 'Február', 'Március', 'Április', 'Május', 'Június',
                                 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December'],
                    monthNamesShort: ['Jan', 'Feb', 'Már', 'Ápr', 'Máj', 'Jún',
                                      'Júl', 'Aug', 'Szep', 'Okt', 'Nov', 'Dec'],
                    dayNames: ['Vasárnap', 'Hétfő', 'Kedd', 'Szerda', 'Csütörtök', 'Péntek', 'Szombat'],
                    dayNamesMin: ['V', 'H', 'K', 'Sze', 'Cs', 'P', 'Szo'],
                    dayNamesShort: ['Vas', 'Hét', 'Kedd', 'Szer', 'Csüt', 'Pén', 'Szom']
                });
            }
        },

        /**
         * Initialize title length validation
         */
        initTitleValidation: function() {
            var $titleField = $('#title');
            var maxLength = 50;

            if ($titleField.length) {
                // Add character counter
                var $counter = $('<span class="cme-title-counter"></span>');
                $titleField.after($counter);

                // Update counter on input
                $titleField.on('input', function() {
                    var length = $(this).val().length;
                    var remaining = maxLength - length;

                    $counter.text(length + '/' + maxLength);

                    if (remaining < 0) {
                        $counter.css('color', '#d63638');
                    } else if (remaining < 10) {
                        $counter.css('color', '#dba617');
                    } else {
                        $counter.css('color', '#50575e');
                    }
                });

                // Trigger initial count
                $titleField.trigger('input');
            }
        }
    };

    // Initialize on DOM ready
    $(document).ready(function() {
        CMEAdmin.init();
    });

})(jQuery);
