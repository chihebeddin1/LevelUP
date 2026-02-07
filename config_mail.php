<?php
// config_mail.php
// Configuration SMTP pour PHPMailer

// Paramètres SMTP (exemple pour Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'uplevel206@gmail.com'); // Votre email Gmail
define('SMTP_PASSWORD', 'eulovvzkixiahwwp'); // Mot de passe d'application
define('SMTP_FROM_EMAIL', 'uplevel206@gmail.com');
define('SMTP_FROM_NAME', 'LEVELUP');

// Délai d'expiration du code (en secondes)
define('CODE_EXPIRATION', 900); // 15 minutes
?>