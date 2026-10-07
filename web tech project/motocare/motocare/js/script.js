/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * Script: js/script.js
 * Stage 1: Vanilla JavaScript (Navigation, Validation, Dynamic UI & Modal)
 * Production Quality Polished Version for College LWP Review 01
 * ============================================================================
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Initialize Current Year in Footer
  initFooterYear();

  // 2. Initialize Mobile Navigation Menu
  initMobileNav();

  // 3. Highlight Active Navigation Link
  highlightActiveNavLink();

  // 4. Initialize Header Scroll Effect
  initHeaderScroll();

  // 5. Initialize Scroll Reveal Animations
  initScrollAnimations();

  // 6. Handle Service Query Parameter Pre-selection (booking.html?service=...)
  initServicePreselection();

  // 7. Initialize Real-Time Input Formatting & Live Error Clearing
  initRealtimeValidation();

  // 8. Initialize Booking Form Validation & Submission (booking.html)
  initBookingForm();

  // 9. Initialize Contact Form Validation & Submission (contact.html)
  initContactForm();

  // 10. Initialize Modal Close & Utility Handlers
  initModalHandlers();

  // 11. Initialize Category Quick Filter Navigation (services.html)
  initCategoryNav();
});

/* ----------------------------------------------------------------------------
 * 1. Footer Dynamic Year
 * ---------------------------------------------------------------------------- */
function initFooterYear() {
  const yearElements = document.querySelectorAll('.current-year, #currentYear');
  const currentYear = new Date().getFullYear();
  yearElements.forEach((el) => {
    el.textContent = currentYear;
  });
}

/* ----------------------------------------------------------------------------
 * 2. Mobile Navigation Toggle
 * ---------------------------------------------------------------------------- */
function initMobileNav() {
  const toggleBtn = document.getElementById('mobileMenuToggle');
  const navMenu = document.getElementById('primaryNavMenu');

  if (!toggleBtn || !navMenu) return;

  toggleBtn.addEventListener('click', () => {
    const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
    toggleBtn.setAttribute('aria-expanded', String(!isExpanded));
    navMenu.classList.toggle('open');
  });

  // Close menu when a navigation link inside is clicked
  const navLinks = navMenu.querySelectorAll('.nav-link, .btn');
  navLinks.forEach((link) => {
    link.addEventListener('click', () => {
      navMenu.classList.remove('open');
      toggleBtn.setAttribute('aria-expanded', 'false');
    });
  });

  // Close menu when clicking outside
  document.addEventListener('click', (e) => {
    if (!navMenu.contains(e.target) && !toggleBtn.contains(e.target) && navMenu.classList.contains('open')) {
      navMenu.classList.remove('open');
      toggleBtn.setAttribute('aria-expanded', 'false');
    }
  });

  // Close on ESC key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && navMenu.classList.contains('open')) {
      navMenu.classList.remove('open');
      toggleBtn.setAttribute('aria-expanded', 'false');
    }
  });

  // Close menu if window is resized above mobile breakpoint (991px)
  window.addEventListener('resize', () => {
    if (window.innerWidth > 991 && navMenu.classList.contains('open')) {
      navMenu.classList.remove('open');
      toggleBtn.setAttribute('aria-expanded', 'false');
    }
  });
}

/* ----------------------------------------------------------------------------
 * 3. Highlight Active Navigation Link
 * ---------------------------------------------------------------------------- */
function highlightActiveNavLink() {
  const path = window.location.pathname;
  let pageName = path.split('/').pop();
  if (!pageName || pageName === '') {
    pageName = 'index.html';
  }

  // Target only header navigation links
  const navLinks = document.querySelectorAll('.nav-menu .nav-link');
  navLinks.forEach((link) => {
    const href = link.getAttribute('href').split('?')[0].split('#')[0];
    if (href === pageName) {
      link.classList.add('active');
    } else {
      link.classList.remove('active');
    }
  });
}

/* ----------------------------------------------------------------------------
 * 4. Header Shadow on Scroll
 * ---------------------------------------------------------------------------- */
