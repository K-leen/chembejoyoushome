<?php
require_once __DIR__ . '/dbconnection.php';

$errors = [];
$success = '';
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$messageText = trim($_POST['message'] ?? '');
$subjectOptions = [
    'General Inquiry',
    'Donations',
    'Volunteering',
    'Visiting the Homes',
    'Media & Press',
    'Corporate Partnership',
    'Child Welfare / Referral',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($name === '') {
        $errors[] = 'Name is required.';
    } elseif (mb_strlen($name) > 50) {
        $errors[] = 'Name must be 50 characters or fewer.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    } elseif (mb_strlen($email) > 50) {
        $errors[] = 'Email must be 50 characters or fewer.';
    }

    if ($phone === '') {
        $errors[] = 'Phone number is required.';
    } elseif (mb_strlen($phone) > 20) {
        $errors[] = 'Phone number must be 20 characters or fewer.';
    }

    if (!in_array($subject, $subjectOptions, true)) {
        $errors[] = 'Please select a valid subject.';
    }

    if ($messageText === '') {
        $errors[] = 'Message is required.';
    }

    if (!$errors) {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare(
                'INSERT INTO message (name, email, phone, subject, message, created_at) '
                . 'VALUES (:name, :email, :phone, :subject, :message, NOW())'
            );
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':subject' => $subject,
                ':message' => $messageText,
            ]);
            $success = 'Thank you! We have received your message and will get back to you within 1–2 business days.';
            $name = $email = $phone = $subject = $messageText = '';
        } catch (PDOException $e) {
            $errors[] = 'Failed to save your message. Please try again later.';
        }
    }
}

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Us - Joyous Children's Home Kenya</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sun: #a98ad4; --earth: #6844a5; --grass: #4a7c3f;
            --sky: #f1eaff; --cream: #faf7ff; --soft: #dac8f4;
            --text: #30223f; --muted: #79698d; --white: #ffffff;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Nunito', sans-serif; background: var(--cream); color: var(--text); }
        nav { background: var(--white); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
        .logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .logo-icon { width: 44px; height: 44px; background: var(--sun); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .logo-text { font-family: 'Playfair Display', serif; font-size: 1.1rem; color: var(--text); line-height: 1.2; }
        .logo-text span { display: block; font-size: 0.7rem; font-family: 'Nunito', sans-serif; color: var(--muted); font-weight: 600; letter-spacing: 1px; }
        nav ul { list-style: none; display: flex; gap: 1.5rem; }
        nav ul a { text-decoration: none; color: var(--muted); font-weight: 600; font-size: 0.9rem; }
        nav ul a:hover, nav ul a.active { color: var(--earth); }
        .nav-donate { background: var(--earth); color: var(--white); padding: 0.5rem 1.2rem; border-radius: 25px; text-decoration: none; font-weight: 700; font-size: 0.9rem; }
        .page-hero { background: linear-gradient(135deg, var(--sky) 0%, var(--soft) 100%); padding: 5rem 2rem 4rem; text-align: center; }
        .page-hero h1 { font-family: 'Playfair Display', serif; font-size: 2.8rem; color: var(--text); margin-bottom: 1rem; }
        .page-hero p { font-size: 1.1rem; color: var(--muted); max-width: 600px; margin: 0 auto; line-height: 1.8; }
        .section-tag { background: var(--soft); color: var(--earth); font-size: 0.8rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; padding: 0.4rem 1rem; border-radius: 20px; display: inline-block; margin-bottom: 1rem; }
        .contact-section { display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; max-width: 1100px; margin: 0 auto; padding: 5rem 2rem; align-items: start; }
        .contact-info h2, .location-section h2, .faq h2 { font-family: 'Playfair Display', serif; }
        .contact-info h2 { font-size: 2rem; margin-bottom: 0.5rem; }
        .contact-info > p { color: var(--muted); line-height: 1.8; margin-bottom: 2.5rem; font-size: 1.05rem; }
        .info-card { background: var(--white); border-radius: 16px; padding: 1.5rem; display: flex; align-items: flex-start; gap: 1.2rem; margin-bottom: 1.2rem; border: 1px solid var(--soft); transition: border-color 0.2s; }
        .info-card:hover { border-color: var(--sun); }
        .info-icon { width: 48px; height: 48px; background: var(--soft); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
        .info-content strong { display: block; font-size: 0.9rem; margin-bottom: 0.3rem; }
        .info-content p { color: var(--muted); font-size: 0.95rem; line-height: 1.5; }
        .info-content a { color: var(--earth); text-decoration: none; font-weight: 600; }
        .social-section { margin-top: 2rem; }
        .social-section h3 { font-size: 1rem; margin-bottom: 1rem; }
        .social-links { display: flex; gap: 0.8rem; flex-wrap: wrap; }
        .social-btn { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1.2rem; border-radius: 25px; text-decoration: none; font-size: 0.85rem; font-weight: 700; border: 2px solid; }
        .fb { border-color: #1877F2; color: #1877F2; } .tw { border-color: #1DA1F2; color: #1DA1F2; }
        .ig { border-color: #E1306C; color: #E1306C; } .wa { border-color: #25D366; color: #25D366; }
        .form-card { background: var(--white); border-radius: 20px; padding: 2.5rem; border: 1px solid var(--soft); }
        .form-card h3 { font-family: 'Playfair Display', serif; font-size: 1.5rem; color: var(--text); margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 700; color: var(--muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.8rem 1rem; border: 1.5px solid var(--soft); border-radius: 10px; font-family: 'Nunito', sans-serif; font-size: 1rem; color: var(--text); background: var(--cream); transition: border-color 0.2s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--earth); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .submit-btn { width: 100%; padding: 1rem; background: var(--earth); color: var(--white); border: none; border-radius: 30px; font-family: 'Nunito', sans-serif; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: all 0.2s; margin-top: 0.5rem; }
        .submit-btn:hover { background: var(--sun); color: var(--text); }
        .location-section { background: var(--earth); padding: 5rem 2rem; text-align: center; }
        .location-section h2 { font-size: 2rem; color: var(--white); margin-bottom: 1rem; }
        .location-section > p { color: rgba(255,255,255,0.85); margin: 0 auto 2.5rem; max-width: 500px; font-size: 1.05rem; line-height: 1.7; }
        .map-placeholder { max-width: 700px; margin: 0 auto; background: rgba(255,255,255,0.1); border-radius: 20px; border: 2px solid rgba(255,255,255,0.2); padding: 4rem 2rem; font-size: 4rem; }
        .map-placeholder p { color: rgba(255,255,255,0.8); font-size: 1rem; margin-top: 1rem; }
        .map-placeholder strong { color: var(--sun); display: block; margin-top: 0.5rem; }
        .faq { padding: 5rem 2rem; max-width: 800px; margin: 0 auto; }
        .faq h2 { font-size: 2rem; text-align: center; margin-bottom: 2.5rem; }
        .faq-item { border-bottom: 1px solid var(--soft); padding: 1.2rem 0; cursor: pointer; }
        .faq-question { display: flex; justify-content: space-between; align-items: center; font-weight: 700; }
        .faq-toggle { font-size: 1.3rem; color: var(--earth); transition: transform 0.2s; }
        .faq-answer { color: var(--muted); font-size: 0.95rem; line-height: 1.7; max-height: 0; overflow: hidden; transition: max-height 0.3s, padding 0.3s; }
        .faq-item.open .faq-answer { max-height: 200px; padding-top: 0.8rem; }
        .faq-item.open .faq-toggle { transform: rotate(45deg); }
        footer { background: var(--text); color: #D4B896; padding: 2rem; text-align: center; }
        footer a { color: var(--sun); text-decoration: none; }
        .error, .success { border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem; }
        .error { background: #FDEBEC; border: 1px solid #B00020; color: #B00020; }
        .error ul { padding-left: 1.2rem; }
        .success { background: #EAF4E8; border: 1px solid var(--grass); color: var(--grass); font-weight: 600; }
        @media (max-width: 768px) {
            nav ul, .nav-donate { display: none; }
            .page-hero h1 { font-size: 2rem; }
            .contact-section { grid-template-columns: 1fr; padding: 3rem 1rem; }
            .form-row { grid-template-columns: 1fr; }
            .form-card { padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <nav>
        <a href="index.html" class="logo">
            <div class="logo-icon">🌻</div>
            <div class="logo-text">Joyous Children's Home <span>Malindi, Kenya</span></div>
        </a>
        <ul>
            <li><a href="index.html">Home</a></li>
            <li><a href="about.html">About Us</a></li>
            <li><a href="children.html">Our Children</a></li>
            <li><a href="getinvolved.html">Get Involved</a></li>
            <li><a href="contact_us.php" class="active">Contact</a></li>
        </ul>
        <a href="donate.html" class="nav-donate">Donate Now</a>
    </nav>

    <section class="page-hero">
        <div class="section-tag">We'd Love to Hear From You</div>
        <h1>Contact Us</h1>
        <p>Whether you want to visit, volunteer, donate goods, or simply learn more, our doors and hearts are always open. Reach out today.</p>
    </section>

    <section class="contact-section">
        <div class="contact-info">
            <h2>Find Us</h2>
            <p>We are based in Kilifi County M'Chembe-Watamu District, Kenya. We welcome visits, but please book in advance so we can give you a warm welcome!</p>
            <div class="info-card"><div class="info-icon">📍</div><div class="info-content"><strong>Our Address</strong><p>Joyous Children's Home<br>Jaccaranda Beach Road,<br>Malindi, Kenya</p></div></div>
            <div class="info-card"><div class="info-icon">📞</div><div class="info-content"><strong>Phone Numbers</strong><p><a href="tel:+254721410537">+254 721 410 537</a> (Main Office)<br><a href="tel:+254708910592">+254 708 910 592</a> (Donations / M-Pesa)</p></div></div>
            <div class="info-card"><div class="info-icon">✉️</div><div class="info-content"><strong>Email</strong><p><a href="mailto:chembechild@yahoo.com">chembechild@yahoo.com</a><br><a href="mailto:kalamajoyce2016@gmail.com">kalamajoyce2016@gmail.com</a></p></div></div>
            <div class="info-card"><div class="info-icon">🕐</div><div class="info-content"><strong>Office Hours</strong><p>Monday - Friday: 8:00 AM - 5:00 PM<br>Saturday: SABBATH DAY<br>Sunday: family day</p></div></div>
            <div class="social-section"><h3>Follow Our Journey</h3><div class="social-links"><a href="#" class="social-btn fb">📘 Facebook</a><a href="#" class="social-btn tw">🐦 Twitter</a><a href="#" class="social-btn ig">🦋 Instagram</a><a href="https://wa.me/254721410537" class="social-btn wa">💬 WhatsApp</a></div></div>
        </div>
        <div class="form-card">
            <h3>Send Us a Message</h3>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?php echo escapeHtml($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success" role="status"><?php echo escapeHtml($success); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo escapeHtml($_SERVER['PHP_SELF'] ?? 'contact_us.php'); ?>">
        <div class="form-row"><div class="form-group"><label for="name">Your Name *</label><input id="name" name="name" type="text" value="<?php echo escapeHtml($name); ?>" placeholder="Rolleen Ndaa" maxlength="50" required></div>
        <div class="form-group"><label for="email">Your Email *</label><input id="email" name="email" type="email" value="<?php echo escapeHtml($email); ?>" placeholder="rolleen@example.com" maxlength="50" required></div></div>

        <div class="form-group">
            <label for="phone">Phone Number *</label>
            <input id="phone" name="phone" type="tel" value="<?php echo escapeHtml($phone); ?>" placeholder="0712 345 678" maxlength="20" required>
        </div>

        <div class="form-group">
            <label for="subject">I am contacting about *</label>
            <select id="subject" name="subject" required>
                <option value="">Select a subject</option>
                <?php foreach ($subjectOptions as $subjectOption): ?>
                    <option value="<?php echo escapeHtml($subjectOption); ?>"<?php echo $subject === $subjectOption ? ' selected' : ''; ?>><?php echo escapeHtml($subjectOption); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="message">Message *</label>
            <textarea id="message" name="message" rows="6" placeholder="Tell us how you'd like to connect or how we can help..." required><?php echo escapeHtml($messageText); ?></textarea>
        </div>

        <button type="submit" class="submit-btn">🌻 Send My Message</button>
    </form>
        </div>
    </section>

    <section class="location-section"><h2>📍 Visit Us in Malindi</h2><p>We love welcoming visitors! Please contact us to arrange a visit so we can ensure a meaningful experience for you and a joyful one for our children.</p><div class="map-placeholder">🗺️<p>Joyous Children's Home</p><strong>M'Chembe-Watamu District, Malindi, Kenya</strong></div></section>
    <section class="faq"><h2>Frequently Asked Questions</h2>
        <div class="faq-item"><div class="faq-question">Can I visit the children? <span class="faq-toggle">+</span></div><div class="faq-answer">Yes! Visits are welcome but must be arranged in advance. Please contact us to schedule a meaningful visit.</div></div>
        <div class="faq-item"><div class="faq-question">How do I know my donation reaches the children? <span class="faq-toggle">+</span></div><div class="faq-answer">We publish annual reports and send regular updates to our donors. We also welcome visits from donors who wish to see the impact firsthand.</div></div>
        <div class="faq-item"><div class="faq-question">Can I sponsor a specific child? <span class="faq-toggle">+</span></div><div class="faq-answer">Yes. Contact us to learn more about child sponsorship and the updates provided to sponsors.</div></div>
        <div class="faq-item"><div class="faq-question">Do you accept international volunteers? <span class="faq-toggle">+</span></div><div class="faq-answer">Absolutely. We welcome international volunteers who commit to at least four weeks.</div></div>
    </section>
    <footer><p>🌻 Joyous Children's Home, Malindi Kenya &nbsp;|&nbsp; <a href="index.html">Back to Home</a> &nbsp;|&nbsp; &copy; 2026</p></footer>

    <script>
        document.querySelectorAll('.faq-item').forEach(function (item) {
            item.addEventListener('click', function () {
                var wasOpen = item.classList.contains('open');
                document.querySelectorAll('.faq-item').forEach(function (faqItem) { faqItem.classList.remove('open'); });
                if (!wasOpen) item.classList.add('open');
            });
        });
    </script>
</body>
</html>
