<?php
// Hide any error messages
error_reporting(0);ini_set('display_errors', 0);
// 30 seconds maximum execution time
ini_set('max_execution_time', 30);

include('../session.php');
require('./discordauthentication/discordauthentication.php');
?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Discord Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha2/css/bootstrap.min.css" integrity="sha384-DhY6onE6f3zzKbjUPRc2hOzGAdEf4/Dz+WJwBvEYL/lkkIsI3ihufq9hk9K4lVoK" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="text-center">
<main>
<?php
// Our output here
if(session('discord_id')) {
    // User is logged in
    ?>
    <img class="mb-4 rounded-circle" src="https://cdn.discordapp.com/avatars/<?=session('discord_id')?>/<?=session('discord_avatar')?>.png?size=256" alt="" width="72" height="72" onerror="this.src = 'assets/images/Discord_icon.svg'">
    <h1 class="h3 mb-3 font-weight-normal"><?=session('discord_username')?>#<?=session('discord_discriminator')?></h1>
    <p class="text-muted"><small><?=session('discord_id')?></small></p>
    <p>
        <?php
        // Show the users profile badges
        for ($i = 0; $i < 20; $i++){
            if (session('discord_public_flags') & (1 << $i))
                echo '<img height="16px" width="16px" src="assets/images/badges/' . $i . '.png" onerror="this.remove();"/>';
        }
        ?>
    </p>
    <a class="btn btn-lg btn-danger btn-block" href="?action=logout">LOG OUT</a>
    <?php
} else {
    // User is not logged in
    ?>
    <img class="mb-4" src="assets/images/Discord_logo.svg" alt="" width="100%">
    <a class="btn btn-lg btn-discord btn-block" href="?action=login&return_uri=<?=$_SERVER['REQUEST_URI']?>">LOG IN</a>
    <?php
}

// Check if we have an invite link and server ID set
if (defined('DISCORD_SERVER_ID') && defined('DISCORD_SERVER_INVITE')) {
    ?>
    <a href="<?=DISCORD_SERVER_INVITE?>">
        <img class="mt-4 mb-2" height="28px" alt="Join our Discord!" src="https://img.shields.io/discord/<?=DISCORD_SERVER_ID?>?color=7289DA&label=discord%20server&logo=discord&logoColor=7289DA&style=for-the-badge"/>
    </a>
    <?php
}
?>
</main>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha2/js/bootstrap.bundle.min.js" integrity="sha384-BOsAfwzjNJHrJ8cZidOg56tcQWfp6y72vEJ8xQ9w6Quywb24iOsW913URv1IS4GD" crossorigin="anonymous"></script>
</body>