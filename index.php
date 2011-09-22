<?php require 'facebook.php';

$facebook = new Facebook(array(
                             'appId'  => '227291683992589',
                             'secret' => 'a36035f90d7be029402e9a3960fc2e58',
                         ));

$user = $facebook->getUser();

if ($user) {
  try {
    $user_profile = $facebook->api('/me');
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

?>
<!doctype html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
  <head>
    <title>Feed Vote</title>
  </head>
  <body>
    <?php 
       if ($user) {
           echo "<a href='" . $logoutUrl . "'>Logout</a><br><br>";
           $data = $user_feed['data'];
           $first = "0";
           $i = 0;
           while($first == "0") {
               if ( !(preg_match("/friends/", $data[$i]['story']))) {
                   $first = $data[$i];
               } else {
                   $i++;
               }
           }
           foreach ($data as $feed_item)
               if ($feed_item && !(preg_match("/friends/", $feed_item['story'])) && !(preg_match("/wrote on/", $feed_item['story'])) && !(preg_match("/likes/", $feed_item['story']) && !(preg_match("/likes a link/", $feed_item['story'])))) {
                   $wall = preg_replace("/\_(.+)/", "", $feed_item['id']);
                   if($feed_item != $first) {
                       echo "<div style='display:block;clear:left'><hr></div>";
                   }
                   echo "<div style='float:left;padding-right:3px;padding-left:3px'><img height='70px' src='http://graph.facebook.com/" . $feed_item['from']['id'] . "/picture'></div>";
                   if($feed_item['story'] == NULL) {
                       echo "<strong>" . $feed_item['from']['name'];
                       if ($wall != $feed_item['from']['id']) {
                           echo "</strong> &rarr; <strong>";
                           foreach ($feed_item['to']['data'] as $person_to)
                               if ($person_to['id'] == $wall) {
                                   echo $person_to['name'];
                               }
                           echo "</strong> ";
                       }
                       echo "</strong> " . $feed_item['message'];
                       if($feed_item['type'] == "status") {
                           echo "<br>";
                       } else if ($feed_item['type'] == "link") {
                           echo "<br><div style='font-size:small;margin-left:80px;width:300px'><a href='" . $feed_item['link'] . "'>" . $feed_item['name'] . "</a><br>" . $feed_item['description'] . "</div>";
                       }else if ($feed_item['type'] == "photo") {
                           $image = preg_replace("/_s.jpg$/", "_b.jpg", $feed_item['picture']);
                           echo "<br><img max-height='600px' src='" . $image . "'><br>";
                       } else if ($feed_item['type'] == "video") {
                           echo "<br><iframe width='420' height='315' src='" . $feed_item['source'] . "?autoplay=1'></iframe><br>";
                       } else {
                           echo "This is an item of type " . $feed_item['type'] . " which is not handled yet. Please inform the developer.";
                           print_r($feed_item);
                       }
                   } else {
                       echo str_replace($feed_item['from']['name'], "<strong>" . $feed_item['from']['name'] . "</strong>", $feed_item['story']) . "<br>";
                       if ($feed_item['type'] == "photo") {
                           $image = preg_replace("/_s.jpg$/", "_b.jpg", $feed_item['picture']);
                           echo "<img max-height='600px' src='" . $image . "'>";
                       } else if (preg_match("/likes a link/", $feed_item['story'])) {
                           echo "<div style='font-size:small;margin-left:80px;width:300px'><a href='" . $feed_item['link'] . "'>" . $feed_item['name'] . "</a><br>" . $feed_item['description'] . "</div>";
                       } else {
                           print_r($feed_item);
                       }
                   }
                   if($feed_item['likes']['count'] != NULL || $feed_item['comments']['count'] != NULL) {
                       echo "<div style='margin-left:80px;background-color:#dde3ee;border-width:2px;border-bottom-width:2em;border-color:#dde3ee;border-style:solid'>";
                   }
                   if($feed_item['likes']['count'] != NULL) {
                       $others = $feed_item['likes']['count'] - count($feed_item['likes']['data']);
                       $j = 0;
                       if (count($feed_item['likes']['data']) == 1) {
                           $like_people = "<strong>" . $feed_item['likes']['data'][0]['name'] . "</strong>";
                       } else {
                           foreach ($feed_item['likes']['data'] as $like)
                               if ($like) {
                                   if (count($feed_item['likes']['data']) == (1 + $j)) { 
                                       if ($others == 0) {
                                           $like_people .= "and ";
                                       }
                                       $like_people .= "<strong>";
                                       $like_people .= $like['name'];
                                       $like_people .= "</strong>";
                                   } else {
                                       $j++;
                                       $like_people .= "<strong>";
                                       $like_people .= $like['name'];
                                       $like_people .= "</strong>";
                                       if (count($feed_item['likes']['data']) == (1 + $j) && $others == 0) {
                                           $like_people .= " ";
                                       } else {
                                           $like_people .= ", ";
                                       }
                                   }
                               }
                       }
                       if ($others == 0) {
                           if (count($feed_item['likes']['data']) == 1) {
                               echo $like_people . " likes this.";
                           } else {                               
                               echo $like_people . " like this.";
                           }
                       } else if ($others == 1) {
                           echo $like_people . " and " . $others . " other like this.";
                       } else {
                           echo $like_people . " and " . $others . " others like this.";
                       }
                       echo "<br>";
                   }
                   $like_people = "";
                   if ($feed_item['comments']['count'] != 0) {
                       $j = 0;
                       foreach ($feed_item['comments']['data'] as $comment) {
                           if ($j == 0) {
                               $j++;
                           } else {
                               echo "<div style='display:block;clear:left;visibility:hidden'><hr></div>";
                           }
                           echo "<div style='float:left;padding-right:3px'><img src='http://graph.facebook.com/" . $comment['from']['id'] . "/picture'></div>";
                           echo "<strong>" . $comment['from']['name'];
                           echo "</strong> " . $comment['message'];
//                       print_r($feed_item['comments']);
                       }
                   }
                   if($feed_item['likes']['count'] != NULL || $feed_item['comments']['count'] != NULL) {
                       echo "</div><br>";
                   }
               }
       } else {
           echo "<a href='" . $loginUrl . "&scope=read_stream,publish_stream'>Login with Facebook</a>";
       }
    ?>
  </body>
</html>
