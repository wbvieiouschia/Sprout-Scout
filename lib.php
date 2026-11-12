<?php
// Shared code for every page: config, database, login check, header/footer.
session_start();
foreach (@file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $l) {
  if ($l[0] !== '#' && strpos($l, '=')) { [$k, $v] = explode('=', $l, 2); $_ENV[trim($k)] = trim($v); }
}
function env($k) { return $_ENV[$k] ?? ''; }

function db(): PDO {
  static $p;
  if (!$p) {
    try {
      $p = new PDO("pgsql:host=" . env('DB_HOST') . ";port=" . env('DB_PORT') . ";dbname=" . env('DB_NAME') . ";sslmode=require",
        env('DB_USER'), env('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    } catch (Throwable $e) { http_response_code(500); exit('The database is unavailable. Please try again later.'); }
  }
  return $p;
}
function q($sql, $args = []) { $s = db()->prepare($sql); $s->execute($args); return $s; }
function esc($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function csrf() { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function check_csrf() { if (!hash_equals(csrf(), $_POST['csrf'] ?? '')) { http_response_code(400); exit('Invalid request.'); } }
function flash($msg = null, $type = 'ok') {
  if ($msg) { $_SESSION['flash'] = [$msg, $type]; return null; }
  $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

// Talks to Supabase Auth (passwords are hashed by Supabase, never stored by us)
function supa($path, $body) {
  $ch = curl_init(env('SUPABASE_URL') . $path);
  curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => ['apikey: ' . env('SUPABASE_KEY'), 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($body)]);
  $r = curl_exec($ch); curl_close($ch);
  return json_decode($r ?: '[]', true) ?: [];
}

const PAGES = ['dashboard.php' => 'Dashboard', 'farmers.php' => 'Farmers', 'farms.php' => 'Farms', 'crops.php' => 'Crops',
               'plantings.php' => 'Planting', 'harvests.php' => 'Harvest', 'reports.php' => 'Reports'];

// Call at the top of every protected page. Starts the HTML and the header.
function page_start($title, $active) {
  global $ME;
  if (empty($_SESSION['uid'])) { header('Location: index.php'); exit; }
  $p = q("SELECT p.full_name, p.role_id, r.role_name FROM profile p LEFT JOIN role r ON r.role_id = p.role_id WHERE p.user_id = ?", [$_SESSION['uid']])->fetch();
  $ME = ['uid' => $_SESSION['uid'], 'name' => $p['full_name'] ?? $_SESSION['email'], 'role' => $p['role_name'] ?? 'Staff', 'admin' => ($p['role_id'] ?? null) == 1];
  echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sprout Scout – ' . esc($title) . '</title>
  <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&family=Nunito:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css"></head><body>
  <header class="topbar"><a class="logo" href="dashboard.php"><span>🌱</span> Sprout Scout</a><nav>';
  foreach (PAGES as $f => $l) echo '<a href="' . $f . '"' . ($f === $active ? ' class="active"' : '') . '>' . $l . '</a>';
  echo '</nav><div class="userchip">' . esc($ME['name']) . ' <span class="badge">' . esc($ME['role'] === 'Administrator' ? 'Admin' : $ME['role']) . '</span>
  <a class="btn small" href="logout.php">Log out</a></div></header><main>';
  if ($f = flash()) echo '<div class="msg ' . $f[1] . '">' . esc($f[0]) . '</div>';
}
function page_end() { echo '</main></body></html>'; }
