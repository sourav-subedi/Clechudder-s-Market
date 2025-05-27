<!DOCTYPE html>
<html lang="en">
    <link rel="stylesheet" href="style.css">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CleckHudderMarket Help Center</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e6ecef;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            text-align: center;
            max-width: 900px;
            width: 100%;
            padding: 20px;
        }
        .header {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .tagline {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
        }
        .faq-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
            align-items: flex-start; /* Changed to fix grid alignment */
        }
        .faq-item {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: left;
            font-size: 16px;
            color: #333;
            min-height: 100px; /* Added minimum height */
            display: flex; /* Added for flex layout */
            flex-direction: column; /* Added for proper content flow */
        }
        .faq-question {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        .faq-question span {
            color: #ff6200;
            font-size: 24px;
        }
        .faq-answer {
            padding: 0 20px 20px;
            display: none;
            font-size: 14px;
            color: #555;
            margin-top: auto; /* Added to push answer to bottom */
        }
        .faq-answer.active {
            display: block;
        }
        .support-section {
            margin-top: 30px;
        }
        .support-section p {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .support-section span {
            font-size: 14px;
            color: #666;
            display: block;
            margin-bottom: 20px;
        }
        .contact-btn {
            background-color: #ff6200;
            color: #fff;
            padding: 10px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .contact-btn:hover {
            background-color: #e05500;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">CleckHudderMarket Help Center</div>
        <div class="tagline">100% awesome assistance, this way</div>
        
        <div class="faq-grid">
            <div class="faq-item">
                <div class="faq-question">What are your delivery hours? <span>+</span></div>
                <div class="faq-answer">
                    Our delivery hours are from 9 AM to 6 PM, Monday through Saturday. We do not deliver on Sundays or public holidays.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">How do I check product availability? <span>+</span></div>
                <div class="faq-answer">
                    You can check product availability by visiting our website and entering your zip code in the product page. Stock updates are real-time.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">Can I modify my order after checkout? <span>+</span></div>
                <div class="faq-answer">
                    Yes, you can modify your order within 1 hour of checkout by contacting our support team. After that, modifications may not be possible.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">What if items arrive damaged? <span>+</span></div>
                <div class="faq-answer">
                    If your items arrive damaged, please report it within 48 hours of delivery. We'll arrange a replacement or refund as per our policy.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">Is contactless delivery available? <span>+</span></div>
                <div class="faq-answer">
                    Yes, contactless delivery is available. You can select this option at checkout, and our driver will leave your order at your doorstep.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">How do discount codes work? <span>+</span></div>
                <div class="faq-answer">
                    Discount codes can be applied at checkout. Enter the code in the designated field, and the discount will be applied to your total if valid.
                </div>
            </div>
        </div>

        <div class="support-section">
            <p>Need more help?</p>
            <span>Our customer support team is ready to assist you</span>
            <button class="contact-btn">Contact Support</button>
        </div>
    </div>
     <script>
        document.querySelectorAll('.faq-question').forEach(item => {
            item.addEventListener('click', () => {
                const answer = item.nextElementSibling;
                const toggle = item.querySelector('span');
                const isActive = answer.classList.contains('active');

                // Close all other answers
                document.querySelectorAll('.faq-item').forEach(faq => {
                    if (faq !== item.parentElement) {
                        const otherAnswer = faq.querySelector('.faq-answer');
                        const otherToggle = faq.querySelector('.faq-question span');
                        otherAnswer.classList.remove('active');
                        otherToggle.textContent = '+';
                    }
                });

                // Toggle current answer
                answer.classList.toggle('active', !isActive);
                toggle.textContent = isActive ? '+' : '-';
            });
        });
     </script>
</body>
</html>