function initHeaderScroll() {
  const header = document.querySelector('.site-header');
  if (!header) return;

  const onScroll = () => {
    if (window.scrollY > 30) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}

/* ----------------------------------------------------------------------------
 * 5. Scroll Reveal Animations (Intersection Observer)
 * ---------------------------------------------------------------------------- */
function initScrollAnimations() {
  const elementsToReveal = document.querySelectorAll(
    '.service-card, .feature-box, .step-card, .testimonial-card, .stat-item, .team-card, .facility-card, .contact-item, .reveal-on-scroll'
  );

  if (!('IntersectionObserver' in window)) {
    elementsToReveal.forEach((el) => el.classList.add('is-revealed'));
    return;
  }

  const observer = new IntersectionObserver(
    (entries, observerInstance) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-revealed');
          observerInstance.unobserve(entry.target);
        }
      });
    },
    {
      root: null,
      threshold: 0.1,
      rootMargin: '0px 0px -30px 0px',
    }
  );

  elementsToReveal.forEach((el) => {
    el.classList.add('reveal-on-scroll');
    observer.observe(el);
  });
}

/* ----------------------------------------------------------------------------
 * 6. Service Query Param Pre-selection (booking.html?service=...)
 * ---------------------------------------------------------------------------- */
function initServicePreselection() {
  const serviceSelect = document.getElementById('serviceRequired');
  const dateInput = document.getElementById('preferredDate');

  // Set minimum date to today for date picker
  if (dateInput) {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const todayStr = `${yyyy}-${mm}-${dd}`;
    dateInput.setAttribute('min', todayStr);
  }

  if (!serviceSelect) return;

  const urlParams = new URLSearchParams(window.location.search);
  const serviceParam = urlParams.get('service');

  if (serviceParam) {
    const cleanParam = decodeURIComponent(serviceParam).trim().toLowerCase();
    for (let i = 0; i < serviceSelect.options.length; i++) {
      const optionVal = serviceSelect.options[i].value.toLowerCase();
      const optionText = serviceSelect.options[i].text.toLowerCase();
      if (optionVal === cleanParam || optionText.includes(cleanParam) || cleanParam.includes(optionVal)) {
        serviceSelect.selectedIndex = i;
        break;
      }
    }
  }
}

/* ----------------------------------------------------------------------------
 * 7. Real-Time Input Formatting & Live Error Clearing
 * ---------------------------------------------------------------------------- */
function initRealtimeValidation() {
  // Attach live clear listener to all inputs and selects in both forms
  const forms = document.querySelectorAll('#serviceBookingForm, #contactInquiryForm');
  forms.forEach((form) => {
    const controls = form.querySelectorAll('input, select, textarea');
    controls.forEach((control) => {
      control.addEventListener('input', () => {
        if (control.classList.contains('input-error')) {
          clearSingleError(control);
        }
      });
      control.addEventListener('change', () => {
        if (control.classList.contains('input-error')) {
          clearSingleError(control);
        }
      });
    });
  });

  // Auto-uppercase registration number
  const regInput = document.getElementById('vehicleReg');
  if (regInput) {
    regInput.addEventListener('input', (e) => {
      e.target.value = e.target.value.toUpperCase();
    });
  }

  // Restrict phone inputs to numeric only
  const phoneInputs = document.querySelectorAll('#customerPhone, #contactPhone');
  phoneInputs.forEach((phoneInput) => {
    phoneInput.addEventListener('input', (e) => {
      e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10);
    });
  });
}

function clearSingleError(inputElement) {
  inputElement.classList.remove('input-error');
  const parent = inputElement.closest('.form-group');
  if (parent) {
    const errorSpan = parent.querySelector('.error-text');
    if (errorSpan) errorSpan.textContent = '';
  }
}

/* ----------------------------------------------------------------------------
 * 8. Booking Form Validation & Submission
 * ---------------------------------------------------------------------------- */
