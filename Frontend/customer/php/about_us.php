<?php
include "../../components/header.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Cleckhudders Market</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../css/homestyle.css">
    <style>
        /* About Page Specific Styles */
.about-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
    background-color: #ffffff; /* White background for the main container */
}

/* Hero Section */
.about-hero {
    text-align: center;
    padding: 4rem 0;
    background-color: #cad9e0; /* Light blue-gray background */
    margin-bottom: 3rem;
}

.about-hero h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: #2c3e50; /* Dark blue-gray for heading */
}

.hero-subtitle {
    font-size: 1.5rem;
    color: #555;
    margin-bottom: 1.5rem;
}

.hero-text {
    max-width: 800px;
    margin: 0 auto;
    line-height: 1.6;
    color: #333;
}

/* Experience Section */
.experience-section {
    display: flex;
    align-items: center;
    gap: 3rem;
    margin-bottom: 4rem;
    padding: 2rem;
    background-color: #f8f9fa; /* Very light gray background */
    border-radius: 8px;
}

.experience-content {
    flex: 1;
}

.experience-image {
    flex: 1;
}

.experience-image img {
    width: 100%;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* Services Section */
.services-section {
    margin-bottom: 4rem;
    padding: 2rem;
    background-color: #f1f5f8; /* Slightly darker light gray background */
    border-radius: 8px;
}

.services-section h2 {
    text-align: center;
    margin-bottom: 2rem;
    color: #2c3e50; /* Dark blue-gray for heading */
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.service-card {
    background-color: #cad9e0; /* Light blue-gray background */
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: transform 0.3s ease;
}

.service-card:hover {
    transform: translateY(-5px);
    background-color: #b8cad3; /* Slightly darker blue-gray on hover */
}
    </style>

</head>
<body>
    <!-- Main Content -->
    <main class="about-page">
        <!-- Hero Section -->
        <section class="about-hero">
            <h1>About Us</h1>
            <p class="hero-subtitle">We help you to serve your daily needs.</p>
            <p class="hero-text">At Cleckhudders Market, we're committed to providing fresh, high-quality groceries with exceptional service. Founded in 2010, we've grown from a small local shop to your trusted neighborhood market.</p>
            <button class="cta-btn" id="learnMoreBtn">Learn More</button>
        </section>

        <!-- Experience Section -->
        <section class="experience-section">
            <div class="experience-content">
                <h2>Providing Grocery Service To You</h2>
                <p>With a year of experience, we've perfected our selection process to bring you only the freshest produce, highest quality meats, and pantry staples you can trust. Our knowledgeable staff are always ready to help you find exactly what you need.</p>
                <ul class="experience-features">
                    <li><i class="fas fa-check"></i> 100% Freshness Guarantee</li>
                    <li><i class="fas fa-check"></i> Locally Sourced When Possible</li>
                    <li><i class="fas fa-check"></i> Competitive Prices</li>
                </ul>
            </div>
            <div class="experience-image">
                <img src="../../image/image.jpg" alt="Our grocery store interior" loading="lazy">
            </div>
        </section>

        <!-- Services Section -->
        <section class="services-section">
            <h2>Grocery Services You Can Count On</h2>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Product Finder Service</h3>
                    <p>Can't find what you need? Our product scouts will locate any special item you're looking for.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                    <h3>Personal Shopping</h3>
                    <p>Too busy to shop? Let our personal shoppers select the perfect items for you.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h3>Fast Delivery</h3>
                    <p>Get your groceries delivered to your door with our reliable same-day delivery service.</p>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Learn More button functionality
    const learnMoreBtn = document.getElementById('learnMoreBtn');
    if (learnMoreBtn) {
        learnMoreBtn.addEventListener('click', function() {
            // Scroll to the experience section
            document.querySelector('.experience-section').scrollIntoView({
                behavior: 'smooth'
            });
            
            // Optional: Add a temporary highlight effect
            const experienceSection = document.querySelector('.experience-section');
            experienceSection.style.boxShadow = '0 0 0 4px rgba(74, 144, 226, 0.5)';
            setTimeout(() => {
                experienceSection.style.boxShadow = 'none';
            }, 2000);
        });
    }

    // Service card hover effects (enhancing the CSS)
    const serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach(card => {
        // Add mouseenter/mouseleave effects
        card.addEventListener('mouseenter', function() {
            const icon = this.querySelector('.service-icon i');
            if (icon) {
                icon.style.transform = 'scale(1.2)';
                icon.style.transition = 'transform 0.3s ease';
            }
        });

        card.addEventListener('mouseleave', function() {
            const icon = this.querySelector('.service-icon i');
            if (icon) {
                icon.style.transform = 'scale(1)';
            }
        });

        // Click effect
        card.addEventListener('click', function() {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = 'translateY(-5px)';
            }, 150);
        });
    });

    // Animation on scroll
    function animateOnScroll() {
        const elements = document.querySelectorAll('.experience-section, .services-section, .service-card');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.2;

            if (elementPosition < screenPosition) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    }

    // Set initial state for animation
    const animatedElements = document.querySelectorAll('.experience-section, .services-section, .service-card');
    animatedElements.forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    });

    // Run on load and scroll
    window.addEventListener('load', animateOnScroll);
    window.addEventListener('scroll', animateOnScroll);

    // Current year in footer (if you have one)
    const yearElement = document.getElementById('current-year');
    if (yearElement) {
        yearElement.textContent = new Date().getFullYear();
    }
});
    </script>

      <!-- FOOTER -->
  <?php
    include "../../components/footer.php";
  ?>
</body>
</html>