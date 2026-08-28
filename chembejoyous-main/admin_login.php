<?php
require_once __DIR__ . '/admin_config.php';

admin_session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Username and password are required.';
    } else {
        if ($username === ADMIN_USER && password_verify($password, ADMIN_PASS_HASH)) {
            // Successful login
            $_SESSION['is_admin'] = true;
            // create CSRF token for admin actions
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            header('Location: admin_messages.php');
            exit;
        } else {
            $errors[] = 'Invalid username or password.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | Joyous Children's Home</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --purple: #6844a5;
            --deep-purple: #39225f;
            --lavender: #f1eaff;
            --lilac: #dac8f4;
            --ink: #30223f;
            --muted: #79698d;
            --white: #ffffff;
            --danger: #a72e5b;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 2rem 1rem;
            color: var(--ink);
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #f8f3ff 0%, #e8dcfb 100%);
        }

        .login-shell {
            width: min(100%, 440px);
            padding: 2.8rem;
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid var(--lilac);
            border-radius: 24px;
            box-shadow: 0 20px 55px rgba(57, 34, 95, 0.16);
        }

        .brand-mark {
            width: 68px;
            height: 68px;
            display: grid;
            place-items: center;
            margin: 0 auto 1.2rem;
            border-radius: 50%;
            color: var(--deep-purple);
            font-size: 2rem;
            background: var(--lavender);
        }

        h1 {
            margin: 0;
            text-align: center;
            color: var(--deep-purple);
            font-family: 'Playfair Display', serif;
            font-size: 2.1rem;
        }

        .welcome {
            margin: .7rem auto 2rem;
            color: var(--muted);
            text-align: center;
            line-height: 1.6;
        }

        form { display: grid; gap: 1.1rem; }

        label {
            display: block;
            margin-bottom: .4rem;
            color: var(--deep-purple);
            font-size: .9rem;
            font-weight: 800;
        }

        input {
            width: 100%;
            padding: .85rem 1rem;
            border: 1.5px solid var(--lilac);
            border-radius: 12px;
            color: var(--ink);
            background: #fcfaff;
            font: inherit;
            transition: border-color .2s, box-shadow .2s;
        }

        input:focus {
            outline: none;
            border-color: var(--purple);
            box-shadow: 0 0 0 4px rgba(104, 68, 165, .13);
        }

        button {
            margin-top: .3rem;
            padding: .9rem 1rem;
            border: 0;
            border-radius: 12px;
            color: var(--white);
            background: var(--purple);
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            transition: background .2s, transform .2s;
        }

        button:hover { background: var(--deep-purple); transform: translateY(-1px); }
        button:focus-visible { outline: 3px solid var(--lilac); outline-offset: 3px; }

        .error {
            margin-bottom: 1.3rem;
            padding: .9rem 1rem;
            border: 1px solid #edb4c9;
            border-radius: 12px;
            color: var(--danger);
            background: #fff1f6;
        }

        .error ul { margin: 0; padding-left: 1.2rem; }

        @media (max-width: 480px) {
            .login-shell { padding: 2rem 1.3rem; border-radius: 18px; }
            h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
<main class="login-shell">
    <div class="brand-mark" aria-hidden="true">🌻</div>
    <h1>Welcome Back</h1>
    <p class="welcome">Sign in to manage messages for Joyous Children's Home.</p>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?php echo htmlspecialchars($err, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" required autofocus>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>

        <button type="submit">Log In to Admin</button>
    </form>
    </main>
</body>
</html>
