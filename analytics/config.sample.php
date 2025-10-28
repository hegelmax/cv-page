<?php
declare(strict_types=1);

/**
* LOGIN and PASSWORD HASH for logging into analytics (form)
* - plain login (e.g., admin)
* - password — hash only (bcrypt/argon2)
* Can be generated with a separate script:
*   <?php echo password_hash('MySecret123', PASSWORD_DEFAULT);
*/
const ANALYTICS_LOGIN = 'hegel';
const ANALYTICS_PASS_HASH = 'REPLACE_WITH_YOUR_PASSWORD_HASH';