<?php
require_once("includes/config.php");

$page_title = "Contact Us - FarmMart";
require_once("includes/header.php");
require_once("includes/navbar.php");

$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_msg = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } else {
        $success_msg = "Your message has been sent successfully! We will get back to you soon.";
        $_POST = array(); 
    }
}
?>

<style>
/* Premium Contact Page Styling */
:root {
  --theme-primary: #047857; /* Deep emerald green */
  --theme-secondary: #059669;
  --theme-dark: #111827;
  --theme-light: #f9fafb;
  --theme-accent: #f59e0b; /* Amber */
  --font-heading: 'Poppins', sans-serif;
  --font-body: 'Inter', sans-serif;
  --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

body {
  background-color: var(--theme-light);
  font-family: var(--font-body);
  color: #4b5563;
}

h1, h2, h3, h4, h5, h6 {
  font-family: var(--font-heading);
  color: var(--theme-dark);
  font-weight: 700;
}

/* Contact Hero Banner */
.contact-hero {
    min-height: 40vh;
    background: linear-gradient(rgba(17, 24, 39, 0.8), rgba(4, 120, 87, 0.7)), 
                url('https://images.unsplash.com/photo-1559884743-74a57598c6c7?auto=format&fit=crop&w=1920&q=80') no-repeat center center;
    background-size: cover;
    background-attachment: fixed;
    position: relative;
    display: flex;
    align-items: center;
}

.hero-title {
    font-size: 55px;
    font-weight: 800;
    color: #fff;
    margin-bottom: 20px;
}
.hero-title span {
    color: #34d399; /* Light emerald accent */
}

/* Article Section */
.article-section {
    background: #fff;
    padding: 60px 0;
    margin-top: -40px;
    border-radius: 30px 30px 0 0;
    position: relative;
    z-index: 10;
    box-shadow: 0 -10px 40px rgba(0,0,0,0.05);
}

.article-content {
    font-size: 16px;
    line-height: 1.8;
    color: #4b5563;
}
.article-content p {
    margin-bottom: 20px;
}
.article-content .highlight {
    font-size: 20px;
    font-weight: 600;
    color: var(--theme-primary);
    border-left: 4px solid var(--theme-accent);
    padding-left: 20px;
    margin: 30px 0;
    font-style: italic;
}

.article-img-wrap {
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}
.article-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: var(--transition);
}
.article-img-wrap:hover img {
    transform: scale(1.05);
}

/* Contact Cards */
.contact-section {
    padding: 80px 0;
    background: var(--theme-light);
}

.contact-card, .info-card {
    background: #fff;
    border-radius: 24px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    height: 100%;
    padding: 40px;
    position: relative;
    overflow: hidden;
}

.contact-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 6px;
    background: linear-gradient(to right, var(--theme-primary), var(--theme-accent));
}

.section-subtitle {
    color: var(--theme-primary);
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    margin-bottom: 10px;
    display: block;
}

/* Form Styles */
.form-label {
    font-weight: 600;
    font-size: 14px;
    color: var(--theme-dark);
    margin-bottom: 8px;
}

.form-control {
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    padding: 14px 20px;
    font-size: 15px;
    background: #f9fafb;
    transition: var(--transition);
}

.form-control:focus {
    background: #fff;
    border-color: var(--theme-primary);
    box-shadow: 0 0 0 4px rgba(4, 120, 87, 0.1);
    outline: none;
}

textarea.form-control {
    min-height: 150px;
    resize: vertical;
}

.btn-premium {
    background: var(--theme-primary);
    color: #fff;
    padding: 16px 36px;
    border-radius: 50px;
    font-family: var(--font-heading);
    font-weight: 600;
    font-size: 15px;
    letter-spacing: 0.5px;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    border: none;
    box-shadow: 0 10px 20px rgba(4, 120, 87, 0.3);
    width: 100%;
}

.btn-premium:hover {
    background: var(--theme-secondary);
    color: #fff;
    transform: translateY(-3px);
    box-shadow: 0 15px 25px rgba(4, 120, 87, 0.4);
}

