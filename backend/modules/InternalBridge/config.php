<?php

return [
    // Shared secret between print backend and any sibling service (currently
    // the shop). Set INTERNAL_BRIDGE_TOKEN in .env on both sides. The default
    // value is intentionally weak so it'll error out before reaching prod.
    'token' => env('INTERNAL_BRIDGE_TOKEN', 'dev-internal-token-CHANGE-ME'),
];
