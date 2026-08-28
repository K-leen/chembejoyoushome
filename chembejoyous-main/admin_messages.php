<?php
require_once __DIR__ . '/admin_config.php';
require_once __DIR__ . '/dbconnection.php';
admin_session_start();

// Require authentication
if (empty($_SESSION['is_admin'])) {
    header('Location: admin_login.php');
    exit;
}

$pdo = getDbConnection();

// Ensure message_reply table exists so replies can be recorded and listed.
try {
    $createRepliesSql = "CREATE TABLE IF NOT EXISTS message_reply (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        admin_user VARCHAR(255) NOT NULL,
        subject VARCHAR(255) DEFAULT NULL,
        body TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (message_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($createRepliesSql);
} catch (PDOException $e) {
    // If creation fails (insufficient privileges or other), continue without fatal error.
}


// Handle delete and reply actions (POST)
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $token = $_POST['csrf_token'] ?? '';

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            $errors[] = 'Invalid CSRF token.';
        } elseif ($id <= 0) {
            $errors[] = 'Invalid message id.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM message WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $success = 'Message deleted.';
        }
    } elseif ($action === 'reply') {
        $id = intval($_POST['id'] ?? 0);
        $token = $_POST['csrf_token'] ?? '';
        $reply_subject = trim($_POST['reply_subject'] ?? '');
        $reply_body = trim($_POST['reply_body'] ?? '');

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            $errors[] = 'Invalid CSRF token.';
        } elseif ($id <= 0) {
            $errors[] = 'Invalid message id.';
        } elseif ($reply_body === '') {
            $errors[] = 'Reply body cannot be empty.';
        } elseif (mb_strlen($reply_subject) > 255 || preg_match('/[\r\n]/', $reply_subject)) {
            $errors[] = 'Reply subject must be 255 characters or fewer and cannot contain line breaks.';
        } else {
            // fetch original message to get recipient
            $stmt = $pdo->prepare('SELECT email, name, subject FROM message WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $orig = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$orig) {
                $errors[] = 'Message not found.';
            } elseif (!filter_var($orig['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'The sender has an invalid email address, so a reply cannot be sent.';
            } else {
                $to = $orig['email'];
                $subject = $reply_subject !== '' ? $reply_subject : replySubject($orig['subject'] ?? '');
                $fromEmail = defined('ADMIN_FROM_EMAIL') ? ADMIN_FROM_EMAIL : 'no-reply@example.com';
                $fromName = defined('ADMIN_FROM_NAME') ? ADMIN_FROM_NAME : ADMIN_USER;

                $headers = 'From: ' . $fromName . ' <' . $fromEmail . "\r\n";
                $headers .= 'Reply-To: ' . $fromEmail . "\r\n";
                $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";

                $mailSent = false;
                if (function_exists('mail')) {
                    $mailSent = @mail($to, $subject, $reply_body, $headers);
                }

                if ($mailSent) {
                    $success = 'Reply sent successfully.';
                } else {
                    $errors[] = 'Failed to send reply email. Check mail configuration.';
                }

                if ($mailSent) {
                    try {
                        $stmt = $pdo->prepare('INSERT INTO message_reply (message_id, admin_user, subject, body, created_at) VALUES (:mid, :admin, :subject, :body, NOW())');
                        $stmt->execute([':mid' => $id, ':admin' => ADMIN_USER, ':subject' => $subject, ':body' => $reply_body]);
                    } catch (PDOException $e) {
                        $errors[] = 'Reply was sent, but its history could not be recorded.';
                    }
                }
            }
        }
    }
}

// If an id is provided via GET, show details for that message
$viewMessage = null;
$replies = [];
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT id, name, email, phone, subject, message, created_at FROM message WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $viewMessage = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        // If the message exists, load reply history (if the table exists).
        if ($viewMessage) {
            try {
                $rstmt = $pdo->prepare('SELECT id, admin_user, subject, body, created_at FROM message_reply WHERE message_id = :mid ORDER BY created_at ASC');
                $rstmt->execute([':mid' => $id]);
                $replies = $rstmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Ignore if table does not exist or query fails.
                $replies = [];
            }
        }
    }
}

