<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/at-config.php';

//If user is logged in, redirect to search-books page
if (isset($_COOKIE['access_token'])) {
    $access_token = $_COOKIE['access_token'];
    if (getAccessToken($access_token, $mysqli)->num_rows != 0) {
        header("Location: /search-books/");
        exit;
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
    break;
    case 'POST':
        $username = $mysqli->real_escape_string($_POST['username']);
        $password = $mysqli->real_escape_string($_POST['password']);

        //Login
        $login_query = "SELECT id FROM `user` WHERE username = '$username' and password = '$password'";

        if (!$ids = $mysqli->query($login_query)) {
            echo "Failed to run query: (" . $mysqli->errno . ") " . $mysqli->error;
            exit;
        }

        //Error if login or username is invalid
        if ($ids->num_rows === 0) {
            echo "Your Login Username or Password is invalid";
            echo '<br/><button type="button" onclick="window.history.back()">Back</button>';
            exit;
        }
        
        $id = $ids->fetch_assoc();
        
        $id = $id['id'];
        $access_token = generateAccessToken();
        $user_browser = getUserBrowser();
        $user_ip = getUserIP();
        $expiry_time = generateExpiryTime();
        
        // $update_access_token_query = "UPDATE `user` SET access_token = '$access_token' WHERE id = '$id'";
        $insert_access_token_query = "INSERT INTO access_info (token, user_id, user_browser, user_ip, expiry_time) VALUES ($access_token, $id, '$user_browser', '$user_ip', '$expiry_time')";
        
        while (!$mysqli->query($insert_access_token_query)) {
            $access_token = generateAccessToken();
        }

        setcookie("access_token", $access_token, time() + COOKIE_EXPIRY_TIME, "/");

        //Redirect
        header("Location: /search-books/");

        exit;
    default:
        http_response_code(405);
        exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Login</title>
    <link rel="stylesheet" href ="login.css" type="text/css"/>
</head>

<body>
    <img id="mute-button" src="sound-on.jpg"/>
    <video autoplay loop><source src ="bg.mp4" type ="video/mp4"></video>
    <main>
        <h1>Login</h1>
        <form method="post">
            <div class="input">
                <label for="username">Username</label>
                <span>
                    <input type="text" id="username" name="username" /><br />
                    <span id="username-error"></span>
                </span>
            </div>
            <div class="input">
                <label for="password">Password</label>
                <span>
                    <input type="password" id="password" name="password" /><br />
                    <span id="password-error"></span>
                </span>
            </div>
            <div class="hyperlink">
                <a href="/register/">Don't have an account?</a>
            </div>
            <div class="button">
                <button>Login</button>
            </div>
        </form>
    </main>
    <script src="validation.js" type="module"></script>
    <script src="mute.js" type="module"></script>
</body>

</html>