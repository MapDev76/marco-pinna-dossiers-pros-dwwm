<?php
if (!isLoggedIn()) {
    return;
}

require __DIR__ . '/settings-panel.php';
