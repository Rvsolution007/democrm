<?php
chdir(dirname(__DIR__));
unlink(__DIR__ . '/fix_gate.php');
echo "<pre>\n";
echo shell_exec('git add -A 2>&1') . "\n";
echo shell_exec('git commit -m "fix: Session Validity and Follow-Up visible for both packages

- Reply Language: only shows for AI Bot package (hasFeature ai_bot)
- Session Validity: shows for both Bot List and AI Bot packages  
- Smart Follow-Up: shows for both Bot List and AI Bot packages
- Fixed duplicate @if/@endif from previous script" 2>&1') . "\n";
echo shell_exec('git log --oneline -3 2>&1') . "\n";
echo "</pre>\n";
unlink(__FILE__);