function initBookingForm() {
  const form = document.getElementById('serviceBookingForm');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    clearFormErrors(form);

    let isValid = true;

    // 1. Customer Name (at least 3 alphabetical characters)
    const nameInput = document.getElementById('customerName');
    const nameVal = nameInput.value.trim();
    if (!nameVal || nameVal.length < 3 || !/^[a-zA-Z\s.]+$/.test(nameVal)) {
      showError(nameInput, 'Please enter a valid full name (letters only, min 3 characters)');
      isValid = false;
    }

    // 2. Mobile Number (10 digits starting with 6, 7, 8, or 9)
    const phoneInput = document.getElementById('customerPhone');
    const phoneVal = phoneInput.value.trim();
    const phoneRegex = /^[6-9]\d{9}$/;
    if (!phoneRegex.test(phoneVal)) {
      showError(phoneInput, 'Please enter a valid 10-digit mobile number starting with 6-9');
      isValid = false;
    }

    // 3. Email Address
    const emailInput = document.getElementById('customerEmail');
    const emailVal = emailInput.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    if (!emailRegex.test(emailVal)) {
      showError(emailInput, 'Please enter a valid email address (e.g. name@domain.com)');
      isValid = false;
    }

    // 4. Vehicle Type
    const typeSelect = document.getElementById('vehicleType');
    if (!typeSelect.value) {
      showError(typeSelect, 'Please select your vehicle type');
      isValid = false;
    }

    // 5. Vehicle Brand
    const brandSelect = document.getElementById('vehicleBrand');
    if (!brandSelect.value) {
      showError(brandSelect, 'Please select your vehicle brand');
      isValid = false;
    }

    // 6. Vehicle Model
    const modelInput = document.getElementById('vehicleModel');
    if (!modelInput.value.trim()) {
      showError(modelInput, 'Please enter your vehicle model (e.g. Classic 350 / Activa)');
      isValid = false;
    }

    // 7. Registration Number (at least 4 characters alphanumeric)
    const regInput = document.getElementById('vehicleReg');
    const regVal = regInput.value.trim();
    if (!regVal || regVal.length < 4) {
      showError(regInput, 'Please enter vehicle registration number (e.g. TN-07-AB-1234)');
      isValid = false;
    }

    // 8. Service Required
    const serviceSelect = document.getElementById('serviceRequired');
    if (!serviceSelect.value) {
      showError(serviceSelect, 'Please select the required service package');
      isValid = false;
    }

    // 9. Preferred Date (cannot be in the past)
    const dateInput = document.getElementById('preferredDate');
    if (!dateInput.value) {
      showError(dateInput, 'Please select an appointment date');
      isValid = false;
    } else {
      const selectedDate = new Date(dateInput.value);
      selectedDate.setHours(0, 0, 0, 0);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      if (selectedDate < today) {
        showError(dateInput, 'Appointment date cannot be in the past');
        isValid = false;
      }
    }

    // 10. Preferred Time Slot
    const timeSelect = document.getElementById('preferredTime');
    if (!timeSelect.value) {
      showError(timeSelect, 'Please choose a preferred time slot');
      isValid = false;
    }

    // If validation fails, focus the first invalid element
    if (!isValid) {
      const firstInvalid = form.querySelector('.input-error');
      if (firstInvalid) {
        firstInvalid.focus();
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      return;
    }

    // Validation Succeeded: Generate Service Token & Display Modal
    const serviceId = generateServiceId();
    const problemDesc = document.getElementById('problemDescription').value.trim() || 'Standard Inspection & Service';

    const bookingDetails = {
      serviceId,
      name: nameVal,
      phone: phoneVal,
      email: emailVal,
      vehicle: `${brandSelect.value} ${modelInput.value.trim()} (${typeSelect.value})`,
      regNumber: regVal.toUpperCase(),
      service: serviceSelect.value,
      dateTime: `${formatDateDisplay(dateInput.value)} (${timeSelect.value})`,
      problemDesc,
    };

    displayBookingConfirmation(bookingDetails);

    // Reset Form
    form.reset();
  });
}

