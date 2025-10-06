/**
 * Scripts for the Ahoi API admin pages.
 *
 * @version 1.0.1
 */
(function($) {
    'use strict';

    $(function() {

        // Updated function to convert a string to a slug
        function stringToSlug(str, useUnderscore = false) {
            str = str.replace(/^\s+|\s+$/g, ''); // trim
            str = str.toLowerCase();

            // Replace non-alphanumeric characters with a separator
            var separator = useUnderscore ? '_' : '-';
            var regex = useUnderscore ? /[^a-z0-9_]/g : /[^a-z0-9-]/g;
            
            str = str.replace(/\s+/g, separator); // replace spaces with separator
            str = str.replace(regex, ''); // remove invalid chars (this is a simplified approach)
            str = str.replace(new RegExp(separator + '+', 'g'), separator); // replace multiple separators with a single one

            return str;
        }

        // Auto-generate slug for creating TABLES (uses hyphen "-")
        $('#table_name').on('keyup', function() {
            var slug = stringToSlug($(this).val());
            $('#table_slug').val(slug);
        });
        
        // Auto-generate slug for creating FIELDS (uses underscore "_")
        $('#field_name').on('keyup', function() {
            var slug = stringToSlug($(this).val(), true); // Pass true to use underscores
            $('#field_slug').val(slug);
        });

    });

})(jQuery);