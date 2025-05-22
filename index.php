    <nav class="navbar">
        <div class="navbar-content">
            <div class="logo">✈️ Egypto Airlines</div>
            <div class="nav-center">
                <a href="index.php" class="nav-link">Home</a>
                <a href="about.html" class="nav-link">About</a>
                <a href="contact.html" class="nav-link">Contact</a>
            </div>
            <div class="nav-right">
                <?php
                session_start();
                if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
                    echo '<span class="user-welcome">Welcome, ' . htmlspecialchars($_SESSION["username"]) . '</span>';
                    echo '<a href="logout.php" class="nav-link login-btn">Logout</a>';
                } else {
                    echo '<a href="login.php" class="nav-link login-btn">Login</a>';
                }
                ?>
                <?php if (isset($_SESSION['github_avatar'])): ?>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                        <img src="<?php echo htmlspecialchars($_SESSION['github_avatar']); ?>" alt="GitHub Avatar" style="width: 32px; height: 32px; border-radius: 50%; border: 2px solid #24292e;">
                        <span style="background: #24292e; color: #fff; padding: 0.3rem 0.8rem; border-radius: 1rem; font-size: 1rem; font-weight: 600;">GitHub User</span>
                    </div>
                    <div style="margin-bottom: 1rem; color: #059669; font-weight: 600;">Welcome, GitHub user! You can book any travel offer below.</div>
                <?php endif; ?>
                <div class="mode-switch">
                    <input type="checkbox" id="mode-toggle" />
                    <label for="mode-toggle" class="toggle-label">
                        <span class="sun">☀️</span>
                        <span class="moon">🌙</span>
                    </label>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">Discover Your Next Adventure</h1>
            <p class="hero-subtitle">Book your dream vacation with our easy-to-use travel booking system</p>
            <div class="hero-buttons">
                <a href="destination_dashboard.php" class="hero-btn secondary" onclick="return checkLogin()">Explore Destinations</a>
            </div>
        </div>
    </section>

    <section id="destinations" class="destinations">
        <div class="container">
            <h2>Popular Destinations</h2>
            <div class="destination-grid">
                <div class="destination-card">
                    <img src="uploads/paris.jpeg" alt="Paris">
                    <h3>Paris, France</h3>
                    <p>From $999</p>
                </div>
                <div class="destination-card">
                    <img src="uploads/tokyo.jpeg" alt="Tokyo">
                    <h3>Tokyo, Japan</h3>
                    <p>From $1299</p>
                </div>
                <div class="destination-card">
                    <img src="uploads/new-york.jpeg" alt="New York">
                    <h3>New York, USA</h3>
                    <p>From $799</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>TravelBooking</h3>
                    <p>Your trusted partner in travel planning and booking.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.html">About</a></li>
                        <li><a href="contact.html">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <p>Email: egyptoairline@gmail.com </p>
                    <p>Phone: +201157022081</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 TravelBooking. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
    <script>
        // Dark mode toggle logic
        const modeToggle = document.getElementById('mode-toggle');
        const body = document.body;
        function setMode(dark) {
            if (dark) {
                body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
            } else {
                body.classList.remove('dark-mode');
                localStorage.setItem('theme', 'light');
            }
        }
        modeToggle.addEventListener('change', function() {
            setMode(this.checked);
        });
        window.addEventListener('DOMContentLoaded', function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                modeToggle.checked = true;
                setMode(true);
            }
        });

        function checkLogin() {
            <?php
            if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
                echo "alert('Please login to access destinations'); window.location.href='login.php'; return false;";
            }
            ?>
            return true;
        }
    </script>

