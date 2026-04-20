<?php

declare(strict_types=1);

require_once __DIR__ . '/premium_helpers.php';

premium_logout();
header('Location: premium.php');
exit;

