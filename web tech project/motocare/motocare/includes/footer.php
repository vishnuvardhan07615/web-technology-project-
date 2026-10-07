<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: includes/footer.php
 * Stage 2: Public Website Footer Template
 * ============================================================================
 */

$rootPath = $rootPath ?? './';
?>
  </main>

  <!-- ========================================================================
       Footer
       ======================================================================== -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <!-- Brand & About -->
        <div class="footer-brand">
          <a href="<?php echo $rootPath; ?>index.php" class="brand-logo" title="MotoCare Home">
            <img src="<?php echo $rootPath; ?>images/logo/logo.svg" alt="MotoCare Logo" width="180" height="42">
          </a>
          <p>
            <strong>Your Ride. Our Responsibility.</strong><br>
            MotoCare is a multi-brand two-wheeler workshop providing authorized-level mechanical overhauls, routine service, and electric vehicle diagnostics.
          </p>
          <div class="social-links" aria-label="Social Media Links">
            <a href="#" class="social-link" title="Facebook" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></a>
            <a href="#" class="social-link" title="Instagram" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path></svg></a>
            <a href="#" class="social-link" title="Twitter / X" aria-label="Twitter"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg></a>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="footer-col">
          <h4>Quick Links</h4>
          <ul class="footer-links">
            <li><a href="<?php echo $rootPath; ?>index.php">Home</a></li>
            <li><a href="<?php echo $rootPath; ?>about.php">About MotoCare</a></li>
            <li><a href="<?php echo $rootPath; ?>services.php">Services Catalog</a></li>
            <li><a href="<?php echo getBookServiceUrl($rootPath); ?>">Book an Appointment</a></li>
            <li><a href="<?php echo $rootPath; ?>contact.php">Contact Us</a></li>
            <li><a href="<?php echo $rootPath; ?>portal-login.php">Portal Login</a></li>
          </ul>
        </div>

        <!-- Popular Services -->
        <div class="footer-col">
          <h4>Popular Services</h4>
          <ul class="footer-links">
            <li><a href="<?php echo $rootPath; ?>services.php#general">General Bike Service</a></li>
            <li><a href="<?php echo $rootPath; ?>services.php#mechanical">Engine Overhaul</a></li>
            <li><a href="<?php echo $rootPath; ?>services.php#general">Full Synthetic Oil Change</a></li>
            <li><a href="<?php echo $rootPath; ?>services.php#mechanical">Brake &amp; Disc Caliper</a></li>
            <li><a href="<?php echo $rootPath; ?>services.php#ev">EV Battery Diagnostic</a></li>
          </ul>
        </div>

        <!-- Working Hours & Contact -->
        <div class="footer-col">
          <h4>Working Hours</h4>
          <table class="hours-table">
            <tbody>
              <tr><td>Monday – Saturday:</td><td>8:00 AM – 8:00 PM</td></tr>
              <tr><td>Sunday:</td><td>9:00 AM – 2:00 PM</td></tr>
              <tr><td>Breakdown Help:</td><td><span class="text-orange">24 / 7 Available</span></td></tr>
            </tbody>
          </table>
          <div style="margin-top: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
            <p><strong>Hotline:</strong> +91 98765 43210</p>
            <p><strong>Support:</strong> support@motocare.com</p>
          </div>
        </div>
      </div>

      <!-- Copyright Bottom Bar -->
      <div class="footer-bottom">
        <p>&copy; <span class="current-year" id="currentYear"><?php echo date('Y'); ?></span> MotoCare. All rights reserved. Industrial College Project (Stage 2).</p>
        <p>Built with PHP, MySQL, CSS3 &amp; Vanilla JavaScript</p>
      </div>
    </div>
  </footer>

  <!-- Core JavaScript -->
  <script src="<?php echo $rootPath; ?>js/script.js"></script>
</body>
</html>
