<?php
require 'lib.php';
if (!empty($_SESSION['uid'])) { header('Location: dashboard.php'); exit; }
$msg = null; $type = 'error'; $signup = ($_POST['mode'] ?? '') === 'signup' || isset($_GET['signup']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $email = trim($_POST['email'] ?? ''); $pw = $_POST['password'] ?? ''; $name = trim($_POST['full_name'] ?? '');
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pw === '') $msg = 'Please enter a valid email and password.';
  elseif ($signup && $name === '') $msg = 'Please enter your full name.';
  elseif ($signup && strlen($pw) < 6) $msg = 'Password must be at least 6 characters.';
  else {
    $r = $signup ? supa('/auth/v1/signup', ['email' => $email, 'password' => $pw, 'data' => ['full_name' => $name]])
                 : supa('/auth/v1/token?grant_type=password', ['email' => $email, 'password' => $pw]);
    $user = $r['user'] ?? null;
    if ($user && !empty($r['access_token'])) {
      session_regenerate_id(true);
      $_SESSION['uid'] = $user['id']; $_SESSION['email'] = $email;
      header('Location: dashboard.php'); exit;
    }
    if ($signup && !isset($r['error_code']) && !isset($r['msg'])) { $msg = 'Account created! Check your email to confirm it, then log in.'; $type = 'ok'; $signup = false; }
    else $msg = $signup ? 'Could not create the account. The email may already be used.' : 'Wrong email or password.';
  }
}
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sprout Scout – Log in</title>
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&family=Nunito:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css"></head><body>
<div class="login-page"><div class="login-form-wrap">
  <h2><?= $signup ? 'Join Sprout Scout' : 'Log into Sprout Scout' ?></h2>
  <form method="post" novalidate>
    <input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="mode" value="<?= $signup ? 'signup' : 'login' ?>">
    <?php if ($signup): ?><label for="full_name">Full name</label><input id="full_name" name="full_name" value="<?= esc($_POST['full_name'] ?? '') ?>"><?php endif; ?>
    <label for="email">Email</label><input id="email" name="email" type="email" value="<?= esc($_POST['email'] ?? '') ?>">
    <label for="password">Password</label><input id="password" name="password" type="password">
    <button type="submit" class="wide"><?= $signup ? 'Create Account' : 'Log In' ?></button>
  </form>
  <?php if ($msg): ?><div class="msg <?= $type ?>"><?= esc($msg) ?></div><?php endif; ?>
  <hr class="divider">
  <a class="btn wide" style="text-align:center" href="index.php<?= $signup ? '' : '?signup=1' ?>"><?= $signup ? 'Back to log in' : 'Create new account' ?></a>
  <div class="footnote"><div class="footnote-brand">🌱 Sprout Scout</div><div class="footnote-tag">Watch your fields grow.</div></div>
</div></div></body></html>
