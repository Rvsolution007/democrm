<?php
chdir(dirname(__DIR__));

// Remove the git_ops.php itself before committing
echo shell_exec('git reset HEAD public/git_ops.php 2>&1') . "\n";

echo shell_exec('git add -A 2>&1') . "\n";
echo shell_exec('git commit -m "feat: move Business Prompt to per-business admin, fix emoji encoding, remove WA API card

- Business Query Prompt moved from SA global → admin per-business setting
- Fixed UTF-8 double-encoding (emojis now display correctly)
- Removed WhatsApp API Configuration card from AI Bot tab
- Removed orphaned WhatsApp API sidebar button
- Added saveAiBusinessPrompt route and controller method
- Cleaned up fix scripts" 2>&1') . "\n";

echo "\nDone!\n";

// Self-destruct
unlink(__FILE__);
