/**
 * Event Landing Pages — Time Slot Picker
 *
 * Reads configuration from the global `elpEventConfig` object
 * injected via wp_localize_script().
 *
 * Expected elpEventConfig properties:
 *   restUrl              - REST API base (e.g., /wp-json/elp/v1)
 *   nonce                - WP REST nonce
 *   slug                 - HubSpot meeting slug
 *   timezone             - IANA timezone
 *   targetDate           - YYYY-MM-DD string
 *   ctaLabel             - Submit button text
 *   confirmationMessage  - Custom confirmation text
 *   enableCountryCode    - Boolean toggle
 *   defaultCountryCode   - Default country dial code
 *   countryCodes         - Object of dial codes { "+1": "+1 (US / Canada)", ... }
 */
(function () {
  'use strict';

  var config = window.elpEventConfig || {};
  if (!config.slug) return;

  // Calculate monthOffset dynamically.
  var monthOffset = 0;
  if (config.targetDate) {
    var now = new Date();
    var parts = config.targetDate.split('-').map(Number);
    monthOffset = (parts[0] - now.getFullYear()) * 12 + (parts[1] - 1 - now.getMonth());
    if (monthOffset < 0) monthOffset = 0;
  }

  // DOM references.
  var timeSlotsEl    = document.getElementById('elpTimeSlots');
  var timeSlotsGrid  = document.getElementById('elpTimeSlotsGrid');
  var timeSlotsDate  = document.getElementById('elpTimeSlotsDate');
  var timeSlotsLabel = document.getElementById('elpTimeSlotsLabel');
  var contactForm    = document.getElementById('elpContactForm');
  var selectedBadge  = document.getElementById('elpSelectedTimeBadge');
  var bookingForm    = document.getElementById('elpBookingForm');
  var submitBtn      = document.getElementById('elpSubmitBtn');
  var confirmationEl = document.getElementById('elpConfirmation');
  var spotsNotice    = document.getElementById('elpSpotsNotice');
  var formFieldsEl   = document.getElementById('elpFormFields');

  if (!timeSlotsGrid) return;

  var selectedSlot = null;

  // Form field definitions from HubSpot API response.
  var formFieldsDef = null;

  // Fallback when the API doesn't return field definitions.
  var defaultFormFields = [
    { name: 'firstname', label: 'First Name', required: true, type: 'string' },
    { name: 'lastname',  label: 'Last Name',  required: true, type: 'string' },
    { name: 'email',     label: 'Email',       required: true, type: 'string', fieldType: 'email' },
    { name: 'phone',     label: 'Phone Number', required: false, type: 'phone' },
  ];

  // ---- Helpers ----

  function escapeHtml(str) {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function formatTime(dateStr) {
    return new Date(dateStr).toLocaleTimeString('en-US', {
      hour: 'numeric',
      minute: '2-digit',
      hour12: true,
      timeZone: config.timezone,
    });
  }

  function formatDateHeading(dateStr) {
    if (!dateStr) return 'your selected date';
    return new Date(dateStr + 'T12:00:00').toLocaleDateString('en-US', {
      weekday: 'long',
      month: 'long',
      day: 'numeric',
      timeZone: config.timezone,
    });
  }

  function slotToDateStr(startMs) {
    return new Date(startMs).toLocaleDateString('en-CA', {
      timeZone: config.timezone,
    });
  }

  function showError(message) {
    timeSlotsGrid.innerHTML =
      '<div class="elp-error-message"><p>' + escapeHtml(message) +
      '</p><button>Try Again</button></div>';
    timeSlotsGrid.querySelector('button').addEventListener('click', loadSlots);
    timeSlotsLabel.textContent = 'Unable to load times';
    timeSlotsDate.textContent = '';
  }

  // ---- Dynamic Form Fields ----

  function getInputType(field) {
    if (field.fieldType === 'email' || field.name === 'email') return 'email';
    if (field.type === 'phone' || field.type === 'phonenumber' || field.name === 'phone') return 'tel';
    if (field.type === 'number') return 'number';
    return 'text';
  }

  function isPhoneField(field) {
    return field.type === 'phone' || field.type === 'phonenumber' || field.name === 'phone';
  }

  function renderSingleField(field) {
    var inputType = getInputType(field);
    var required  = field.required ? ' required' : '';
    var fieldId   = 'elpField_' + field.name;
    var label     = field.label || field.name;

    var html = '<div class="elp-field">';
    html += '<label for="' + escapeHtml(fieldId) + '">' + escapeHtml(label) + '</label>';

    if (isPhoneField(field) && config.enableCountryCode && config.countryCodes) {
      html += '<div class="elp-phone-wrapper">';
      html += '<select id="elpCountryCode" class="elp-country-code">';
      var codes = config.countryCodes;
      for (var code in codes) {
        if (codes.hasOwnProperty(code)) {
          var sel = code === config.defaultCountryCode ? ' selected' : '';
          html += '<option value="' + escapeHtml(code) + '"' + sel + '>' + escapeHtml(code) + '</option>';
        }
      }
      html += '</select>';
      html += '<input type="tel" id="' + escapeHtml(fieldId) + '" name="' + escapeHtml(field.name) + '"' + required + '>';
      html += '</div>';
    } else {
      html += '<input type="' + inputType + '" id="' + escapeHtml(fieldId) + '" name="' + escapeHtml(field.name) + '"' + required + '>';
    }

    html += '</div>';
    return html;
  }

  function renderFormFields(fields) {
    if (!formFieldsEl) return;

    var html = '';
    var i = 0;

    while (i < fields.length) {
      var field = fields[i];

      // Pair firstname + lastname side-by-side.
      if (field.name === 'firstname' && i + 1 < fields.length && fields[i + 1].name === 'lastname') {
        html += '<div class="elp-field-row">';
        html += renderSingleField(fields[i]);
        html += renderSingleField(fields[i + 1]);
        html += '</div>';
        i += 2;
      } else {
        html += renderSingleField(field);
        i++;
      }
    }

    formFieldsEl.innerHTML = html;
  }

  // ---- API ----

  function fetchJSON(endpoint, params) {
    var parts = [];
    for (var key in params) {
      if (params.hasOwnProperty(key)) {
        parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(params[key]));
      }
    }
    var url = config.restUrl + endpoint + (parts.length ? '?' + parts.join('&') : '');

    return fetch(url, {
      headers: { 'X-WP-Nonce': config.nonce },
    }).then(function (res) {
      if (!res.ok) throw new Error('HTTP ' + res.status + ' from ' + endpoint);
      return res.json();
    });
  }

  // ---- Render ----

  function renderSlots(availability) {
    // Render the form fields (hidden until a slot is selected).
    renderFormFields(formFieldsDef || defaultFormFields);

    var outer = availability.linkAvailability
      ? availability.linkAvailability.linkAvailabilityByDuration
      : availability.linkAvailabilityByDuration;

    if (!outer || Object.keys(outer).length === 0) {
      showError('No available time slots found. Please check back later.');
      return;
    }

    var durationMs  = Object.keys(outer)[0];
    var bucket      = outer[durationMs];
    var durationMin = Math.round(parseInt(durationMs) / 60000);
    var slots       = bucket.availabilities || [];

    // Group by date.
    var dateMap = {};
    slots.forEach(function (slot) {
      var dateStr = slotToDateStr(slot.startMillisUtc);
      if (!dateMap[dateStr]) dateMap[dateStr] = [];
      dateMap[dateStr].push(slot);
    });

    var dates = Object.keys(dateMap).sort();
    if (config.targetDate) {
      dates = dates.filter(function (d) { return d === config.targetDate; });
    }

    if (dates.length === 0) {
      showError('No slots available for the selected date.');
      return;
    }

    if (dates.length === 1) {
      timeSlotsDate.textContent = formatDateHeading(dates[0]);
    } else {
      timeSlotsDate.textContent = '';
    }
    timeSlotsLabel.textContent = 'Select a ' + durationMin + '-minute slot';

    var totalSlots = 0;
    var html = '';

    dates.forEach(function (date) {
      var daySlots = dateMap[date];
      if (!daySlots || daySlots.length === 0) return;

      if (dates.length > 1) {
        html += '<div class="elp-date-group-header">' + escapeHtml(formatDateHeading(date)) + '</div>';
      }

      daySlots.forEach(function (slot) {
        totalSlots++;
        var timeLabel = formatTime(slot.startMillisUtc);
        html +=
          '<button class="elp-time-slot" ' +
            'data-start="' + slot.startMillisUtc + '" ' +
            'data-duration="' + durationMs + '" ' +
            'data-date="' + date + '"' +
          '>' + escapeHtml(timeLabel) + '</button>';
      });
    });

    timeSlotsGrid.innerHTML = html;

    if (spotsNotice) {
      spotsNotice.innerHTML = '<strong>Limited availability</strong> \u2014 ' + totalSlots + ' slot' + (totalSlots !== 1 ? 's' : '') + ' open. Secure yours today.';
    }

    timeSlotsGrid.querySelectorAll('.elp-time-slot').forEach(function (btn) {
      btn.addEventListener('click', function () { selectSlot(btn); });
    });
  }

  // ---- Selection ----

  function selectSlot(btn) {
    timeSlotsGrid.querySelectorAll('.elp-time-slot').forEach(function (b) {
      b.classList.remove('selected');
    });
    btn.classList.add('selected');

    var startMs   = parseInt(btn.dataset.start);
    var dateStr   = btn.dataset.date;
    var timeLabel = btn.textContent;

    selectedSlot = {
      startMillisUtc: startMs,
      durationMs: parseInt(btn.dataset.duration),
    };

    selectedBadge.innerHTML = escapeHtml(formatDateHeading(dateStr)) + ' at <strong>' + escapeHtml(timeLabel) + '</strong>';
    contactForm.classList.add('visible');

    setTimeout(function () {
      contactForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 100);
  }

  // ---- Booking ----

  function submitBooking(e) {
    e.preventDefault();
    if (!selectedSlot) return;

    var fields     = formFieldsDef || defaultFormFields;
    var formFields = [];
    var firstName  = '';

    // Validate required fields.
    for (var j = 0; j < fields.length; j++) {
      if (!fields[j].required) continue;
      var reqInput = document.getElementById('elpField_' + fields[j].name);
      if (!reqInput || !reqInput.value.trim()) return;
    }

    // Collect all field values.
    for (var i = 0; i < fields.length; i++) {
      var field = fields[i];
      var input = document.getElementById('elpField_' + field.name);
      if (!input) continue;

      var value = input.value.trim();

      // Prepend country code for phone fields.
      if (isPhoneField(field) && value && config.enableCountryCode) {
        var codeEl = document.getElementById('elpCountryCode');
        if (codeEl) {
          value = codeEl.value + value;
        }
      }

      if (field.name === 'firstname') firstName = value;

      if (value) {
        formFields.push({ name: field.name, value: value });
      }
    }

    submitBtn.disabled = true;
    submitBtn.classList.add('loading');

    var payload = {
      slug: config.slug,
      timezone: config.timezone,
      duration: selectedSlot.durationMs,
      startMillisUtc: selectedSlot.startMillisUtc,
      formFields: formFields,
    };

    fetch(config.restUrl + '/book', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': config.nonce,
      },
      body: JSON.stringify(payload),
    })
      .then(function (res) {
        if (!res.ok) {
          return res.json().catch(function () { return {}; }).then(function (err) {
            throw new Error(err.message || 'Booking failed (HTTP ' + res.status + ')');
          });
        }
        return res.json();
      })
      .then(function () {
        var selectedBtn = timeSlotsGrid.querySelector('.elp-time-slot.selected');
        var dateStr     = selectedBtn ? selectedBtn.dataset.date : '';
        var timeLabel   = selectedBtn ? selectedBtn.textContent : '';

        timeSlotsEl.style.display    = 'none';
        contactForm.style.display    = 'none';
        if (spotsNotice) spotsNotice.style.display = 'none';

        var confirmText = config.confirmationMessage || 'Check your email for confirmation details.';

        confirmationEl.style.display = 'block';
        confirmationEl.innerHTML =
          '<div class="elp-confirmation">' +
            '<div class="elp-check-icon">&#10003;</div>' +
            '<h3>You\'re Booked!</h3>' +
            '<p>' + escapeHtml(firstName) + ', your appointment is confirmed for<br>' +
              '<span class="elp-booked-time">' + escapeHtml(formatDateHeading(dateStr)) + ' at ' + escapeHtml(timeLabel) + '</span>' +
            '</p>' +
            '<p>' + escapeHtml(confirmText) + '</p>' +
          '</div>';
      })
      .catch(function (err) {
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
        var errEl = document.getElementById('elpBookingError');
        if (!errEl) {
          errEl = document.createElement('div');
          errEl.id = 'elpBookingError';
          errEl.className = 'elp-error-message';
          bookingForm.appendChild(errEl);
        }
        errEl.innerHTML = '<p>' + escapeHtml(err.message) + '</p><p>Please try again.</p>';
      });
  }

  bookingForm.addEventListener('submit', submitBooking);

  // ---- Init ----

  function loadSlots() {
    timeSlotsGrid.innerHTML = '<div class="elp-loading-spinner"><span>Checking availability...</span></div>';
    timeSlotsDate.textContent = '';
    timeSlotsLabel.textContent = 'Loading available times...';

    // Fetch meeting config (form fields) without blocking slot rendering.
    fetchJSON('/meeting-config', { slug: config.slug, timezone: config.timezone })
      .then(function (data) {
        if (data && data.formFields && data.formFields.length > 0) {
          formFieldsDef = data.formFields;
          renderFormFields(formFieldsDef);
        }
      })
      .catch(function (err) {
        console.warn('Meeting config fetch failed, using default fields:', err);
      });

    fetchJSON('/availability', { slug: config.slug, timezone: config.timezone, monthOffset: monthOffset })
      .then(function (data) { renderSlots(data); })
      .catch(function (err) {
        console.error('Failed to load slots:', err);
        showError('Could not load available times. Please try again.');
      });
  }

  loadSlots();
})();
