/**
 * PodBaz Robot Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize admin functionality
        initPodBazRobot();
    });

    /**
     * Initialize PodBaz Robot admin
     */
    function initPodBazRobot() {
        // Handle settings form validation
        $('form[action="options.php"]').on('submit', function(e) {
            var interval = $('input[name="podbazrobot_settings[interval]"]').val();
            
            if (interval && (interval < 1 || interval > 1440)) {
                e.preventDefault();
                alert('Please enter a valid interval between 1 and 1440 minutes.');
                return false;
            }
        });

        // Add confirmation for critical actions
        $('.podbazrobot-clear-logs').on('click', function(e) {
            if (!confirm('Are you sure you want to clear all logs?')) {
                e.preventDefault();
                return false;
            }
        });
    }

})(jQuery);
