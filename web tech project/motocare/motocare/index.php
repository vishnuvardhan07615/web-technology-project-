<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: index.php
 * Stage 2: Dynamic Home Page
 * ============================================================================
 */

$pageTitle = 'MotoCare – Two-Wheeler Service & Mechanic Shop Management System';
$pageDescription = 'Complete bike and scooter servicing, repairs and maintenance by certified mechanics. Multi-brand care for motorcycles, scooters, and electric two-wheelers.';
$currentPage = 'index.php';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/feedback-helper.php';
require_once __DIR__ . '/includes/header.php';

$publicTestimonials = [];
if ($pdo) {
    $publicTestimonials = getPublicTestimonials($pdo, 3);
}
?>

    <!-- ======================================================================
         2. Hero Section
         ====================================================================== -->
    <section class="hero-section" aria-labelledby="heroTitle">
      <div class="container">
        <div class="hero-grid">
          <!-- Hero Text Content -->
          <div class="hero-content">
            <div class="hero-badge">
              <span class="pulse-dot"></span>
              <span>Authorized Multi-Brand Two-Wheeler Care</span>
            </div>
            <h1 class="hero-title" id="heroTitle">
              Professional Two-Wheeler Service <span class="text-gradient">You Can Trust</span>
            </h1>
            <p class="hero-text">
              Complete bike and scooter servicing, repairs and maintenance by skilled mechanics. 
              We ensure factory-grade maintenance for motorcycles, gearless scooters, and electric two-wheelers.
            </p>
            <div class="hero-actions">
              <a href="<?php echo getBookServiceUrl(); ?>" class="btn btn-primary btn-lg">
                <span>Book a Service</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
              </a>
              <a href="services.php" class="btn btn-secondary btn-lg">
                <span>Explore Services</span>
              </a>
            </div>

            <!-- Trust Points -->
            <div class="hero-trust-list">
              <div class="trust-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <span>100% Genuine Spares</span>
              </div>
              <div class="trust-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <span>Same-Day Delivery</span>
              </div>
              <div class="trust-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <span>Digital Service Card</span>
              </div>
            </div>
          </div>

          <!-- Hero Visual Component -->
          <div class="hero-visual">
            <div class="hero-card-stack">
              <div class="hero-img-container">
                <img src="images/bikes/motorcycle.svg" alt="Motorcycle engineering illustration" width="400" height="240">
              </div>

              <!-- Vehicle Categories Badges -->
              <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
                <span class="service-tag">Motorcycles</span>
                <span class="service-tag">Scooters</span>
                <span class="service-tag">Electric (EV)</span>
              </div>

              <!-- Floating Live Workshop Badge -->
              <div class="floating-badge">
                <div class="badge-icon">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                </div>
                <div class="badge-info">
                  <h4>Workshop Active</h4>
                  <p>Slots Available for Booking Today</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         3. Quick Statistics Section
         ====================================================================== -->
    <section class="stats-section" aria-label="Business Statistics">
      <div class="container">
        <div class="stats-grid">
          <div class="stat-item">
            <span class="stat-number">10+</span>
            <span class="stat-label">Years of Workshop Experience</span>
          </div>
          <div class="stat-item">
            <span class="stat-number">5,000+</span>
            <span class="stat-label">Two-Wheelers Serviced</span>
          </div>
          <div class="stat-item">
            <span class="stat-number">8+</span>
            <span class="stat-label">Certified Expert Mechanics</span>
          </div>
          <div class="stat-item">
            <span class="stat-number">4.9 / 5</span>
            <span class="stat-label">Customer Satisfaction Rating</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         4. Popular Services Section
         ====================================================================== -->
    <section class="section-padding" id="popularServices" aria-labelledby="servicesTitle">
      <div class="container">
        <div class="section-header">
          <span class="section-badge">Precision Care</span>
          <h2 class="section-title" id="servicesTitle">Popular Services</h2>
          <p class="section-subtitle">
            Routine checkups, mechanical overhauls, and EV electrical diagnostics carried out with cutting-edge garage equipment.
          </p>
        </div>

        <div class="services-grid">
          <!-- 1. General Service -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
              </div>
              <span class="service-tag">Essential</span>
            </div>
            <h3 class="service-title">General Service</h3>
            <p class="service-desc">
              Comprehensive 36-point inspection, thorough foam wash, spark plug cleaning, carburetor/throttle tuning, and chain lubrication.
            </p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Starting From</span>
                <span class="price-val">₹500</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'General Service'); ?>" class="btn btn-outline btn-sm">Book Now</a>
            </div>
          </article>

          <!-- 2. Engine Service -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
              </div>
              <span class="service-tag">Mechanical</span>
            </div>
            <h3 class="service-title">Engine Service</h3>
            <p class="service-desc">
              Piston and cylinder head overhaul, tappet adjustment, valve clearance tuning, clutch plate inspection, and compression testing.
            </p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Starting From</span>
                <span class="price-val">₹1,500</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Engine Service'); ?>" class="btn btn-outline btn-sm">Book Now</a>
            </div>
          </article>

          <!-- 3. Oil Change -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path></svg>
              </div>
              <span class="service-tag">Fluids</span>
            </div>
            <h3 class="service-title">Oil Change</h3>
            <p class="service-desc">
              High-performance synthetic / semi-synthetic engine oil replacement, magnetic drain plug inspection, and oil filter change.
            </p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Starting From</span>
                <span class="price-val">₹450</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Oil Change'); ?>" class="btn btn-outline btn-sm">Book Now</a>
            </div>
          </article>

          <!-- 4. Brake Service -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="4"></circle><line x1="4.93" y1="4.93" x2="9.17" y2="9.17"></line><line x1="14.83" y1="14.83" x2="19.07" y2="19.07"></line><line x1="14.83" y1="9.17" x2="19.07" y2="4.93"></line></svg>
              </div>
              <span class="service-tag">Safety</span>
            </div>
            <h3 class="service-title">Brake Service</h3>
            <p class="service-desc">
              Disc brake pad replacement, drum shoe cleaning, rotor alignment check, hydraulic DOT4 fluid bleeding, and lever calibration.
            </p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Starting From</span>
                <span class="price-val">₹300</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Brake Service'); ?>" class="btn btn-outline btn-sm">Book Now</a>
            </div>
          </article>

          <!-- 5. Battery Service -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="7" width="16" height="12" rx="2"></rect><line x1="22" y1="11" x2="22" y2="15"></line><line x1="6" y1="11" x2="6" y2="15"></line><line x1="10" y1="11" x2="14" y2="11"></line></svg>
              </div>
              <span class="service-tag">Electrical</span>
            </div>
            <h3 class="service-title">Battery Service</h3>
            <p class="service-desc">
              Digital load testing, terminal corrosion descaling, charging voltage measurement, and branded battery replacement with warranty.
            </p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Starting From</span>
                <span class="price-val">₹200</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Battery Service'); ?>" class="btn btn-outline btn-sm">Book Now</a>
            </div>
          </article>

          <!-- 6. Electrical Repair -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
              </div>
              <span class="service-tag">Diagnosis</span>
            </div>
            <h3 class="service-title">Electrical Repair</h3>
            <p class="service-desc">
              Wiring harness troubleshooting, LED headlight/tail light fixing, starter motor repair, sensor scanning, and fuse-box restoration.
            </p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Starting From</span>
                <span class="price-val">₹300</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Electrical Repair'); ?>" class="btn btn-outline btn-sm">Book Now</a>
            </div>
          </article>
        </div>

        <div style="text-align: center; margin-top: 3.5rem;">
          <a href="services.php" class="btn btn-secondary btn-lg">
            <span>View Full Service Catalog (All 19 Services)</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
          </a>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         5. Why Choose MotoCare Section
         ====================================================================== -->
    <section class="section-padding" style="background-color: var(--bg-surface);" aria-labelledby="whyChooseTitle">
      <div class="container">
        <div class="section-header">
          <span class="section-badge">The MotoCare Advantage</span>
          <h2 class="section-title" id="whyChooseTitle">Why Choose MotoCare?</h2>
          <p class="section-subtitle">
            We operate with the standards of an authorized showroom while retaining the friendly affordability of a trusted neighborhood mechanic.
          </p>
        </div>

        <div class="features-grid">
          <!-- 1. Experienced Mechanics -->
          <div class="feature-box">
            <div class="feature-icon-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <h3 class="feature-title">Experienced Mechanics</h3>
            <p class="feature-desc">
              Certified technicians with over a decade of specialized hands-on expertise in ICE superbikes, commuter scooters, and high-tech EVs.
            </p>
          </div>

          <!-- 2. Genuine Spare Parts -->
          <div class="feature-box">
            <div class="feature-icon-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            </div>
            <h3 class="feature-title">Genuine Spare Parts</h3>
            <p class="feature-desc">
              We exclusively fit OEM and OE-certified parts backed by manufacturer warranty. No counterfeit duplicates or compromised safety.
            </p>
          </div>

          <!-- 3. Transparent Pricing -->
          <div class="feature-box">
            <div class="feature-icon-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
            <h3 class="feature-title">Transparent Pricing</h3>
            <p class="feature-desc">
              Pre-approved job card estimates with zero hidden charges. We share WhatsApp photos of damaged parts prior to replacement.
            </p>
          </div>

          <!-- 4. Fast Service -->
          <div class="feature-box">
            <div class="feature-icon-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <h3 class="feature-title">Fast Service Turnaround</h3>
            <p class="feature-desc">
              Guaranteed same-day delivery for routine scheduled services and express oil lubrication packages without quality compromise.
            </p>
          </div>

          <!-- 5. Customer Satisfaction -->
          <div class="feature-box">
            <div class="feature-icon-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
            </div>
            <h3 class="feature-title">Customer Satisfaction</h3>
            <p class="feature-desc">
              Dedicated 7-day service post-repair warranty. If any issue re-occurs, we rectify it with immediate priority at zero extra cost.
            </p>
          </div>

          <!-- 6. Modern Tools -->
          <div class="feature-box">
            <div class="feature-icon-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            </div>
            <h3 class="feature-title">Modern Diagnostic Tools</h3>
            <p class="feature-desc">
              Equipped with hydraulic bike ramps, OBD-II scanner tablets for fuel-injected bikes, EV cell balancers, and pneumatic impact wrenches.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         6. How It Works Section
         ====================================================================== -->
    <section class="section-padding" aria-labelledby="howItWorksTitle">
      <div class="container">
        <div class="section-header">
          <span class="section-badge">Streamlined Workflow</span>
          <h2 class="section-title" id="howItWorksTitle">How It Works</h2>
          <p class="section-subtitle">
            Our smooth 6-step industrial workflow takes your two-wheeler from booking to showroom-fresh delivery.
          </p>
        </div>

        <div class="how-it-works-grid">
          <div class="step-card">
            <div class="step-header">
              <span class="step-number">01</span>
              <span class="step-badge">Stage 1</span>
            </div>
            <h3 class="step-title">Step 1 – Book Your Service</h3>
            <p class="step-desc">Select your vehicle brand, model, and required package on our online portal. Choose your preferred date and time slot.</p>
          </div>

          <div class="step-card">
            <div class="step-header">
              <span class="step-number">02</span>
              <span class="step-badge">Stage 2</span>
            </div>
            <h3 class="step-title">Step 2 – Bring Your Vehicle</h3>
            <p class="step-desc">Drop off your two-wheeler at our workshop facility or request our convenient doorstep vehicle pickup service.</p>
          </div>

          <div class="step-card">
            <div class="step-header">
              <span class="step-number">03</span>
              <span class="step-badge">Stage 3</span>
            </div>
            <h3 class="step-title">Step 3 – Vehicle Inspection</h3>
            <p class="step-desc">Our lead mechanic performs a comprehensive multi-point diagnostic check and prepares an itemized digital job card.</p>
          </div>

          <div class="step-card">
            <div class="step-header">
              <span class="step-number">04</span>
              <span class="step-badge">Stage 4</span>
            </div>
            <h3 class="step-title">Step 4 – Service &amp; Repair</h3>
            <p class="step-desc">Technicians replace fluids, adjust mechanical components, clean filters, and replace worn parts using calibrated tools.</p>
          </div>

          <div class="step-card">
            <div class="step-header">
              <span class="step-number">05</span>
              <span class="step-badge">Stage 5</span>
            </div>
            <h3 class="step-title">Step 5 – Quality Check</h3>
            <p class="step-desc">Every serviced ride undergoes a rigorous supervisor road test and safety audit before high-pressure foam washing.</p>
          </div>

          <div class="step-card">
            <div class="step-header">
              <span class="step-number">06</span>
              <span class="step-badge">Stage 6</span>
            </div>
            <h3 class="step-title">Step 6 – Delivery</h3>
            <p class="step-desc">Receive automated SMS notification, review the itemized transparent invoice, and ride away with a 7-day service warranty.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         7. Customer Testimonials Section
         ====================================================================== -->
    <section class="section-padding" style="background-color: var(--bg-surface);" aria-labelledby="reviewsTitle">
      <div class="container">
        <div class="section-header">
          <span class="section-badge">Client Stories</span>
          <h2 class="section-title" id="reviewsTitle">What Riders Say About Us</h2>
          <p class="section-subtitle">
            Trusted by daily commuters, touring enthusiasts, and delivery fleets across the city.
          </p>
        </div>

        <div class="testimonials-grid">
          <?php if (!empty($publicTestimonials)): ?>
            <?php foreach ($publicTestimonials as $t): 
              $rVal = (int)$t['rating'];
            ?>
              <div class="testimonial-card">
                <div class="star-rating" aria-label="<?php echo $rVal; ?> out of 5 stars">
                  <?php for ($i = 0; $i < 5; $i++): ?>
                    <svg viewBox="0 0 24 24" style="<?php echo ($i < $rVal) ? 'fill: #fbbf24; stroke: #fbbf24;' : 'fill: rgba(255,255,255,0.1); stroke: rgba(255,255,255,0.2);'; ?>">
                      <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                  <?php endfor; ?>
                </div>
                <p class="review-text">
                  "<?php echo htmlspecialchars($t['comments']); ?>"
                </p>
                <div class="customer-info">
                  <div class="customer-avatar"><?php echo htmlspecialchars($t['initials']); ?></div>
                  <div class="customer-meta">
                    <h4><?php echo htmlspecialchars($t['display_name']); ?></h4>
                    <p><?php echo htmlspecialchars($t['service_name']); ?> &bull; <?php echo htmlspecialchars($t['vehicle_desc']); ?></p>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="testimonial-card">
              <div class="star-rating" aria-label="5 out of 5 stars">
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
              </div>
              <p class="review-text">
                "Gave my Classic 350 for a full engine de-carb and brake overhaul. Arun Kumar and his team did an outstanding job. The engine sound is buttery smooth now and fuel economy noticeably improved. Transparent billing too!"
              </p>
              <div class="customer-info">
                <div class="customer-avatar">RV</div>
                <div class="customer-meta">
                  <h4>Ramesh Venkatesh</h4>
                  <p>Royal Enfield Classic 350 Owner</p>
                </div>
              </div>
            </div>

            <div class="testimonial-card">
              <div class="star-rating" aria-label="5 out of 5 stars">
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
              </div>
              <p class="review-text">
                "Booking my Activa service online took less than 2 minutes. Dropped it at 9:00 AM before office, and they handed it back fully serviced and polished by 3:00 PM. No unnecessary upselling, just honest work."
              </p>
              <div class="customer-info">
                <div class="customer-avatar">DS</div>
                <div class="customer-meta">
                  <h4>Deepa Sundaram</h4>
                  <p>Honda Activa 6G Owner</p>
                </div>
              </div>
            </div>

            <div class="testimonial-card">
              <div class="star-rating" aria-label="5 out of 5 stars">
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
              </div>
              <p class="review-text">
                "Finding a garage that truly understands electric scooters was difficult until I found MotoCare. Sanjay solved an erratic BMS sensor fault in my Ather that two other shops couldn't figure out. Highly recommended for EV owners!"
              </p>
              <div class="customer-info">
                <div class="customer-avatar">VM</div>
                <div class="customer-meta">
                  <h4>Vignesh Mohan</h4>
                  <p>Ather 450X Owner</p>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         8. Call To Action (CTA) Section
         ====================================================================== -->
    <section class="section-padding">
      <div class="container">
        <div class="cta-banner">
          <span class="section-badge" style="background: rgba(255,255,255,0.1); color: #ffffff; border-color: rgba(255,255,255,0.2);">
            Get Ready for the Road
          </span>
          <h2 class="cta-title">Your Bike Deserves the Best Care</h2>
          <p class="cta-desc">
            Experience smooth rides, optimized fuel efficiency, and complete peace of mind. Book an appointment online in under 60 seconds.
          </p>
          <a href="<?php echo getBookServiceUrl(); ?>" class="btn btn-primary btn-lg">
            <span>Book Your Service</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
          </a>
        </div>
      </div>
    </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
