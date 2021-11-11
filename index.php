<?php
// Hide any error messages
error_reporting(0);ini_set('display_errors', 0);
// 30 seconds maximum execution time
ini_set('max_execution_time', 30);

include('../session.php');
require('./config.php');

$authorizeURL = 'https://discord.com/api/oauth2/authorize';
$tokenURL = 'https://discord.com/api/oauth2/token';
$apiURLBase = 'https://discord.com/api/users/@me';
$revokeURL = 'https://discordapp.com/api/oauth2/token/revoke';

// Start the login process by sending the user to Discord's authorization page
if(get('action') == 'login') {
    session_start();

    $params = array(
        'client_id' => OAUTH2_CLIENT_ID,
        'redirect_uri' => OAUTH2_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'identify guilds'
    );
    
    // Redirect the user to Discord's authorization page
    header('Location: https://discordapp.com/api/oauth2/authorize?' . http_build_query($params));
    die();
}

if(get('action') == 'logout') {
    apiRequest($revokeURL, array(
        'token' => session('discord_access_token'),
        'client_id' => OAUTH2_CLIENT_ID,
        'client_secret' => OAUTH2_CLIENT_SECRET,
      ));
      
    // Log user out of our site
    unset($_SESSION['discord_access_token']);
    unset($_SESSION['discord_id']);
    unset($_SESSION['discord_avatar']);
    unset($_SESSION['discord_username']);
    unset($_SESSION['discord_discriminator']);
    unset($_SESSION['discord_public_flags']);
    
    // Reload page
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    die();
}

// When Discord redirects the user back here, there will be a "code" and "state" parameter in the query string
if(get('code')) {
    // Exchange the auth code for a token
    $token = apiRequest($tokenURL, array(
        'grant_type' => 'authorization_code',
        'client_id' => OAUTH2_CLIENT_ID,
        'client_secret' => OAUTH2_CLIENT_SECRET,
        'redirect_uri' => OAUTH2_REDIRECT_URI,
        'code' => get('code')
    ));
    
    if ($token->error) {
        // Dump the token data if debugging
        // var_dump($token);
        die('Something went wrong, try again later..');
    }
    
    $_SESSION['discord_access_token'] = $token->access_token;
    
    $user = apiRequest($apiURLBase);
    
    $_SESSION['discord_id'] = $user->id;
    $_SESSION['discord_avatar'] = $user->avatar;
    $_SESSION['discord_username'] = $user->username;
    $_SESSION['discord_discriminator'] = $user->discriminator;
    $_SESSION['discord_public_flags'] = $user->public_flags;

    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
}

function apiRequest($url, $post=FALSE, $headers=array()) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    $response = curl_exec($ch);


    if($post)
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));

    $headers[] = 'Accept: application/json';

    if(session('discord_access_token'))
        $headers[] = 'Authorization: Bearer ' . session('discord_access_token');

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    return json_decode($response);
}

function get($key, $default=NULL) {
    return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
}

function session($key, $default=NULL) {
    return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
}

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
    <img class="mb-4 rounded-circle" src="https://cdn.discordapp.com/avatars/<?=$_SESSION['discord_id']?>/<?=$_SESSION['discord_avatar']?>.png?size=256" alt="" width="72" height="72" onerror="this.src = 'assets/images/Discord_icon.svg'">
    <h1 class="h3 mb-3 font-weight-normal"><?=$_SESSION['discord_username']?>#<?=$_SESSION['discord_discriminator']?></h1>
    <p class="text-muted"><small><?=$_SESSION['discord_id']?></small></p>
    <a class="btn btn-lg btn-danger btn-block" href="?action=logout">LOG OUT</a>
    <?php
} else {
    // User is not logged in
    ?>
    <img class="mb-4" src="assets/images/Discord_logo.svg" alt="" width="100%">
    <a class="btn btn-lg btn-discord btn-block" href="?action=login">LOG IN</a>
    <?php
}

// Check if we have an invite link and server ID set
if (defined('DISCORD_SERVER_ID') && defined('DISCORD_SERVER_INVITE')) {
    ?>
    <a href="<?=DISCORD_SERVER_INVITE?>">
        <img class="mt-5 mb-3" alt="Join our Discord!" src="https://img.shields.io/discord/<?=DISCORD_SERVER_ID?>?color=7289DA&label=discord%20server&logo=discord&logoColor=7289DA&style=for-the-badge"/>
    </a>
    <?php
}
?>
</main>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha2/js/bootstrap.bundle.min.js" integrity="sha384-BOsAfwzjNJHrJ8cZidOg56tcQWfp6y72vEJ8xQ9w6Quywb24iOsW913URv1IS4GD" crossorigin="anonymous"></script>
</body>