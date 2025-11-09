<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';

function cc_send_mail(string $to, string $subject, string $html): void {
    $cfg = cc_env();
    $smtp = $cfg['smtp'] ?? [];
    if (empty($smtp['enabled'])) {
        return; // email delivery disabled by configuration
    }
    $from_email = $smtp['from_email'] ?? 'noreply@localhost';
    $from_name = $smtp['from_name'] ?? 'Creative Chaos';
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= 'From: ' . sprintf('"%s" <%s>', $from_name, $from_email) . "\r\n";
    @mail($to, $subject, $html, $headers);
}

function cc_notify_registration(int $reg_id): void {
    $pdo = cc_db();
    $st = $pdo->prepare('SELECT * FROM registrations WHERE id = ?');
    $st->execute([$reg_id]);
    $reg = $st->fetch();
    if (!$reg) {
        return;
    }

    $type = strtoupper((string) $reg['registration_type']);
    $summaryParts = [];
    $summaryParts[] = '<strong>Type:</strong> ' . htmlspecialchars($type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    if ($reg['registration_type'] === 'team') {
        $summaryParts[] = '<strong>Team:</strong> ' . htmlspecialchars((string) $reg['team_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $summaryParts[] = '<strong>School:</strong> ' . htmlspecialchars((string) $reg['school'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    } else {
        $summaryParts[] = '<strong>School:</strong> ' . htmlspecialchars((string) $reg['school'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    $summaryParts[] = '<strong>Contact Email:</strong> ' . htmlspecialchars((string) $reg['guardian_email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $summaryParts[] = '<strong>Grade:</strong> ' . htmlspecialchars((string) $reg['grade'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $summaryParts[] = '<strong>Category:</strong> ' . htmlspecialchars((string) $reg['category'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $summaryParts[] = '<strong>Writers:</strong> ' . (int) $reg['writer_count'];
    if ($reg['registration_type'] === 'team') {
        $summaryParts[] = '<strong>Extra Writers:</strong> ' . (int) $reg['extra_writers'];
    }
    $summaryParts[] = '<strong>Estimated Fee:</strong> $' . number_format((float) $reg['fee'], 2);
    $summary = '<p>' . implode('<br>', $summaryParts) . '</p>';

    $writersHtml = '';
    $wq = $pdo->prepare('SELECT writer_name, writer_email, writer_phone FROM registration_writers WHERE registration_id = ? ORDER BY id ASC');
    $wq->execute([$reg_id]);
    $writers = $wq->fetchAll();
    if ($writers) {
        $items = [];
        foreach ($writers as $w) {
            $name = htmlspecialchars((string) $w['writer_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            if ($reg['registration_type'] === 'open') {
                $email = htmlspecialchars((string) ($w['writer_email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $phone = htmlspecialchars((string) ($w['writer_phone'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $items[] = sprintf('<li>%s — %s — %s</li>', $name, $email, $phone);
            } else {
                $items[] = '<li>' . $name . '</li>';
            }
        }
        if ($items) {
            $writersHtml = '<h4>Writer List</h4><ul>' . implode('', $items) . '</ul>';
        }
    }

    $bodyContent = $summary . $writersHtml;

    $to = $reg['guardian_email'];
    if ($to) {
        $subject_user = 'Creative Chaos Registration Received (ID ' . $reg_id . ')';
        $body_user = '<h3>Thank you! Your registration was received.</h3>' . $bodyContent . '<p>We will follow up with schedule and details. If you have questions, reply to this email.</p>';
        cc_send_mail($to, $subject_user, $body_user);
    }

    $cfg = cc_env();
    $admins = $cfg['admin_emails'] ?? [];
    if (is_array($admins)) {
        foreach ($admins as $adminEmail) {
            if (!$adminEmail) {
                continue;
            }
            $subject_admin = '[CC] New Registration #' . $reg_id;
            $body_admin = '<h3>New Registration</h3>' . $bodyContent . '<p><em>Sent automatically by the portal.</em></p>';
            cc_send_mail($adminEmail, $subject_admin, $body_admin);
        }
    }
}
