(function (Drupal) {
  'use strict';

  Drupal.behaviors.PhoneInternational = {

    /**
     * Allow to alter phone library options.
     *
     * @return void
     */
    alterPhoneLibraryOptions: function(field, options) {},

    attach: function (context, settings) {

      // Do something like jquery.once. Be sure that this attach only runs once.
      var fields = document.querySelectorAll('.phone_international-number');
      if (fields.length) {
        // Loop each one and load the library.
        fields.forEach(function (field) {
          if (field.classList.contains('jsIntPhone')) {
            return;
          }
          field.classList.add('jsIntPhone');
          // As we are using attach form, check first if its already loaded.
          var parent = field.parentElement;
          if (!parent.classList.contains('intl-tel-input')) {
            var country = field.getAttribute('data-country');
            var geolocation = field.getAttribute('data-geo');
            var exclude = field.getAttribute('data-exclude');
            var preferred = field.getAttribute('data-preferred');
            var options = {
              initialCountry: (geolocation > 0) ? 'auto' : country,
              excludeCountries: exclude ? exclude.split('-') : [],
              geoIpLookup: function (callback) {
                if (typeof aja === 'function') {
                  aja().url('https://extreme-ip-lookup.com/json/').data({}).on('success', function (resp) {
                    var countryCode = (resp && resp.countryCode) ? resp.countryCode : country;
                    callback(countryCode);
                  }).go();
                }
              },
              preferredCountries: preferred ? preferred.split('-') : [],
              nationalMode: true,
              autoPlaceholder: 'aggressive',
              formatOnDisplay: true,
              hiddenInput: "full_number",
              utilsScript: drupalSettings.phone_international.path + '/js/utils.js',
            };

            Drupal.behaviors.PhoneInternational.alterPhoneLibraryOptions(field, options);

            // Initialize the phone library.
            var iti = window.intlTelInput(field, options);

            // Set drupal selector and value for the hidden input and delete the one provided by form element.
            var drupal_selector = field.parentNode.nextElementSibling.getAttribute("data-drupal-selector");
            var value = field.parentNode.nextElementSibling.getAttribute("value");
            field.parentNode.nextElementSibling.remove();
            field.nextElementSibling.setAttribute("data-drupal-selector", drupal_selector);
            field.nextElementSibling.setAttribute("value", value);

            // Add event lister to update the hidden input value on keyup to make sure that the hidden input
            // value is set for all ajax request (the intl-tel-input library update the value on form submit only)
            field.addEventListener('keyup', function (e) {
              field.nextElementSibling.value = iti.getNumber();
            });

          }
        });

      }
    }
  };

})(Drupal);
