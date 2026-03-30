<?php
echo 'admin hash: ' . password_hash('admin', PASSWORD_BCRYPT) . '<br>';
echo '1234 hash: ' . password_hash('1234', PASSWORD_BCRYPT) . '<br>';