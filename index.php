<?php require 'facebook.php';

$facebook = new Facebook(array(
                             'appId'  => '147201912033054',
                             'secret' => '61ef5734be20d29715ef1133e9456834',
                         ));

$user = $facebook->getUser();

if ($user) {
    try {
        $user_feed = $facebook->api('/me/home');
    } catch (FacebookApiException $e) {
        error_log($e);
        $user = null;
    }
}

if ($user) {
    $logoutUrl = $facebook->getLogoutUrl();
} else {
    $loginUrl = $facebook->getLoginUrl();
}

<!doctype html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
  <head>
    <title>Feed Vote</title>
  </head>
  <body>
    <?php 
       if ($user) {
           echo $user_feed;
       } else {
           echo "<a href='" + $loginUrl + "'>Login with Facebook</a>";
       }
    ?>
  </body>
</html>
