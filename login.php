<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ? AND is_active = 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            $conn->query("UPDATE users SET last_login = NOW() WHERE id = " . $user['id']);
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        }
    }
    $error = 'Invalid username or password';
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-logo">
                <i class="fab fa-whatsapp"></i>
                <h1>WhatsApp Marketing</h1>
                <p>Sign in to your dashboard</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" required autofocus placeholder="Enter username">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" required placeholder="Enter password">
                </div>
                <button type="submit" class="btn btn-wa btn-block">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            <div class="login-hint">
                <i class="fas fa-info-circle"></i> Default: admin / admin123
            </div>
        </div>
    </div>
</body>
</html>
