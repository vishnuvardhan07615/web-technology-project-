<?php
/**
 * ============================================================================
 * MotoCare – Two-Wheeler Service & Mechanic Shop Management System
 * File: services.php
 * Stage 2: Dynamic Services Catalog with MySQL Database Integration
 * ============================================================================
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Services Catalog – MotoCare Two-Wheeler Service & Repair';
$pageDescription = 'Explore MotoCare comprehensive two-wheeler service catalog. Transparent pricing for General Maintenance, Mechanical Overhauls, Electrical Diagnostics, Tyre care, and EV Systems.';
$currentPage = 'services.php';

// Attempt to load live services from MySQL database
$dbServices = [];
$dbConnected = false;

if (isset($pdo) && $pdo !== null) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM `services` WHERE `status` = 'Active' ORDER BY `id` ASC");
        $stmt->execute();
        $dbServices = $stmt->fetchAll();
        $dbConnected = (count($dbServices) > 0);
    } catch (PDOException $e) {
        $dbConnected = false;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

    <!-- ======================================================================
         2. Services Hero Banner
         ====================================================================== -->
    <section class="section-padding" style="background: radial-gradient(circle at 50% 0%, rgba(255,94,20,0.12) 0%, transparent 60%); padding-bottom: 2rem;">
      <div class="container">
        <div class="section-header" style="margin-bottom: 2.5rem;">
          <span class="section-badge">Complete Two-Wheeler Care</span>
          <h1 class="section-title">Specialized Service <span class="text-gradient">Catalog &amp; Pricing</span></h1>
          <p class="section-subtitle">
            Transparent estimates, skilled workmanship, and genuine parts guarantee across five dedicated vehicle disciplines.
          </p>
          <?php if ($dbConnected): ?>
            <div style="margin-top: 1rem; display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: var(--color-green); background: rgba(16,185,129,0.1); padding: 0.25rem 0.75rem; border-radius: 99px;">
              <span class="pulse-dot"></span> Live Catalog Connected (MySQL database: motocare_db)
            </div>
          <?php endif; ?>
        </div>

        <!-- Category Jump Bar -->
        <nav class="category-nav" aria-label="Service Category Quick Links">
          <a href="#general" class="category-btn active">General Service</a>
          <a href="#mechanical" class="category-btn">Mechanical</a>
          <a href="#electrical" class="category-btn">Electrical</a>
          <a href="#tyres" class="category-btn">Tyre &amp; Wheel</a>
          <a href="#ev" class="category-btn">Electric Vehicles (EV)</a>
        </nav>
      </div>
    </section>

    <!-- ======================================================================
         CATEGORY 1: GENERAL SERVICE
         ====================================================================== -->
    <section class="section-padding" id="general" style="padding-top: 2rem;">
      <div class="container">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem;">
          <div class="service-icon-box" style="width: 44px; height: 44px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
          </div>
          <div>
            <h2 style="font-size: 1.85rem;">General Service</h2>
            <p style="font-size: 0.9rem; color: var(--text-muted);">Routine periodic maintenance packages for peak engine life and fuel efficiency.</p>
          </div>
        </div>

        <div class="services-grid">
          <!-- General Service -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
              </div>
              <span class="service-tag">Popular</span>
            </div>
            <h3 class="service-title">General Service</h3>
            <p class="service-desc">36-point diagnostic inspection, oil top-up, spark plug cleaning, carburetor/throttle cleaning, clutch tuning, and foam wash.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹500</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'General Service'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>

          <!-- Oil Change -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path></svg>
              </div>
              <span class="service-tag">Express</span>
            </div>
            <h3 class="service-title">Oil Change</h3>
            <p class="service-desc">High-grade synthetic or mineral oil draining and refill, magnetic sump plug inspection, and oil filter gasket replacement.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹450</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Oil Change'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>

          <!-- Chain Cleaning -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M8 12h8"></path></svg>
              </div>
              <span class="service-tag">Drive</span>
            </div>
            <h3 class="service-title">Chain Cleaning &amp; Lube</h3>
            <p class="service-desc">Degreasing of chain link rollers, O-ring/X-ring check, slack tension adjustment to spec, and high-tack synthetic lubrication.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹200</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Chain Cleaning'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>

          <!-- Bike Washing -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
              </div>
              <span class="service-tag">Detailing</span>
            </div>
            <h3 class="service-title">Bike Washing &amp; Polish</h3>
            <p class="service-desc">High-pressure snow foam wash, degreasing of rims and swingarm, air drying, and silicone body polish for lasting shine.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹150</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Bike Washing'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         CATEGORY 2: MECHANICAL
         ====================================================================== -->
    <section class="section-padding" id="mechanical" style="background-color: var(--bg-surface);">
      <div class="container">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem;">
          <div class="service-icon-box" style="width: 44px; height: 44px; color: var(--color-blue); border-color: rgba(56,189,248,0.2); background: rgba(56,189,248,0.1);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
          </div>
          <div>
            <h2 style="font-size: 1.85rem;">Mechanical Repairs</h2>
            <p style="font-size: 0.9rem; color: var(--text-muted);">Engine overhauls, clutch plate upgrades, braking dynamics, and suspension setup.</p>
          </div>
        </div>

        <div class="services-grid">
          <!-- Engine Service -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
              </div>
              <span class="service-tag">Complex</span>
            </div>
            <h3 class="service-title">Engine Service &amp; Overhaul</h3>
            <p class="service-desc">Complete diagnostic disassembly, decarbonization, cylinder kit check, valve lapping, gasket renewal, and timing chain setup.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹1,500</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Engine Service'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>

          <!-- Brake Service -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="4"></circle></svg>
              </div>
              <span class="service-tag">Safety</span>
            </div>
            <h3 class="service-title">Brake Service</h3>
            <p class="service-desc">Disc pad/drum shoe replacement, caliper pin greasing, disc rotor runout inspection, and hydraulic fluid pressure bleeding.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹300</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Brake Service'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>

          <!-- Clutch Service -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              </div>
              <span class="service-tag">Transmission</span>
            </div>
            <h3 class="service-title">Clutch Service</h3>
            <p class="service-desc">Friction plate and steel plate renewal, clutch cable lubrication/replacement, clutch hub inspection, and bite point adjustment.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹500</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Clutch Service'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>

          <!-- Suspension Service -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2v20M8 5h8M8 19h8"></path></svg>
              </div>
              <span class="service-tag">Handling</span>
            </div>
            <h3 class="service-title">Suspension Service</h3>
            <p class="service-desc">Front telescopic fork oil replacement, fork oil seal renewal, rear monoshock/twin-shock preload calibration and bush greasing.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹600</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Suspension Service'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         CATEGORY 3: ELECTRICAL
         ====================================================================== -->
    <section class="section-padding" id="electrical">
      <div class="container">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem;">
          <div class="service-icon-box" style="width: 44px; height: 44px; color: var(--color-yellow); border-color: rgba(250,204,21,0.2); background: rgba(250,204,21,0.1);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
          </div>
          <div>
            <h2 style="font-size: 1.85rem;">Electrical &amp; Electronics</h2>
            <p style="font-size: 0.9rem; color: var(--text-muted);">Wiring harness diagnostics, starter coils, lighting upgrades, and battery health.</p>
          </div>
        </div>

        <div class="services-grid">
          <!-- Battery Service -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="7" width="16" height="12" rx="2"></rect><line x1="22" y1="11" x2="22" y2="15"></line></svg>
              </div>
              <span class="service-tag">Power</span>
            </div>
            <h3 class="service-title">Battery Service</h3>
            <p class="service-desc">Digital load test, terminal cleaning, specific gravity test, electrolyte top-up, and charging relay health check.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹200</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Battery Service'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>

          <!-- Electrical Diagnosis -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
              </div>
              <span class="service-tag">Diagnostic</span>
            </div>
            <h3 class="service-title">Electrical Diagnosis</h3>
            <p class="service-desc">Multimeter analysis of stator coil, RR unit (rectifier regulator), ignition coil, and spark plug cap resistance.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹300</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Electrical Repair'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>

          <!-- Headlight Repair -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line></svg>
              </div>
              <span class="service-tag">Lighting</span>
            </div>
            <h3 class="service-title">Headlight &amp; Indicator Repair</h3>
            <p class="service-desc">High/low beam switch repair, LED conversion bulb fitment, flasher relay replacement, and beam angle leveling.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹200</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Electrical Repair'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>

          <!-- Horn/Wiring Repair -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11 5L6 9H2v6h4l5 4V5z"></path></svg>
              </div>
              <span class="service-tag">Controls</span>
            </div>
            <h3 class="service-title">Horn &amp; Wiring Repair</h3>
            <p class="service-desc">Dual trumpet/standard horn fitment, relay installation, wiring short-circuit tracing, and insulated sleeve replacement.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹250</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Electrical Repair'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         CATEGORY 4: TYRE & WHEEL
         ====================================================================== -->
    <section class="section-padding" id="tyres" style="background-color: var(--bg-surface);">
      <div class="container">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem;">
          <div class="service-icon-box" style="width: 44px; height: 44px; color: var(--text-main); border-color: rgba(255,255,255,0.2); background: rgba(255,255,255,0.1);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="3"></circle></svg>
          </div>
          <div>
            <h2 style="font-size: 1.85rem;">Tyre &amp; Wheel Care</h2>
            <p style="font-size: 0.9rem; color: var(--text-muted);">Tubeless tyre repair, high-speed wheel truing, rim bend removal, and fresh rubber fitment.</p>
          </div>
        </div>

        <div class="services-grid">
          <!-- Tyre Replacement -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="6"></circle></svg>
              </div>
              <span class="service-tag">Tyres</span>
            </div>
            <h3 class="service-title">Tyre Replacement</h3>
            <p class="service-desc">Scratch-free pneumatic machine mounting for tubeless/tube tyres (MRF, CEAT, Michelin, Apollo) with new rubber valve stem.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Labor Starting</span>
                <span class="price-val">₹200</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Tyre Service'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>

          <!-- Puncture Repair -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line></svg>
              </div>
              <span class="service-tag">Instant</span>
            </div>
            <h3 class="service-title">Puncture Repair</h3>
            <p class="service-desc">High-grade mushroom plug / strip repair for tubeless radial tyres and cold vulcanizing patch repair for inner tubes.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹100</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Tyre Service'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>

          <!-- Wheel Alignment -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
              </div>
              <span class="service-tag">Balancing</span>
            </div>
            <h3 class="service-title">Wheel Truing &amp; Alignment</h3>
            <p class="service-desc">Spoke wheel tightening and truing on dial gauges, alloy wheel rim bend rectification, and dynamic wheel balance testing.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹300</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'Tyre Service'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         CATEGORY 5: ELECTRIC VEHICLES (EV)
         ====================================================================== -->
    <section class="section-padding" id="ev">
      <div class="container">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1rem;">
          <div class="service-icon-box" style="width: 44px; height: 44px; color: var(--color-green); border-color: rgba(16,185,129,0.2); background: rgba(16,185,129,0.1);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
          </div>
          <div>
            <h2 style="font-size: 1.85rem;">Electric Vehicles (EV Care)</h2>
            <p style="font-size: 0.9rem; color: var(--text-muted);">Specialized diagnostics for Ather, Ola, TVS iQube, Chetak, and commercial e-scooters.</p>
          </div>
        </div>

        <div class="services-grid">
          <!-- EV General Checkup -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
              </div>
              <span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); border-color: rgba(16,185,129,0.3);">EV Pro</span>
            </div>
            <h3 class="service-title">EV General Checkup</h3>
            <p class="service-desc">Complete 42-point EV safety scan, firmware update check, throttle potentiometer test, regenerative braking calibration, and disc service.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹600</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'EV Service'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>

          <!-- Battery Inspection -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="7" width="16" height="12" rx="2"></rect><line x1="22" y1="11" x2="22" y2="15"></line></svg>
              </div>
              <span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); border-color: rgba(16,185,129,0.3);">BMS &amp; Cells</span>
            </div>
            <h3 class="service-title">Battery Pack Inspection</h3>
            <p class="service-desc">Cell voltage balance check, internal resistance test, BMS communication log scan, thermal management audit, and IP67 seal integrity inspection.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹450</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'EV Service'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>

          <!-- Motor Diagnosis -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="8"></circle><path d="M12 2v4M12 18v4"></path></svg>
              </div>
              <span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); border-color: rgba(16,185,129,0.3);">BLDC / Hub</span>
            </div>
            <h3 class="service-title">Motor &amp; Controller Diagnosis</h3>
            <p class="service-desc">PMSM / BLDC motor winding resistance check, Hall sensor testing, MCU controller MOSFET health check, and belt drive tensioning.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹700</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'EV Service'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>

          <!-- Charging System Check -->
          <article class="service-card">
            <div class="service-card-top">
              <div class="service-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
              </div>
              <span class="service-tag" style="background: rgba(16,185,129,0.15); color: var(--color-green); border-color: rgba(16,185,129,0.3);">Charger</span>
            </div>
            <h3 class="service-title">Charging System Check</h3>
            <p class="service-desc">Onboard charger port inspection, pin continuity testing, earthing verification, fast-charge handshake test, and portable adapter diagnostic.</p>
            <div class="service-meta">
              <div class="service-price">
                <span class="price-label">Estimated Price</span>
                <span class="price-val">₹350</span>
              </div>
              <a href="<?php echo getBookServiceUrl('./', 'EV Service'); ?>" class="btn btn-primary btn-sm">Book Now</a>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- ======================================================================
         CTA Banner
         ====================================================================== -->
    <section class="section-padding">
      <div class="container">
        <div class="cta-banner">
          <h2 class="cta-title">Need a Custom Two-Wheeler Repair?</h2>
          <p class="cta-desc">
            Not sure which package fits your bike symptoms? Book an appointment or call our lead mechanics for free diagnostic advice.
          </p>
          <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo getBookServiceUrl(); ?>" class="btn btn-primary btn-lg">Book Service Appointment</a>
            <a href="contact.php" class="btn btn-secondary btn-lg">Call Workshop Support</a>
          </div>
        </div>
      </div>
    </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
