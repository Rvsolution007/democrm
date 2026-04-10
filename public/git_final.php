<?php
chdir(dirname(__DIR__));
echo "<pre>\n";
echo shell_exec('git add -A 2>&1') . "\n";
echo shell_exec('git commit -m "cleanup: remove temp git_ops script" 2>&1') . "\n";
echo shell_exec('git log --oneline -5 2>&1') . "\n";
echo "</pre>\n";
