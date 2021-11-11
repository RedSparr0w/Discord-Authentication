<?php
require(__DIR__ . '/config.php');

$authorizeURL = 'https://discord.com/api/oauth2/authorize';
$tokenURL = 'https://discord.com/api/oauth2/token';
$apiURLBase = 'https://discord.com/api/users/@me';
$revokeURL = 'https://discordapp.com/api/oauth2/token/revoke';

if (get('return_uri')) {
    $_SESSION['return_uri'] = get('return_uri');
}

// Start the login process by sending the user to Discord's authorization page
if(get('action') == 'login') {
    session_start();

    $params = array(
        'client_id' => OAUTH2_CLIENT_ID,
        'redirect_uri' => OAUTH2_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'identify'
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
    
    // Go back to homepage
    header('Location: /');
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
  
    // Dump user data if debugging
    // var_dump($user); die();
    
    $_SESSION['discord_id'] = $user->id;
    $_SESSION['discord_avatar'] = $user->avatar ?? '';
    $_SESSION['discord_username'] = $user->username;
    $_SESSION['discord_discriminator'] = $user->discriminator;
    $_SESSION['discord_public_flags'] = $user->public_flags ?? 0;

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

// If user logged in
if(session('discord_id')) {
    // If return uri is set
    if (session('return_uri')) {
        header('Location: ' . session('return_uri'));
        unset($_SESSION['return_uri']);
        die('Redirecting to ' . session('return_uri'));
    }
}