/* Contact Info List */
.info-icon-wrapper {
    width: 60px;
    height: 60px;
    background: rgba(4, 120, 87, 0.1);
    color: var(--theme-primary);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 30px;
}

.info-item h5 {
    font-size: 18px;
    margin-bottom: 5px;
}
.info-item p {
    margin: 0;
    color: #6b7280;
    font-size: 15px;
}

.map-container {
    border-radius: 16px;
    overflow: hidden;
    height: 250px;
    margin-top: 30px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}
.map-container iframe {
    width: 100%;
    height: 100%;
    border: none;
}
</style>

<!-- HERO SECTION -->
<section class="contact-hero">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-8" data-aos="fade-up">
                <h1 class="hero-title">
                    Get in Touch <br><span>With FarmMart</span>
                </h1>
                <p class="lead text-white opacity-75 mb-0" style="font-weight: 300;">
                    We are bridging the gap between local organic farms and your kitchen table. Let's talk about the future of food.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- OUR STORY ARTICLE -->
<section class="article-section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <span class="section-subtitle">Our Mission</span>
                <h2 class="mb-4">Cultivating a sustainable future for local farmers.</h2>
                <div class="article-content">
                    <p>At FarmMart, we believe that the best food doesn't come from a factory—it comes from the earth, nurtured by the hands of passionate local farmers. Our platform was born out of a desire to eliminate the complex supply chains that hurt both the farmer's profit and the consumer's health.</p>
                    
                    <div class="highlight">
                        "When you buy from a local farmer, you aren't just buying food. You are investing in your community's health and the future of our planet."
                    </div>
                    
                    <p>We provide farmers with the digital tools they need to bring their fresh harvests directly to your doorstep. By fostering a transparent, farm-to-table ecosystem, we guarantee that you get 100% organic, chemical-free produce while our farmers get the fair compensation they deserve.</p>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="article-img-wrap">
                    <img src="https://images.unsplash.com/photo-1500937386664-56d1dfef3854?auto=format&fit=crop&w=800&q=80" alt="Farmer holding fresh produce">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CONTACT FORM & INFO -->
<section class="contact-section">
    <div class="container">
        <div class="row g-5 justify-content-center">
            
            <!-- Contact Form -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="contact-card">
                    <span class="section-subtitle">Send a Message</span>
                    <h3 class="mb-4">How can we help you?</h3>
                    
                    <?php if ($success_msg): ?>
                        <div class="alert alert-success alert-dismissible fade show" style="border-radius: 12px;" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success_msg) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger alert-dismissible fade show" style="border-radius: 12px;" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error_msg) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="contact.php">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="John Doe" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="john@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" placeholder="How can we assist you?" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="message" class="form-label">Your Message</label>
                                <textarea class="form-control" id="message" name="message" placeholder="Type your message here..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn-premium">
                                    Send Message <i class="bi bi-send ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="col-lg-5" data-aos="fade-up" data-aos-delay="200">
                <div class="info-card">
                    <span class="section-subtitle">Contact Info</span>
                    <h3 class="mb-5">Our Headquarters</h3>

                    <div class="info-item">
                        <div class="info-icon-wrapper">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div>
                            <h5>Office Address</h5>
                            <p>123 FarmMart Boulevard<br>Green Valley, Agriculture Dist. 45021</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon-wrapper">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div>
                            <h5>Email Us</h5>
                            <p>Support: support@farmmart.com<br>Partnerships: farmers@farmmart.com</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon-wrapper">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <div>
                            <h5>Call Us</h5>
                            <p>Mon-Sat: 9:00 AM - 6:00 PM<br>+1 (555) 123-4567</p>
                        </div>
                    </div>

                    <div class="map-container">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3151.835434510875!2d144.95565131531591!3d-37.81732797975183!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x6ad642af0f11fd81%3A0xf577c4b5f8fa0!2sMelbourne%20VIC%2C%20Australia!5e0!3m2!1sen!2s!4v1700000000000"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<?php
require_once("includes/footer.php");
?>