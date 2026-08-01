<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = requireApiAuth();
apiJson(['ok' => true, 'user' => userPayload($user)]);
