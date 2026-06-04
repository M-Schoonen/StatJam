<?php
session_start();

// ── DB config ──────────────────────────────────────────────────────────────
$host   = 'localhost';
$dbname = 'statjam';
$user   = 'root';
$pass   = 'root';
$port   = 3306;

// ── Handle AJAX POST ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $email    = trim($_POST['email']           ?? '');
    $password =      $_POST['password']        ?? '';
    $repeat   =      $_POST['repeat_password'] ?? '';

    // Validation
    if (empty($email) || empty($password) || empty($repeat)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }

    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
        exit;
    }

    if ($password !== $repeat) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }

    // Connect
    try {
        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
            $user, $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed. Is MAMP running?']);
        exit;
    }

    // Check if email already exists
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'An account with this email already exists.']);
        exit;
    }

    // Insert new user with hashed password
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (email, password) VALUES (?, ?)');
    $stmt->execute([$email, $hash]);

    $_SESSION['user_id']    = $pdo->lastInsertId();
    $_SESSION['user_email'] = $email;
    echo json_encode(['success' => true, 'redirect' => 'index.php']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>StatJam – Sign Up</title>
  <link rel="icon" href="./img/StatJam-Ball-Logo.webp" type="image/webp" />
  <link rel="stylesheet" href="./css/login.css" />
  <style>
    .alert {
      display: none;
      padding: 10px 14px;
      border-radius: 8px;
      font-size: 0.875rem;
      margin-bottom: 16px;
      font-weight: 500;
    }
    .alert.error   { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
    .alert.success { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
    .alert.visible { display: block; }
    .btn-submit:disabled { opacity: .7; cursor: not-allowed; }
    .spinner {
      display: inline-block;
      width: 14px; height: 14px;
      border: 2px solid rgba(255,255,255,.4);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin .6s linear infinite;
      vertical-align: middle;
      margin-right: 6px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>
<body>

  <!-- LEFT PANEL -->
  <div class="left-panel">
    <img class="bg-img" src="./img/login-bg.avif" alt="Basketball court" />

    <div class="left-content">
      <span class="badge">Sports Analytics</span>

      <h1 class="headline">Elevate<br>Your Game</h1>

      <p class="subtext">
        Track every stat, analyze every play, and unlock the full potential of your performance on and off the court.
      </p>

      <div class="stats-row">
        <div class="stat-item">
          <div class="value">1k+</div>
          <div class="label">Active Players</div>
        </div>
        <div class="stat-item">
          <div class="value">98%</div>
          <div class="label">Accuracy Rate</div>
        </div>
        <div class="stat-item">
          <div class="value">200+</div>
          <div class="label">Teams Tracked</div>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div class="right-panel">

      <!-- Logo -->
      <div class="logo">
        <img src="./img/StatJam-Logo.webp" alt="StatJam Logo" class="logo-img"
          onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" />
        <svg class="logo-icon" style="display:none" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="32" cy="32" r="30" fill="#F47A20"/>
          <path d="M32 2 Q44 16 44 32 Q44 48 32 62" stroke="white" stroke-width="2" fill="none"/>
          <path d="M32 2 Q20 16 20 32 Q20 48 32 62" stroke="white" stroke-width="2" fill="none"/>
          <path d="M2 32 Q16 20 32 20 Q48 20 62 32" stroke="white" stroke-width="2" fill="none"/>
          <rect x="18" y="42" width="6" height="10" rx="1.5" fill="white"/>
          <rect x="26" y="38" width="6" height="14" rx="1.5" fill="white"/>
          <rect x="34" y="34" width="6" height="18" rx="1.5" fill="white"/>
          <rect x="42" y="30" width="6" height="22" rx="1.5" fill="white"/>
          <rect x="14" y="52" width="38" height="2" rx="1" fill="white"/>
        </svg>
      </div>

      <div class="form-wrapper">

      <!-- Heading -->
      <h2 class="form-title">Sign Up</h2>
      <p class="form-subtitle">Welcome! Create your StatJam account.</p>

      <!-- Alert banner -->
      <div class="alert" id="alert"></div>

      <!-- Form -->
      <div>
        <div class="field">
          <label for="email">Email Address</label>
          <div class="input-wrap">
            <span class="input-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
              </svg>
            </span>
            <input type="email" id="email" placeholder="email@example.com" autocomplete="email" />
          </div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="input-wrap">
            <span class="input-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </span>
            <input type="password" id="password" placeholder="Min. 8 characters" autocomplete="new-password" />
            <button class="toggle-pw" onclick="togglePw('password','eye-icon-1')" type="button" aria-label="Toggle password visibility">
              <svg id="eye-icon-1" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="field">
          <label for="repeat_password">Repeat Password</label>
          <div class="input-wrap">
            <span class="input-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </span>
            <input type="password" id="repeat_password" placeholder="Repeat your password" autocomplete="new-password" />
            <button class="toggle-pw" onclick="togglePw('repeat_password','eye-icon-2')" type="button" aria-label="Toggle password visibility">
              <svg id="eye-icon-2" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <button class="btn-submit" id="submit-btn" type="button" onclick="handleRegister()">
          Create Account <span class="arrow-icon">&#8594;</span>
        </button>
      </div>

      <p class="signup-row">
        Already have an account? <a href="./login.php">Sign in</a>
      </p>

      <p class="legal">
        By signing up, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
      </p>
    </div>
  </div>

  <script>
    const eyeOpen  = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
    const eyeClosed = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`;

    function togglePw(inputId, iconId) {
      const input = document.getElementById(inputId);
      const icon  = document.getElementById(iconId);
      if (input.type === 'password') {
        input.type    = 'text';
        icon.innerHTML = eyeClosed;
      } else {
        input.type    = 'password';
        icon.innerHTML = eyeOpen;
      }
    }

    function showAlert(msg, type) {
      const el = document.getElementById('alert');
      el.textContent = msg;
      el.className = `alert ${type} visible`;
    }
    function hideAlert() {
      document.getElementById('alert').className = 'alert';
    }

    async function handleRegister() {
      hideAlert();

      const email    = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value;
      const repeat   = document.getElementById('repeat_password').value;
      const btn      = document.getElementById('submit-btn');

      if (!email || !password || !repeat) {
        showAlert('Please fill in all fields.', 'error');
        return;
      }
      if (password.length < 8) {
        showAlert('Password must be at least 8 characters.', 'error');
        return;
      }
      if (password !== repeat) {
        showAlert('Passwords do not match.', 'error');
        return;
      }

      btn.disabled  = true;
      btn.innerHTML = '<span class="spinner"></span> Creating account\u2026';

      try {
        const formData = new FormData();
        formData.append('email',           email);
        formData.append('password',        password);
        formData.append('repeat_password', repeat);

        const res  = await fetch('register.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
          showAlert('Account created!', 'success');
          setTimeout(() => { window.location.href = data.redirect; }, 800);
        } else {
          showAlert(data.message || 'Registration failed.', 'error');
          btn.disabled  = false;
          btn.innerHTML = 'Create Account <span class="arrow-icon">\u2192</span>';
        }
      } catch (err) {
        showAlert('Could not reach the server. Is MAMP running?', 'error');
        btn.disabled  = false;
        btn.innerHTML = 'Create Account <span class="arrow-icon">\u2192</span>';
      }
    }

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') handleRegister();
    });
  </script>
</body>
</html>