<style>
    :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8;
        --secondary: #64748b;
        --accent: #f59e0b;
        --background: #f8fafc;
        --text: #1e293b;
        --card-bg: #ffffff;
        --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --transition: all 0.3s ease;
    }

    body {
        background: var(--background);
        color: var(--text);
        font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
        line-height: 1.6;
        margin: 0;
        padding: 0;
        transition: var(--transition);
    }

    /* Navbar Styles */
    .navbar {
        width: 100%;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        box-shadow: var(--shadow);
        padding: 1rem 0;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1000;
        transition: var(--transition);
    }

    .navbar-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 2rem;
    }

    .logo {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        transition: var(--transition);
    }

    .nav-center {
        display: flex;
        align-items: center;
        gap: 2.5rem;
    }

    .nav-link {
        color: var(--text);
        text-decoration: none;
        font-weight: 500;
        font-size: 1.1rem;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: var(--transition);
    }

    .nav-link:hover {
        color: var(--primary);
        background: rgba(37, 99, 235, 0.1);
    }

    .nav-right {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .user-welcome {
        color: var(--primary);
        font-weight: 600;
        font-size: 1rem;
    }

    .login-btn {
        background: var(--primary);
        color: white !important;
        padding: 0.7rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        transition: var(--transition);
        text-decoration: none;
        box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
    }

    .login-btn:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(37, 99, 235, 0.3);
    }

    /* Hero Section */
    .hero {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                    url('https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=1500&q=80') center/cover no-repeat;
        padding: 2rem;
    }

    .hero-content {
        text-align: center;
        max-width: 800px;
        padding: 2rem;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }

    .hero-title {
        font-size: 3.5rem;
        font-weight: 800;
        color: white;
        margin-bottom: 1.5rem;
        line-height: 1.2;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .hero-subtitle {
        font-size: 1.5rem;
        color: rgba(255, 255, 255, 0.9);
        margin-bottom: 2.5rem;
        line-height: 1.6;
    }

    .hero-buttons {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .hero-btn {
        display: inline-block;
        padding: 1rem 2rem;
        font-size: 1.2rem;
        font-weight: 600;
        color: white;
        background: var(--primary);
        border-radius: 12px;
        text-decoration: none;
        transition: var(--transition);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .hero-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        background: var(--primary-dark);
    }

    /* Destinations Section */
    .destinations {
        padding: 5rem 2rem;
        background: white;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .destinations h2 {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--text);
        text-align: center;
        margin-bottom: 3rem;
    }

    .destination-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        padding: 1rem;
    }

    .destination-card {
        background: var(--card-bg);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: var(--transition);
    }

    .destination-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    .destination-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }

    .destination-card h3 {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--text);
        padding: 1.5rem 1.5rem 0.5rem;
        margin: 0;
    }

    .destination-card p {
        font-size: 1.2rem;
        color: var(--primary);
        font-weight: 600;
        padding: 0 1.5rem 1.5rem;
        margin: 0;
    }

    /* Footer */
    footer {
        background: #1e293b;
        color: white;
        padding: 4rem 2rem 2rem;
    }

    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 3rem;
    }

    .footer-section h3 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        color: white;
    }

    .footer-section p {
        color: rgba(255, 255, 255, 0.8);
        line-height: 1.6;
    }

    .footer-section ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-section ul li {
        margin-bottom: 0.8rem;
    }

    .footer-section ul li a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: var(--transition);
    }

    .footer-section ul li a:hover {
        color: white;
    }

    .footer-bottom {
        max-width: 1200px;
        margin: 3rem auto 0;
        padding-top: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        text-align: center;
        color: rgba(255, 255, 255, 0.6);
    }

    /* Dark Mode */
    body.dark-mode {
        --background: #0f172a;
        --text: #e2e8f0;
        --card-bg: #1e293b;
    }

    body.dark-mode .navbar {
        background: rgba(15, 23, 42, 0.95);
    }

    body.dark-mode .nav-link {
        color: var(--text);
    }

    body.dark-mode .nav-link:hover {
        background: rgba(37, 99, 235, 0.2);
    }

    body.dark-mode .destinations {
        background: var(--background);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .navbar-content {
            padding: 0 1rem;
        }

        .nav-center {
            display: none;
        }

        .hero-title {
            font-size: 2.5rem;
        }

        .hero-subtitle {
            font-size: 1.2rem;
        }

        .destination-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Mode Switch */
    .mode-switch {
        position: relative;
        display: inline-block;
    }

    .toggle-label {
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.2rem;
    }

    #mode-toggle {
        display: none;
    }

    .sun, .moon {
        transition: var(--transition);
    }

    body.dark-mode .sun {
        opacity: 0.5;
    }

    body:not(.dark-mode) .moon {
        opacity: 0.5;
    }
</style> 