/* ----------------------------------------------------------------------------
 * Helper: Generate Stage 1 Service ID (e.g., MC-2026-1045)
 * ---------------------------------------------------------------------------- */
function generateServiceId() {
  const currentYear = new Date().getFullYear();
  const randomSuffix = Math.floor(1000 + Math.random() * 9000);
  return `MC-${currentYear}-${randomSuffix}`;
}

function formatDateDisplay(dateStr) {
  if (!dateStr) return '';
  const dateObj = new Date(dateStr + 'T00:00:00');
  const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
  return dateObj.toLocaleDateString('en-US', options);
}

/* ----------------------------------------------------------------------------
 * Display Booking Confirmation Modal
 * ---------------------------------------------------------------------------- */
function displayBookingConfirmation(details) {
  const modalOverlay = document.getElementById('bookingSuccessModal');
  if (!modalOverlay) {
    alert(`Booking Confirmed!\nYour Service ID is: ${details.serviceId}\nOur service team will contact you shortly.`);
    return;
  }

  // Populate Modal Fields
  const tokenElement = document.getElementById('modalServiceId');
  if (tokenElement) tokenElement.textContent = details.serviceId;

  const modalSummary = document.getElementById('modalSummaryContent');
  if (modalSummary) {
    modalSummary.innerHTML = `
      <div class="summary-row">
        <span class="summary-label">Customer Name:</span>
        <span class="summary-val">${escapeHtml(details.name)}</span>
      </div>
      <div class="summary-row">
        <span class="summary-label">Vehicle:</span>
        <span class="summary-val">${escapeHtml(details.vehicle)}</span>
      </div>
      <div class="summary-row">
        <span class="summary-label">Registration No:</span>
        <span class="summary-val">${escapeHtml(details.regNumber)}</span>
      </div>
      <div class="summary-row">
        <span class="summary-label">Selected Service:</span>
        <span class="summary-val">${escapeHtml(details.service)}</span>
      </div>
      <div class="summary-row">
        <span class="summary-label">Appointment Slot:</span>
        <span class="summary-val">${escapeHtml(details.dateTime)}</span>
      </div>
      <div class="summary-row">
        <span class="summary-label">Contact Phone:</span>
        <span class="summary-val">${escapeHtml(details.phone)}</span>
      </div>
      <div class="summary-row">
        <span class="summary-label">Special Notes:</span>
        <span class="summary-val">${escapeHtml(details.problemDesc)}</span>
      </div>
    `;
  }

  // Show Modal
  modalOverlay.classList.add('active');
  document.body.style.overflow = 'hidden';
}

/* ----------------------------------------------------------------------------
 * 9. Contact Form Validation & Submission
 * ---------------------------------------------------------------------------- */
function initContactForm() {
  const form = document.getElementById('contactInquiryForm');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    clearFormErrors(form);

    let isValid = true;

    // Name
    const nameInput = document.getElementById('contactName');
    const nameVal = nameInput.value.trim();
    if (!nameVal || nameVal.length < 2 || !/^[a-zA-Z\s.]+$/.test(nameVal)) {
      showError(nameInput, 'Please enter your name (letters only, min 2 characters)');
      isValid = false;
    }

    // Email
    const emailInput = document.getElementById('contactEmail');
    const emailVal = emailInput.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    if (!emailRegex.test(emailVal)) {
      showError(emailInput, 'Please enter a valid email address');
      isValid = false;
    }

    // Phone
    const phoneInput = document.getElementById('contactPhone');
    const phoneVal = phoneInput.value.trim();
    const phoneRegex = /^[6-9]\d{9}$/;
    if (!phoneRegex.test(phoneVal)) {
      showError(phoneInput, 'Please enter a valid 10-digit phone number starting with 6-9');
      isValid = false;
    }

    // Message
    const msgInput = document.getElementById('contactMessage');
    const msgVal = msgInput.value.trim();
    if (!msgVal || msgVal.length < 10) {
      showError(msgInput, 'Please enter a detailed message (minimum 10 characters)');
      isValid = false;
    }

    if (!isValid) {
      const firstInvalid = form.querySelector('.input-error');
      if (firstInvalid) {
        firstInvalid.focus();
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      return;
    }

    // Success Notification Toast
    showToast(`Thank you, ${nameVal}! Your message has been received. Our team will contact you shortly.`);

    form.reset();
  });
}

