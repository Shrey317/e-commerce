<?php
// booking_process.php
require_once 'db.php';

// 1. Sanitize & Validate
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$dt_raw  = trim($_POST['datetime'] ?? '');
$people  = (int)($_POST['people'] ?? 0);
$request = trim($_POST['special_request'] ?? '');

$errors = [];
if (!$name)    $errors[] = "Name is required.";
#if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email required.";
if (!$dt = DateTime::createFromFormat('Y-m-d\TH:i', $dt_raw)) {
    // adjust format if using different picker format
    $errors[] = "Date & time format is invalid.";
}
if ($people < 1) $errors[] = "Please select at least one person.";

if ($errors) {
    // Bounce back with errors (you could store in session and redirect)
    echo "<h2>There were errors:</h2><ul>";
    foreach ($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>";
    echo "</ul><p><a href='javascript:history.back()'>Go back</a></p>";
    exit;
}
// 2. Insert into database
$stmt = $mysqli->prepare("
    INSERT INTO reservations
      (name, email, datetime, people, special_request)
    VALUES
      (?, ?, ?, ?, ?)
");
$dt_formatted = $dt->format('Y-m-d H:i:s');  // correct MySQL DATETIME format
$stmt->bind_param('sssis', $name, $email, $dt_formatted, $people, $request);

if (!$stmt->execute()) {
    die("Database error: " . $stmt->error);
}

// 3. Send confirmation email
$to      = $email;
$subject = "Your Reservation at THE GRILLER";
$body    = <<<EOD
Hello {$name},

Thank you for your reservation at THE GRILLER!

Here are the details:
  • Date & Time: {$dt->format('l, F j, Y \a\t g:i A')}
  • Number of People: {$people}
  • Special Request: {$request}

We look forward to serving you soon!

Warm regards,
The Griller Team
EOD;

// Set From header (adjust to a real address on your domain)
$headers = "From: reservations@thegriller.com\r\n"
         . "Reply-To: reservations@thegriller.com\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n";

$mail_sent = mail($to, $subject, $body, $headers);

// 4. Show on-screen confirmation
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Booking Confirmed</title>
  <link href="css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center" style="height:100vh;">
  <div class="text-center">
    <h1 class="text-success mb-4">Thank you, <?= htmlspecialchars($name) ?>!</h1>
    <p>Your table for <?= $people ?> on 
       <strong><?= $dt->format('l, F j, Y \a\t g:i A') ?></strong> has been reserved.</p>

    <?php if ($mail_sent): ?>
      <p>A confirmation email has been sent to your email <strong><?= htmlspecialchars($email) ?></strong>.</p>
    <?php else: ?>
      <p class="text-warning">We were unable to send a confirmation email. Please contact us if you don't receive one shortly.</p>
    <?php endif; ?>

    <a href="index.html" class="btn btn-primary mt-3">Back to Home</a>
  </div>
</body>
</html>
