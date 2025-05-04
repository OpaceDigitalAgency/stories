<?php
// debug_dump.php

// Bypass any router auth/logic:
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
  'debug' => 'hello from debug_dump',
  'time'  => date('c')
]);
exit;