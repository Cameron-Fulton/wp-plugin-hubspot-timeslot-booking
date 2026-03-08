/**
 * Event Landing Pages — Time Slot Picker
 *
 * Reads configuration from the global `elpEventConfig` object
 * injected via wp_localize_script().
 *
 * Expected elpEventConfig properties:
 *   restUrl       - REST API base (e.g., /wp-json/elp/v1)
 *   nonce         - WP REST nonce
 *   slug          - HubSpot meeting slug
 *   timezone      - IANA timezone
 *   targetDate    - YYYY-MM-DD string
 *   ctaLabel      - Submit button text
 *   confirmationMessage - Custom confirmation text
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

  if (!timeSlotsGrid) return;

  var selectedSlot = null;

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

  // ---- API ----

  function fetchAvailability() {
    var url = config.restUrl + '/availability' +
      '?slug=' + encodeURIComponent(config.slug) +
      '&timezone=' + encodeURIComponent(config.timezone) +
      '&monthOffset=' + monthOffset;

    return fetch(url, {
      headers: { 'X-WP-Nonce': config.nonce },
    }).then(function (res) {
      if (!res.ok) throw new Error('Failed to load availability (HTTP ' + res.status + ')');
      return res.json();
    });
  }

  // ---- Render ----

  function renderSlots(availability) {
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

    var firstName = document.getElementById('elpFirstName').value.trim();
    var lastName  = document.getElementById('elpLastName').value.trim();
    var email     = document.getElementById('elpEmail').value.trim();
    var rawPhone  = document.getElementById('elpPhone').value.trim();
    var phone     = rawPhone;

    // Prepend country code if enabled and phone is provided.
    if (rawPhone && config.enableCountryCode) {
      var codeEl = document.getElementById('elpCountryCode');
      if (codeEl) {
        phone = codeEl.value + rawPhone;
      }
    }

    if (!firstName || !lastName || !email) return;

    submitBtn.disabled = true;
    submitBtn.classList.add('loading');

    var payload = {
      slug: config.slug,
      timezone: config.timezone,
      duration: selectedSlot.durationMs,
      startMillisUtc: selectedSlot.startMillisUtc,
      formFields: [
        { name: 'firstname', value: firstName },
        { name: 'lastname', value: lastName },
        { name: 'email', value: email },
      ],
    };
    if (phone) {
      payload.formFields.push({ name: 'phone', value: phone });
    }

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

    fetchAvailability()
      .then(function (data) { renderSlots(data); })
      .catch(function (err) {
        console.error('Failed to load slots:', err);
        showError('Could not load available times. Please try again.');
      });
  }

  loadSlots();
})();
