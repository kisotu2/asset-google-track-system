<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Asset Management Login</title>

<style>
    :root {
        --primary: #0f4c81;      /* Deep Blue */
        --secondary: #00b4a0;    /* Teal */
        --light-bg: #f4f9fb;
        --text-dark: #1f2d3d;
        --text-light: #6c7a89;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', sans-serif;
    }

    body {
        background: var(--light-bg);
        display: flex;
        height: 100vh;
    }

    .container {
        display: flex;
        width: 100%;
    }

    /* LEFT SIDE */
    .left {
        flex: 1;
        background: var(--primary);
        color: white;
        padding: 60px;
        border-top-right-radius: 120px;
        border-bottom-right-radius: 120px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .logo {
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 40px;
    }

    .logo span {
        color: var(--secondary);
    }

    .illustration {
        margin: 30px 0;
        font-size: 80px;
    }

    .left h1 {
        font-size: 32px;
        margin-bottom: 15px;
    }

    .left p {
        font-size: 15px;
        color: #dbe9f4;
    }

    /* RIGHT SIDE */
    .right {
        flex: 1;
        background: white;
        padding: 80px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .top-links {
        position: absolute;
        top: 30px;
        right: 60px;
    }

    .top-links a {
        margin-left: 20px;
        text-decoration: none;
        color: var(--primary);
        font-weight: 500;
    }

    .top-links .signin {
        background: var(--secondary);
        color: white;
        padding: 8px 18px;
        border-radius: 20px;
    }

    h2 {
        margin-bottom: 30px;
        color: var(--primary);
    }

    label {
        font-size: 14px;
        color: var(--text-light);
    }

    input {
        width: 100%;
        padding: 12px;
        margin: 8px 0 20px 0;
        border: 1px solid #dce3ea;
        border-radius: 8px;
        outline: none;
    }

    input:focus {
        border-color: var(--secondary);
    }

    .forgot {
        text-align: right;
        font-size: 13px;
        margin-bottom: 20px;
    }

    .forgot a {
        text-decoration: none;
        color: var(--secondary);
    }

    .btn-login {
        background: var(--primary);
        color: white;
        border: none;
        padding: 12px;
        width: 100%;
        border-radius: 25px;
        cursor: pointer;
        font-size: 15px;
        transition: 0.3s;
    }

    .btn-login:hover {
        background: var(--secondary);
    }

    .footer-links {
        position: absolute;
        bottom: 20px;
        left: 60px;
        font-size: 13px;
        color: white;
    }

    @media(max-width: 900px){
        .container{
            flex-direction: column;
        }
        .left{
            border-radius: 0;
            text-align: center;
        }
        .right{
            padding: 40px;
        }
    }

</style>
</head>
<body>

<div class="container">

    <!-- LEFT SIDE -->
    <div class="left">
        <div class="logo">FCMB <span>Asset Management</span></div>

        <div class="illustration">📊</div>

        <h1>We manage your assets <br> so you grow confidently</h1>
        <p>Focus on what matters while we take care of your financial growth.</p>

        <div class="footer-links">
            Terms & Conditions | FAQs | Contact Us
        </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="right">

        <div class="top-links">
            <a href="register.php">Sign up</a>
            <a href="login.php" class="signin">Sign in</a>
        </div>

        <h2>Welcome back</h2>

        <form>
            <label>Email Address</label>
            <input type="email" placeholder="hello@example.com" required>

            <label>Password</label>
            <input type="password" placeholder="************" required>

            <div class="forgot">
                <a href="#">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-login">Login →</button>
        </form>

    </div>

</div>

</body>
</html>