/* ----------------------------------------------------------------------------
 * 10. Modal Handlers (Close, Copy ID with Fallback, Print Slip)
 * ---------------------------------------------------------------------------- */
function initModalHandlers() {
  const modalOverlay = document.getElementById('bookingSuccessModal');
  if (!modalOverlay) return;

  const closeBtns = modalOverlay.querySelectorAll('.js-close-modal');
  closeBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      closeBookingModal();
    });
  });

  modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) {
      closeBookingModal();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalOverlay.classList.contains('active')) {
      closeBookingModal();
    }
  });

  // Copy Service ID button with clipboard API and execCommand fallback
  const copyBtn = document.getElementById('btnCopyServiceId');
  if (copyBtn) {
    copyBtn.addEventListener('click', () => {
      const token = document.getElementById('modalServiceId').textContent.trim();
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(token)
          .then(() => {
            showToast(`Service ID ${token} copied to clipboard!`);
          })
          .catch(() => {
            fallbackCopy(token);
          });
      } else {
        fallbackCopy(token);
      }
    });
  }

  // Print Slip
  const printBtn = document.getElementById('btnPrintReceipt');
  if (printBtn) {
    printBtn.addEventListener('click', () => {
      window.print();
    });
  }
}

function fallbackCopy(text) {
  const tempInput = document.createElement('input');
  tempInput.value = text;
  document.body.appendChild(tempInput);
  tempInput.select();
  try {
    document.execCommand('copy');
    showToast(`Service ID ${text} copied to clipboard!`);
  } catch (err) {
    showToast(`Service ID: ${text}`);
  }
  document.body.removeChild(tempInput);
}

function closeBookingModal() {
  const modalOverlay = document.getElementById('bookingSuccessModal');
  if (modalOverlay) {
    modalOverlay.classList.remove('active');
    document.body.style.overflow = '';
  }
}

/* ----------------------------------------------------------------------------
 * 11. Category Filter Navigation (services.html)
 * ---------------------------------------------------------------------------- */
function initCategoryNav() {
  const categoryBtns = document.querySelectorAll('.category-nav .category-btn');
  if (!categoryBtns.length) return;

  categoryBtns.forEach((btn) => {
    btn.addEventListener('click', (e) => {
      categoryBtns.forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');
    });
  });
}

/* ----------------------------------------------------------------------------
 * Validation Helpers & Utilities
 * ---------------------------------------------------------------------------- */
function showError(inputElement, message) {
  inputElement.classList.add('input-error');
  const parent = inputElement.closest('.form-group');
  if (parent) {
    let errorSpan = parent.querySelector('.error-text');
    if (!errorSpan) {
      errorSpan = document.createElement('span');
      errorSpan.className = 'error-text';
      parent.appendChild(errorSpan);
    }
    errorSpan.textContent = message;
  }
}

function clearFormErrors(form) {
  const inputs = form.querySelectorAll('.input-error');
  inputs.forEach((input) => input.classList.remove('input-error'));

  const errorSpans = form.querySelectorAll('.error-text');
  errorSpans.forEach((span) => {
    span.textContent = '';
  });
}

function escapeHtml(str) {
  if (!str) return '';
  const div = document.createElement('div');
  div.appendChild(document.createTextNode(str));
  return div.innerHTML;
}

/* ----------------------------------------------------------------------------
 * Toast Notification Utility
 * ---------------------------------------------------------------------------- */
function showToast(message) {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.setAttribute('role', 'alert');
  toast.innerHTML = `
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
      <polyline points="22 4 12 14.01 9 11.01"></polyline>
    </svg>
    <span>${escapeHtml(message)}</span>
  `;

  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    toast.style.transition = 'all 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 4500);
}
