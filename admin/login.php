<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

// If already admin, redirect to dashboard
if (isAdmin()) {
    redirect(BASE_URL . '/admin/dashboard.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$phone || !$password) {
        $errors[] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare('SELECT id, password, role FROM users WHERE phone = ? LIMIT 1');
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                if ($user['role'] === 'admin') {
                    refreshUserSession($mysqli, $user['id']);
                    redirect(BASE_URL . '/admin/dashboard.php');
                } else {
                    $errors[] = 'คุณไม่มีสิทธิ์ในการเข้าถึงระบบผู้ดูแลระบบ';
                }
            } else {
                $errors[] = 'เบอร์โทรศัพท์หรือรหัสผ่านไม่ถูกต้อง';
            }
        } else {
            $errors[] = 'เบอร์โทรศัพท์หรือรหัสผ่านไม่ถูกต้อง';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบผู้ดูแลระบบ | สมาร์ท รีไซเคิล ช็อป</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        /* Dedicated premium styling for Admin Login */
        body.admin-login-body {
            background: radial-gradient(circle at 50% 50%, #1e1b4b 0%, #0f172a 100%);
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            overflow-x: hidden;
            position: relative;
        }

        /* Decorative glowing ambient spots */
        .ambient-glow-1 {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, rgba(99, 102, 241, 0) 70%);
            top: -100px;
            left: -100px;
            z-index: 1;
            pointer-events: none;
        }

        .ambient-glow-2 {
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.08) 0%, rgba(16, 185, 129, 0) 70%);
            bottom: -200px;
            right: -100px;
            z-index: 1;
            pointer-events: none;
        }

        .admin-login-container {
            width: 100%;
            max-width: 460px;
            z-index: 10;
            position: relative;
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .admin-login-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 48px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 50px rgba(99, 102, 241, 0.1);
        }

        .admin-login-header {
            text-align: center;
            margin-bottom: 36px;
        }

        .admin-login-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.3);
            color: #ffffff;
        }

        .admin-login-logo svg {
            width: 32px;
            height: 32px;
        }

        .admin-login-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }

        .admin-login-subtitle {
            color: #94a3b8;
            font-size: 0.95rem;
        }

        .admin-login-form .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .admin-login-form label {
            display: block;
            color: #cbd5e1;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 8px;
            letter-spacing: 0.025em;
        }

        .admin-login-form .input-wrapper {
            position: relative;
        }

        .admin-login-form .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            display: flex;
            align-items: center;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .admin-login-form input {
            width: 100%;
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 14px 16px 14px 48px;
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.25s ease;
            box-sizing: border-box;
        }

        .admin-login-form input:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(30, 41, 59, 0.8);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
        }

        .admin-login-form input:focus + .input-icon {
            color: #6366f1;
        }

        .admin-login-form input::placeholder {
            color: #475569;
        }

        .admin-login-btn {
            width: 100%;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
            margin-top: 8px;
        }

        .admin-login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
            filter: brightness(1.1);
        }

        .admin-login-btn:active {
            transform: translateY(0);
        }

        .admin-login-alerts {
            margin-bottom: 24px;
        }

        .admin-login-alert {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 14px 16px;
            border-radius: 12px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-in-out;
        }

        .admin-login-footer {
            text-align: center;
            margin-top: 32px;
        }

        .admin-login-back-link {
            color: #94a3b8;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
        }

        .admin-login-back-link:hover {
            color: #6366f1;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        @media (max-width: 480px) {
            .admin-login-card {
                padding: 32px 24px;
            }
        }
    </style>
</head>
<body class="admin-login-body">
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-login-header">
                <div class="admin-login-logo">
                    <!-- Key / Shield Dashboard Lock SVG Icon -->
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h1 class="admin-login-title">แผงควบคุมร้านค้า</h1>
                <p class="admin-login-subtitle">ระบบจัดการ สมาร์ท รีไซเคิล ช็อป</p>
                
                <!-- Admin Credentials Info Box -->
                <div style="margin-top: 20px; padding: 12px; background: rgba(99, 102, 241, 0.15); border: 1px dashed rgba(99, 102, 241, 0.4); border-radius: 12px; text-align: left; font-size: 0.85rem; box-shadow: 0 4px 12px rgba(0,0,0,0.25);">
                    <div style="color: #a5b4fc; font-weight: bold; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                        <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        บัญชีทดสอบระบบผู้ดูแลระบบ (Admin)
                    </div>
                    <div style="color: #cbd5e1; line-height: 1.5;">
                        <strong>เบอร์โทรศัพท์:</strong> <code style="background: rgba(0,0,0,0.4); padding: 2px 6px; border-radius: 4px; color: #fff; font-family: monospace;">0900000000</code><br>
                        <strong>รหัสผ่าน:</strong> <code style="background: rgba(0,0,0,0.4); padding: 2px 6px; border-radius: 4px; color: #fff; font-family: monospace;">password</code>
                    </div>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="admin-login-alerts">
                    <?php foreach ($errors as $error): ?>
                        <div class="admin-login-alert">
                            <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <span><?= sanitize($error) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form class="admin-login-form" method="POST" action="">
                <div class="form-group">
                    <label for="phone">เบอร์โทรศัพท์ผู้ดูแลระบบ</label>
                    <div class="input-wrapper">
                        <input type="text" id="phone" name="phone" placeholder="เช่น 0900000000" value="<?= sanitize($_POST['phone'] ?? '') ?>" required autocomplete="tel">
                        <span class="input-icon">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="ระบุรหัสผ่านของท่าน" required autocomplete="current-password">
                        <span class="input-icon">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </span>
                    </div>
                </div>

                <button type="submit" class="admin-login-btn">เข้าสู่ระบบควบคุมร้าน</button>
            </form>

            <div class="admin-login-footer">
                <a href="<?= BASE_URL ?>/" class="admin-login-back-link">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    กลับสู่หน้าร้านหลัก
                </a>
            </div>
        </div>
    </div>
</body>
</html>
