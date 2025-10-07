<!DOCTYPE html>
<html lang="tl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>iVote – Home</title>
  <style>
    :root {
      --bg: #0f172a;
      --panel: #111827;
      --muted: #94a3b8;
      --text: #e5e7eb;
      --brand: #22d3ee;
      --brand-strong: #06b6d4;
      --accent: #22c55e;
      --danger: #ef4444;
      --ring: rgba(34,211,238,.35);
      --radius: 18px;
      --shadow: 0 10px 30px rgba(0,0,0,.35);
    }
    * { box-sizing: border-box; }
    html, body { height: 100%; }
    body {
      margin: 0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, "Helvetica Neue", Arial, "Noto Sans";
      color: var(--text);
      background-image: url('assets/imgs/background.webp');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      position: relative;
      filter: "blur(50px)";
      line-height: 1.5;
    }
    body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(12, 11, 11, 0.5); /* Semi-transparent overlay for readability */
      z-index: -1;
    }
    header {
      position: sticky; top: 0; z-index: 10;
      backdrop-filter: saturate(1.2) blur(6px);
      background: linear-gradient(180deg, rgba(15,23,42,.9), rgba(15,23,42,.7));
      border-bottom: 1px solid rgba(255,255,255,.06);
    }
    .container { max-width: 1200px; margin: 0 auto; padding: 16px 20px; }
    .nav { display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 16px; }
    .nav .menu { display: flex; gap: 18px; align-items: center; }
    .nav a { color: var(--muted); text-decoration: none; font-weight: 500; }
    .nav a:hover { color: #cbd5e1; }
    .logo {
      display: inline-flex; align-items: center; gap: 10px; justify-self: end;
      padding: 8px 12px; border-radius: 999px; background: rgba(34,211,238,.08);
      border: 1px solid rgba(34,211,238,.25);
    }
    .logo-mark {
      width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center;
      background: linear-gradient(135deg, var(--brand), var(--brand-strong));
      color: #00262b; font-weight: 800;
      box-shadow: 0 4px 16px rgba(34,211,238,.35);
    }
    .logo-text { font-weight: 800; letter-spacing: .2px; color: #e6fbff; }
    .main {
      max-width: 1200px; margin: 40px auto; padding: 0 20px;
      display: grid; grid-template-columns: 1.1fr .9fr; gap: 28px; align-items: stretch;
    }
    .hero {
      background: linear-gradient(160deg, rgba(6,182,212,.25), rgba(34,197,94,.15));
      border: 1px solid rgba(148,163,184,.2);
      border-radius: var(--radius);
      padding: 36px; box-shadow: var(--shadow);
    }
    .kicker { color: var(--brand); font-weight: 700; text-transform: uppercase; letter-spacing: .12em; font-size: 12px; }
    h1 { font-size: clamp(28px, 4vw, 44px); margin: 8px 0 8px; line-height: 1.15; }
    .sub { color: #cbd5e1; font-size: 16px; margin-bottom: 22px; }
    .features { display: grid; gap: 12px; margin-top: 16px; }
    .feat {
      display: grid; grid-template-columns: 28px 1fr; gap: 12px; align-items: center;
      padding: 12px 12px; border-radius: 12px; background: rgba(255,255,255,.05);
      border: 1px solid rgba(148,163,184,.15);
    }
    .feat .dot { width: 10px; height: 10px; border-radius: 999px; background: var(--brand); box-shadow: 0 0 0 6px rgba(34,211,238,.15); justify-self: center; }
    .cta-row { display: flex; gap: 12px; margin-top: 22px; flex-wrap: wrap; }
    .btn {
      appearance: none; border: 0; cursor: pointer; font-weight: 700; border-radius: 12px; padding: 12px 16px;
      box-shadow: 0 8px 20px rgba(6,182,212,.24);
    }
    .btn-primary { background: linear-gradient(135deg, var(--brand), var(--brand-strong)); color: #00262b; }
    .btn-ghost { background: transparent; color: #e2e8f0; border: 1px solid rgba(148,163,184,.25); }
    .btn:focus { outline: 2px solid var(--ring); outline-offset: 2px; }
    .btn:disabled { opacity: 0.6; cursor: not-allowed; }
    .login-panel {
      background: linear-gradient(180deg, rgba(17,24,39,.9), rgba(17,24,39,.75));
      border: 1px solid rgba(148,163,184,.2);
      border-radius: var(--radius);
      padding: 28px; box-shadow: var(--shadow);
      display: grid; gap: 16px; align-content: start; position: relative;
    }
    .login-panel h2 { margin: 0; font-size: 22px; }
    .login-panel p { margin: 0; color: var(--muted); font-size: 14px; }
    form { display: grid; gap: 14px; margin-top: 6px; }
    .field { display: grid; gap: 8px; }
    label { font-size: 13px; color: #cbd5e1; }
    input, select {
      width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(148,163,184,.28);
      background: rgba(2,6,23,.55); color: var(--text);
    }
    input::placeholder { color: #94a3b8; }
    .row { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .row a { color: var(--brand); text-decoration: none; font-size: 13px; }
    .row a:hover { text-decoration: underline; }
    .checkbox { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: #cbd5e1; }
    .checkbox input { width: 16px; height: 16px; }
    .notice { font-size: 12px; color: var(--muted); text-align: center; margin-top: 6px; }
    footer { margin: 40px auto; max-width: 1200px; padding: 0 20px 40px; color: var(--muted); font-size: 13px; }
    .foot { display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; }

    .otp-modal {
      position: fixed; inset: 0; background: rgba(0,0,0,0.6);
      display: none; place-items: center; z-index: 999;
    }
    .otp-box {
      background: var(--panel); padding: 24px; border-radius: var(--radius);
      box-shadow: var(--shadow); width: 100%; max-width: 360px;
      display: grid; gap: 16px;
    }
    .otp-box h3 { margin: 0; font-size: 20px; }
    .otp-input {
      letter-spacing: 8px; font-size: 20px; text-align: center;
    }
    .otp-actions { display: flex; gap: 10px; justify-content: flex-end; }
    .alert { font-size: 13px; color: var(--accent); text-align: center; }
    .error { color: var(--danger); }
    .spinner {
      border: 3px solid rgba(255,255,255,0.3);
      border-top: 3px solid var(--brand);
      border-radius: 50%;
      width: 20px; height: 20px;
      animation: spin 0.8s linear infinite;
      display: inline-block;
      margin-left: 8px;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <header>
    <div class="container nav">
      <nav class="menu" aria-label="Pangunahing menu">
        <a href="#">Home</a>
        <a href="#features">Features</a>
        <a href="#how">How it works</a>
        <a href="#support">Support</a>
      </nav>
      <div class="logo" aria-label="iVote logo">
        <div class="logo-mark" aria-hidden="true">✓</div>
        <div class="logo-text">iVote</div>
      </div>
    </div>
  </header>

  <main class="main">
    <section class="hero" id="features">
      <span class="kicker">Secure • Fast • Transparent</span>
      <h1>Welcome to <span style="color: var(--brand)">iVote</span><br/>Your trusted campus e-voting platform</h1>
      <p class="sub">Magdaos ng eleksyon nang mas mabilis at ligtas. Real-time tally, role-based access, at audit trail—lahat nasa isang platform.</p>
      <div class="features">
        <div class="feat"><span class="dot"></span><div>End-to-end encrypted ballot casting</div></div>
        <div class="feat"><span class="dot"></span><div>OTP at email verification para sa voter identity</div></div>
        <div class="feat"><span class="dot"></span><div>Accessible UI for desktop at mobile</div></div>
      </div>
      <div class="cta-row">
        <button class="btn btn-primary" type="button">Start Demo</button>
        <button class="btn btn-ghost" type="button">Learn More</button>
      </div>
    </section>

    <aside class="login-panel" aria-label="Login panel">
      <h2>Login</h2>
      <p>Welcome back! Please enter your details.</p>
      <form id="loginForm" method="post">
        <div class="field">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" placeholder="example@catsu.edu.ph" required />
        </div>
        <div class="field">
          <label for="studentid">Student ID</label>
          <input id="studentid" name="studentid" type="text" placeholder="2025-12345" required />
        </div>
        <div class="field" style="position: relative;">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" placeholder="MM/DD/YYYY" required minlength="6" />
          <button type="button" id="togglePassword" aria-label="Toggle password visibility" 
            style="position: absolute; right: 12px; top: 38px; background: none; border: none; cursor: pointer; color: var(--muted); font-size: 18px; padding: 0;">
            👁️
          </button>
        </div>
        <div class="row">
          <label class="checkbox"><input type="checkbox" name="remember" /> Remember me</label>
          <a href="#">Forgot password?</a>
        </div>
        <button class="btn btn-primary" type="submit" id="loginBtn" aria-label="Login">Login</button>
      </form>
    </aside>
  </main>

  <footer>
    <div class="foot">
      <span>© <span id="year"></span> iVote • All rights reserved.</span>
      <span>Made for CatSU student elections</span>
    </div>
  </footer>

  <div class="otp-modal" id="otpModal">
    <div class="otp-box">
      <h3>Enter OTP Code</h3>
      <p id="otpMessage">We sent a 6-digit code to your registered email.</p>
      <input type="text" id="otpCode" maxlength="6" class="otp-input" placeholder="••••••" />
      <div class="alert" id="otpAlert"></div>
      <div class="otp-actions">
        <button class="btn btn-ghost" type="button" onclick="closeOtp()">Cancel</button>
        <button class="btn btn-primary" type="button" id="verifyBtn" onclick="verifyOtp()">Verify</button>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('year').textContent = new Date().getFullYear();

    let userRole = null;
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const otpModal = document.getElementById('otpModal');
    const otpAlert = document.getElementById('otpAlert');
    const otpMessage = document.getElementById('otpMessage');
    const verifyBtn = document.getElementById('verifyBtn');

    loginForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      loginBtn.disabled = true;
      loginBtn.innerHTML = 'Sending OTP<span class="spinner"></span>';

      const formData = new FormData(loginForm);

      fetch("login.php", {
        method: "POST",
        body: formData
      })
      .then(res => {
        console.log('Login response status:', res.status);
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
        return res.text();
      })
      .then(text => {
        console.log('Login raw response:', text);
        try {
          return JSON.parse(text);
        } catch (e) {
          console.error('JSON parse error:', e);
          throw new Error('Invalid response from server');
        }
      })
      .then(data => {
        console.log('Login parsed data:', data);
        loginBtn.disabled = false;
        loginBtn.textContent = 'Login';
        
        if (data.status === "success") {
          userRole = data.role;
          const userEmail = document.getElementById('email').value;
          otpMessage.textContent = data.message || `We sent a 6-digit code to ${userEmail}`;
          otpModal.style.display = 'grid';
          document.getElementById('otpCode').focus();
        } else {
          alert(data.message || "Login failed. Please try again.");
        }
      })
      .catch(err => {
        loginBtn.disabled = false;
        loginBtn.textContent = 'Login';
        alert("Error: " + err.message);
        console.error('Login error:', err);
      });
    });

    function closeOtp() {
      otpModal.style.display = 'none';
      document.getElementById('otpCode').value = '';
      otpAlert.textContent = '';
      otpAlert.classList.remove('error');
    }

    function verifyOtp() {
      const code = document.getElementById('otpCode').value.trim();
      
      if (code.length !== 6) {
        otpAlert.textContent = "Please enter a 6-digit code";
        otpAlert.classList.add("error");
        return;
      }

      verifyBtn.disabled = true;
      verifyBtn.innerHTML = 'Verifying<span class="spinner"></span>';

      fetch("verify_otp.php", {
        method: "POST",
        headers: { 
          'Content-Type': 'application/x-www-form-urlencoded',
          'Accept': 'application/json'
        },
        body: `otp=${encodeURIComponent(code)}`
      })
      .then(res => {
        console.log('Response status:', res.status);
        if (!res.ok) {
          throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.text();
      })
      .then(text => {
        console.log('Raw response:', text);
        try {
          return JSON.parse(text);
        } catch (e) {
          console.error('JSON parse error:', e);
          console.error('Response text:', text);
          throw new Error('Invalid JSON response from server');
        }
      })
      .then(data => {
        console.log('Parsed data:', data);
        verifyBtn.disabled = false;
        verifyBtn.textContent = 'Verify';
        
        if (data.status === "success") {
          otpAlert.textContent = "OTP verified! Redirecting...";
          otpAlert.classList.remove("error");
          
          setTimeout(() => {
            if (userRole === "voter") {
              window.location.href = "Voters.php";
            } else if (userRole === "admin") {
              window.location.href = "admin.php";
            } else if (userRole === "commissioner") {
              window.location.href = "commissioners.php";
            } else {
              window.location.href = "home.php";
            }
          }, 1500);
        } else {
          otpAlert.textContent = data.message || "Invalid OTP code. Please try again.";
          otpAlert.classList.add("error");
        }
      })
      .catch(err => {
        console.error('Fetch error:', err);
        verifyBtn.disabled = false;
        verifyBtn.textContent = 'Verify';
        otpAlert.textContent = "Error: " + err.message;
        otpAlert.classList.add("error");
      });
    }

    document.getElementById('otpCode').addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        verifyOtp();
      }
    });

    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('togglePassword');

    togglePasswordBtn.addEventListener('click', () => {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      togglePasswordBtn.textContent = type === 'password' ? '👁️' : '🙈';
    });
  </script>
</body>
</html>