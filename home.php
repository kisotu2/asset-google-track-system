<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>IRA Asset Management System</title>

<style>

:root {
    --primary: #003366;
    --secondary: #C8102E;
    --accent: #F2A900;
    --light-bg: #eef2f7;
    --text-dark: #1f2d3d;
    --text-light: #6c7a89;
}

/* Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

/* Animated Background */
body {
    height: 100vh;
    background:linear-gradient(to right,#b08116,#99bb4f);
    background-size: 400% 400%;
    animation: gradientMove 12s ease infinite;
    display: flex;
    border-radius: 0 0 20px 20px;
}

@keyframes gradientMove {
    0% {background-position: 0% 50%;}
    50% {background-position: 100% 50%;}
    100% {background-position: 0% 50%;}
}

/* Layout */
.container {
    display: flex;
    width: 100%;
}

/* LEFT PANEL */
.left {
    flex: 1;
    padding: 60px;
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.ira-logo {
    width: 170px;
    margin-bottom: 25px;
}

.system-title {
    font-size: 30px;
    font-weight: 600;
    margin-bottom: 10px;
}

.motto {
    color: var(--accent);
    font-style: italic;
    margin-bottom: 30px;
}

.description {
    max-width: 400px;
    color: #d6e2f0;
}

/* RIGHT PANEL */
.right {
    flex: 1;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    padding: 80px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
}

/* Login Card */
.login-card {
    background: white;
    padding: 45px;
    border-radius: 18px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}

h2 {
    color: var(--primary);
    margin-bottom: 25px;
}

/* Inputs */
input {
    width: 100%;
    padding: 14px;
    margin: 10px 0 20px;
    border-radius: 10px;
    border: 1px solid #dce3ea;
    transition: 0.3s;
}

input:focus {
    border-color: var(--secondary);
    box-shadow: 0 0 0 3px rgba(200,16,46,0.1);
}

/* Password Wrapper */
.password-wrapper {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 15px;
    top: 18px;
    cursor: pointer;
    font-size: 14px;
    color: var(--text-light);
}

/* Remember Me */
.remember {
    display: flex;
    align-items: center;
    font-size: 14px;
    margin-bottom: 20px;
}

.remember input {
    width: auto;
    margin-right: 8px;
}

/* Button */
.btn-login {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 30px;
    background:linear-gradient(to right,#b08116,#99bb4f);
    color: white;
    font-size: 15px;
    cursor: pointer;
    transition: 0.3s;
}

.btn-login:hover {
    transform: translateY(-2px);
    opacity: 0.95;
}

/* Footer */
.system-footer {
    position: absolute;
    bottom: 20px;
    right: 60px;
    font-size: 12px;
    color: #555;
}

/* Responsive */
@media(max-width:900px){
    .container {
        flex-direction: column;
    }
    .right {
        padding: 40px;
    }
}

</style>
</head>
<body>

<div class="container">

    <!-- LEFT -->
    <div class="left">
        <img src="IRA.png" class="ira-logo">
        <div class="system-title">Asset Management System</div>
        <div class="motto">Promoting insurance. Protecting the insured.</div>
        <div class="description">
            Securely monitor and manage ICT assets across departments with transparency and accountability.
        </div>
    </div>

    <!-- RIGHT -->
    <div class="right">

        <div class="login-card">
            <h2>Welcome Back</h2>

            <form>
                <label>Email Address</label>
                <input type="email" placeholder="hello@example.com" required>

                <label>Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" placeholder="************" required>
                    <span class="toggle-password" onclick="togglePassword()">Show</span>
                </div>

                <div class="remember">
                    <input type="checkbox">
                    <label>Remember Me</label>
                </div>

                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>

        <div class="system-footer">
            IRA Asset Management System by L.Blessings | © 2026 Insurance Regulatory Authority
        </div>

    </div>

</div>

<script>
function togglePassword() {
    const password = document.getElementById("password");
    const toggle = document.querySelector(".toggle-password");
    
    if (password.type === "password") {
        password.type = "text";
        toggle.textContent = "Hide";
    } else {
        password.type = "password";
        toggle.textContent = "Show";
    }
}
</script>

</body>
</html>