<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: contact.php
 * Stage 2: Dynamic Contact & Emergency Roadside Support Page
 * ============================================================================
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Contact Us & Emergency Assistance – MotoCare';
$pageDescription = 'Get in touch with MotoCare two-wheeler workshop. Address, phone numbers, emergency roadside breakdown assistance, and customer inquiry form.';
$currentPage = 'contact.php';

require_once __DIR__ . '/includes/header.php';
?>

    <!-- ======================================================================
         2. Contact Hero Banner
         ====================================================================== -->
    <section class="section-padding" style="background: radial-gradient(circle at 50% 0%, rgba(255,94,20,0.12) 0%, transparent 60%); padding-bottom: 2rem;">
      <div class="container">
        <div class="section-header" style="margin-bottom: 2rem;">
          <span class="section-badge">Get in Touch</span>
          <h1 class="section-title">We Are Here to <span class="text-gradient">Help Your Ride</span></h1>
          <p class="section-subtitle">
            Have a technical inquiry, need roadside assistance, or wish to consult our head mechanics? Connect with us anytime.
          </p>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         3. Emergency Assistance Strip
         ====================================================================== -->
    <section class="container" id="emergency">
      <div class="emergency-strip">
        <div class="emergency-content">
          <div class="emergency-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
          </div>
          <div class="emergency-text">
            <h3>24/7 Emergency Roadside Assistance</h3>
            <p>Stuck with a puncture, broken drive chain, dead battery, or unexpected engine seizure? Our mobile mechanic van is ready.</p>
          </div>
        </div>
        <a href="tel:+919876543211" class="btn btn-secondary" style="background: #ffffff; color: #991b1b; font-weight: 700; border: none;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
          <span>Call Helpline: +91 98765 43211</span>
        </a>
      </div>
    </section>

    <!-- ======================================================================
         4. Contact Details & Inquiry Form
         ====================================================================== -->
    <section class="section-padding" style="padding-top: 1rem;">
      <div class="container">
        <div class="contact-layout">
          
          <!-- Left: Workshop Details & Map -->
          <div class="contact-info-col">
            <h2 style="font-size: 1.75rem; margin-bottom: 1.5rem;">Workshop Information</h2>

            <div class="contact-info-list">
              <!-- Address -->
              <div class="contact-item">
                <div class="contact-item-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </div>
                <div class="contact-item-body">
                  <h4>Workshop Location</h4>
                  <p>
                    MotoCare Two-Wheeler Center<br>
                    #42, Grand Trunk Road, Near Metro Pillar 128,<br>
                    Auto Nagar Industrial Area, Chennai – 600032
                  </p>
                </div>
              </div>

              <!-- Phone Numbers -->
              <div class="contact-item">
                <div class="contact-item-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                </div>
                <div class="contact-item-body">
                  <h4>Phone &amp; WhatsApp</h4>
                  <p><a href="tel:+919876543210">+91 98765 43210 (Workshop Desk)</a></p>
                  <p><a href="tel:+914423456789">+91 044 2345 6789 (Front Office)</a></p>
                </div>
              </div>

              <!-- Email -->
              <div class="contact-item">
                <div class="contact-item-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                </div>
                <div class="contact-item-body">
                  <h4>Email Addresses</h4>
                  <p><a href="mailto:support@motocare.com">support@motocare.com</a></p>
                  <p><a href="mailto:bookings@motocare.com">bookings@motocare.com</a></p>
                </div>
              </div>

              <!-- Working Hours -->
              <div class="contact-item">
                <div class="contact-item-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                </div>
                <div class="contact-item-body">
                  <h4>Operational Hours</h4>
                  <p>Monday – Saturday: <strong>8:00 AM – 8:00 PM</strong></p>
                  <p>Sunday: <strong>9:00 AM – 2:00 PM</strong></p>
                  <p class="text-orange" style="font-size: 0.825rem; margin-top: 0.25rem;">Roadside Breakdown: 24/7 on call</p>
                </div>
              </div>
            </div>

            <!-- Map View Placeholder -->
            <div class="map-placeholder">
              <iframe 
                src="https://maps.google.com/maps?q=Chennai%20Metro%20Pillar%20Industrial&t=&z=14&ie=UTF8&iwloc=&output=embed" 
                title="MotoCare Workshop Google Maps Location"
                loading="lazy" 
                aria-label="Google Map View of MotoCare Workshop">
              </iframe>
            </div>
          </div>

          <!-- Right: Send Us a Message Form -->
          <div class="form-card">
            <h2 style="font-size: 1.75rem; margin-bottom: 0.5rem;">Send Us a Message</h2>
            <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 2rem;">
              Have questions regarding bike modifications, fleet servicing, or warranty claims? Write to us directly.
            </p>

            <form id="contactInquiryForm" method="POST" action="contact.php" novalidate>
              <div class="form-grid">
                
                <!-- Name -->
                <div class="form-group full-width">
                  <label for="contactName" class="form-label">
                    <span>Your Full Name</span>
                    <span class="required-dot">*</span>
                  </label>
                  <input type="text" id="contactName" name="contactName" class="form-input" placeholder="e.g. Karthik Selvan" required autocomplete="name">
                  <span class="error-text"></span>
                </div>

                <!-- Email -->
                <div class="form-group">
                  <label for="contactEmail" class="form-label">
                    <span>Email Address</span>
                    <span class="required-dot">*</span>
                  </label>
                  <input type="email" id="contactEmail" name="contactEmail" class="form-input" placeholder="e.g. karthik@example.com" required autocomplete="email">
                  <span class="error-text"></span>
                </div>

                <!-- Phone -->
                <div class="form-group">
                  <label for="contactPhone" class="form-label">
                    <span>Contact Number</span>
                    <span class="required-dot">*</span>
                  </label>
                  <input type="tel" id="contactPhone" name="contactPhone" class="form-input" placeholder="10-digit number" maxlength="10" required autocomplete="tel">
                  <span class="error-text"></span>
                </div>

                <!-- Message -->
                <div class="form-group full-width">
                  <label for="contactMessage" class="form-label">
                    <span>Your Message / Inquiry</span>
                    <span class="required-dot">*</span>
                  </label>
                  <textarea id="contactMessage" name="contactMessage" class="form-textarea" placeholder="Describe your two-wheeler requirements or feedback in detail..." required></textarea>
                  <span class="error-text"></span>
                </div>

                <!-- Submit Button -->
                <div class="form-group full-width" style="margin-top: 0.5rem;">
                  <button type="submit" class="btn btn-primary btn-lg btn-block" id="btnSubmitContact">
                    <span>Send Message to Workshop</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                  </button>
                  <p style="text-align: center; font-size: 0.8rem; color: var(--text-dim); margin-top: 0.75rem;">
                    * We typically respond within 30–60 minutes during workshop business hours.
                  </p>
                </div>

              </div>
            </form>
          </div>

        </div>
      </div>
    </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