// Fetch recent messages (limit 200)
$stmt = $pdo->query('SELECT id, name, email, subject, message, created_at FROM message ORDER BY created_at DESC LIMIT 200');
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function replySubject($subject) {
    $subject = trim((string)$subject);
    if ($subject === '') return 'Re: Your message to Joyous Children\'s Home';
    return preg_match('/^re:\s/i', $subject) ? $subject : 'Re: ' . $subject;
}
function replyMailto($email, $subject) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return '#';
    return 'mailto:' . rawurlencode($email) . '?subject=' . rawurlencode(replySubject($subject));
}
function truncate($s, $len = 120) {
    $s = (string)$s;
    if (mb_strlen($s) <= $len) return $s;
    return mb_substr($s, 0, $len - 3) . '...';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Messages | Joyous Children's Home</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        :root{--purple:#6844a5;--deep-purple:#39225f;--lavender:#f1eaff;--lilac:#dac8f4;--ink:#30223f;--muted:#79698d;--white:#fff;--danger:#a72e5b;--line:#eadff8}
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;padding:2rem;color:var(--ink);font-family:'Nunito',sans-serif;background:linear-gradient(135deg,#f8f3ff 0%,#e8dcfb 100%)}
        .admin-shell{max-width:1200px;margin:0 auto;padding:2rem;background:rgba(255,255,255,.96);border:1px solid var(--lilac);border-radius:24px;box-shadow:0 20px 55px rgba(57,34,95,.14)}
        .admin-header{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1.5rem}
        h1,h2,h3{color:var(--deep-purple);font-family:'Playfair Display',serif}
        h1{margin:0;font-size:2.2rem}
        h2{margin-top:0}
        h3{margin-top:1.8rem}
        a{color:var(--purple);font-weight:700}
        .logout-link{padding:.5rem .85rem;border:1px solid var(--lilac);border-radius:9px;text-decoration:none}
        .meta{font-size:.92rem;color:var(--muted)}
        .meta a{font-weight:600}
        .actions{white-space:nowrap}
        .msg{padding:1rem;background:#fcfaff;border:1px solid var(--line);border-radius:10px;white-space:pre-wrap;overflow-wrap:anywhere}
        .msg pre{margin:0;font:inherit;white-space:pre-wrap}
        .success,.error{margin-bottom:1rem;padding:.85rem 1rem;border-radius:10px}
        .success{color:#356b42;background:#effaf0;border:1px solid #afd8b4}
        .error{color:var(--danger);background:#fff1f6;border:1px solid #edb4c9}
        .reply-button,.primary-button{display:inline-block;padding:.48rem .75rem;border-radius:8px;color:var(--white);background:var(--purple);text-decoration:none;font-weight:800}
        .reply-button:hover,.primary-button:hover{background:var(--deep-purple)}
        .danger-button{padding:.48rem .75rem;border:0;border-radius:8px;color:var(--white);background:var(--danger);font:inherit;font-weight:800;cursor:pointer}
        .detail-actions{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-top:1rem}
        hr{margin:2rem 0;border:0;border-top:1px solid var(--line)}
        .reply-form{max-width:700px;display:grid;gap:.55rem}
        .reply-form label{color:var(--deep-purple);font-weight:800}
        .reply-form input,.reply-form textarea{width:100%;padding:.75rem .9rem;border:1.5px solid var(--lilac);border-radius:10px;color:var(--ink);background:#fcfaff;font:inherit}
        .reply-form input:focus,.reply-form textarea:focus{outline:none;border-color:var(--purple);box-shadow:0 0 0 4px rgba(104,68,165,.13)}
        table{width:100%;border-collapse:separate;border-spacing:0;overflow:hidden;border:1px solid var(--line);border-radius:12px;background:var(--white)}
        th,td{padding:.75rem;text-align:left;border-bottom:1px solid var(--line);vertical-align:top}
        th{color:var(--deep-purple);background:var(--lavender);font-size:.85rem;text-transform:uppercase;letter-spacing:.04em}
        tr:last-child td{border-bottom:0}
        tbody tr:hover{background:#fcfaff}
        @media (max-width:800px){body{padding:1rem}.admin-shell{padding:1.2rem;border-radius:16px}.admin-header{align-items:flex-start;flex-direction:column}table{display:block;overflow-x:auto;white-space:nowrap}th,td{min-width:120px}.msg{white-space:pre-wrap}}
    </style>
</head>
<body>
<main class="admin-shell">
    <header class="admin-header"><h1>Messages</h1><a class="logout-link" href="admin_logout.php">Log out</a></header>

    <?php if ($success): ?><div class="success"><?php echo e($success); ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="error"><ul><?php foreach ($errors as $err) echo '<li>' . e($err) . '</li>'; ?></ul></div><?php endif; ?>

    <?php if ($viewMessage): ?>
        <h2>Message #<?php echo e($viewMessage['id']); ?></h2>
        <p class="meta">From: <?php echo e($viewMessage['name']); ?> &lt;<a href="mailto:<?php echo e($viewMessage['email']); ?>"><?php echo e($viewMessage['email']); ?></a>&gt; — <?php echo e($viewMessage['created_at']); ?></p>
        <p><strong>Subject:</strong> <?php echo e($viewMessage['subject']); ?></p>
        <div class="msg"><pre><?php echo e($viewMessage['message']); ?></pre></div>

        <div class="detail-actions">
            <form method="post" onsubmit="return confirm('Delete this message?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo e($viewMessage['id']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                <button class="danger-button" type="submit">Delete</button>
            </form>
            <a class="reply-button" href="<?php echo e(replyMailto($viewMessage['email'], $viewMessage['subject'])); ?>">Reply in Email</a>
            <a href="admin_messages.php">Back to list</a>
        </div>

        <hr>

        <h3>Reply history</h3>
        <?php if (!empty($replies)): ?>
            <?php foreach ($replies as $r): ?>
                <div style="border:1px solid #eee;padding:.5rem;margin-bottom:.5rem;">
                    <div class="meta">By <?php echo e($r['admin_user']); ?> on <?php echo e($r['created_at']); ?><?php if (!empty($r['subject'])) { echo ' — ' . e($r['subject']); } ?></div>
                    <div class="msg"><pre><?php echo e($r['body']); ?></pre></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No replies yet.</p>
        <?php endif; ?>

        <h3>Reply to sender</h3>
        <form class="reply-form" method="post">
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="id" value="<?php echo e($viewMessage['id']); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">

            <label for="reply_subject">Subject</label>
            <input id="reply_subject" name="reply_subject" type="text" value="<?php echo e(replySubject($viewMessage['subject'] ?? '')); ?>" maxlength="255">

            <label for="reply_body">Message</label>
            <textarea id="reply_body" name="reply_body" rows="8" required></textarea>

            <button class="primary-button" type="submit">Send Reply</button>
        </form>

    <?php else: ?>
        <p>Showing up to 200 most recent messages.</p>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>From</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Received</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($messages)): ?>
                    <tr><td colspan="7">No messages found.</td></tr>
                <?php else: foreach ($messages as $m): ?>
                    <tr>
                        <td><?php echo e($m['id']); ?></td>
                        <td><?php echo e($m['name']); ?></td>
                        <td><?php echo e($m['email']); ?></td>
                        <td><?php echo e($m['subject']); ?></td>
                        <td><?php echo e(truncate($m['message'], 100)); ?></td>
                        <td><?php echo e($m['created_at']); ?></td>
                        <td class="actions"><a class="reply-button" href="<?php echo e(replyMailto($m['email'], $m['subject'])); ?>">Reply</a> <a href="admin_messages.php?id=<?php echo e($m['id']); ?>">View</a></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

</body>
</html>
