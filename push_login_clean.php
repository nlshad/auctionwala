<?php
// push_login_clean.php
$out1 = [];
exec('git add . 2>&1', $out1);

$out2 = [];
exec('git commit -m "Clean up login card: remove Firebase subtitle, API key link, and update button to Sign In with Google" 2>&1', $out2);

$out3 = [];
exec('git push origin main 2>&1', $out3);

echo "<pre>";
echo "GIT ADD:\n" . implode("\n", $out1) . "\n\n";
echo "GIT COMMIT:\n" . implode("\n", $out2) . "\n\n";
echo "GIT PUSH:\n" . implode("\n", $out3) . "\n\n";
echo "</pre>";
