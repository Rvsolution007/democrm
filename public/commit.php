<?php
chdir(dirname(__DIR__));
// Delete temp scripts
foreach (['fix_gate.php', 'git_final.php'] as $f) {
    if (file_exists(__DIR__ . '/' . $f)) unlink(__DIR__ . '/' . $f);
}

echo "<pre>\n";
echo shell_exec('git add -A 2>&1') . "\n";
echo shell_exec('git commit -m "fix: correct feature gate for admin settings sections

- Reply Language: only visible for AI Bot package (hasFeature ai_bot)
- Session Validity & Follow-Up: visible for both packages (whatsapp_connect)
- Business Query Prompt: visible for all whatsapp_connect users
- Fixed unclosed @if in backup section
- Added @if(hasFeature ai_bot) to sidebar section" 2>&1') . "\n";
echo shell_exec('git log --oneline -3 2>&1') . "\n";
echo "</pre>\n";
unlink(__FILE__);
