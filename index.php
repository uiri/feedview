<?php header("Content-type: text/html; charset=utf-8");
require 'facebook.php';

$facebook = new Facebook(array(
                             'appId'  => '227291683992589',
                             'secret' => 'a36035f90d7be029402e9a3960fc2e58',
                         ));

$user = $facebook->getUser();

if ($user) {
  try {
    $user_feed = $facebook->api('/me/home', array('limit' => '150'));
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
    <div id="fb-root"></div>
    <script src="http://connect.facebook.net/en_US/all.js"></script>
    <script>
      FB.init({
          appId  : '227291683992589',
          status : true, // check login status
          cookie : true, // enable cookies to allow the server to access the session
          xfbml  : true, // parse XFBML
          channelURL : 'http://uiri.servehttp.com/channel.php', // channel.html file
          oauth  : true // enable OAuth 2.0
      });
      function LikeSomething(id) {
          FB.api('/'+id+'/likes', 'post');
          setTimeout("reloadLikes('"+id+"')", 1500);
      }
      function reloadLikes(id) {
          FB.api('/'+id, function (response) {
              var likeHtml = "";
              if (response.likes.data == undefined) {
                  if (response.likes > 1) {
                      document.getElementById("likes_"+id).innerHTML = "<br><strong>" + comment['likes'] + "people</strong> like this comment.";
                  } else {
                      document.getElementById("likes_"+id).innerHTML = "<br><strong>1 person</strong> likes this comment.";
                  }
              } else {
                  var others = response['likes']['count'] - response['likes']['data'].length;
                  var j = 0;
                  if (response['likes']['data'].length == 1) {
                      likeHtml += "<strong>" + response['likes']['data'][0].name + "</strong>";
                  } else {
                      for (like in response['likes']['data']) {
                          if (response['likes']['data'].length == (1 + j)) { 
                              if (others == 0) {
                                  likeHtml += "and ";
                              }
                              likeHtml += "<strong>";
                              likeHtml += response['likes']['data'][like].name;
                              likeHtml += "</strong>";
                          } else {
                              j++;
                              likeHtml += "<strong>";
                              likeHtml += response['likes']['data'][like].name;
                              likeHtml += "</strong>";
                              if (response['likes']['data'].length == (1 + j) && others == 0) {
                                  likeHtml += " ";
                              } else {
                                  likeHtml += ", ";
                              }
                          }
                      }
                  }
                  if (others == 0) {
                      if (response['likes']['data'].length == 1) {
                          likeHtml += " likes this.";
                      } else {                               
                          likeHtml += " like this.";
                      }
                  } else if (others == 1) {
                      likeHtml += " and one other like this.";
                  } else {
                      likeHtml += " and " + others + " others like this.";
                  }
                  document.getElementById("likes_"+id).innerHTML = likeHtml + "<br>";
              }
          });
      }
    </script>
    <?php
        if ($user) {
           echo "<a href='" . $logoutUrl . "'>Logout</a><br><br>";
           $data = $user_feed['data'];
           $first = "0";
           $i = 0;
           while($first == "0") {
               $feed_item = $data[$i];
               if ($feed_item && !(preg_match("/friends/", $feed_item['story'])) && !(preg_match("/wrote on/", $feed_item['story'])) && !(preg_match("/likes/", $feed_item['story']) && !(preg_match("/likes a link/", $feed_item['story']))) && !($feed_item['type'] == "photo" && $feed_item['application']['canvas_name']) && !(preg_match("/video/", $feed_item['story']) && $feed_item['type'] == "status")) {
                   $first = $data[$i];
               } else {
                   $i++;
               }
           }
           foreach ($data as $feed_item)
               if ($feed_item && !(preg_match("/friends/", $feed_item['story'])) && !(preg_match("/wrote on/", $feed_item['story'])) && !(preg_match("/likes/", $feed_item['story']) && !(preg_match("/likes a link/", $feed_item['story']))) && !($feed_item['type'] == "photo" && $feed_item['application']['canvas_name']) && !(preg_match("/video/", $feed_item['story']) && $feed_item['type'] == "status")) {
                   $wall = preg_replace("/\_(.+)/", "", $feed_item['id']);
                   if($feed_item != $first) {
                       echo "<div style='display:block;clear:left'><hr></div>";
                   }
                   $endOfUrl = str_replace("_", "/posts/", $feed_item['id']);
                   echo "<span style='float:right'>";
                   echo "[<a href='http://facebook.com/{$endOfUrl}'>View on Facebook</a>]</span>";
                   echo "<div style='float:left;padding-right:3px;padding-left:3px'>"; 
                   echo "<img height='70px' src='http://graph.facebook.com/" . $feed_item['from']['id'] . "/picture'></div>";
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
                       echo "</strong> " . str_replace("\n", "<br>", $feed_item['message']);
                   } else {
                       if ($feed_item['type'] == "link") {
                           echo str_replace($feed_item['name'], "<a href='" . $feed_item['link'] . "'>" . $feed_item['name'] . "</a>", str_replace($feed_item['from']['name'], "<strong>" . $feed_item['from']['name'] . "</strong>", $feed_item['story']));
                       } else {
                           $story = $feed_item['story'];
                           if ($feed_item['story_tags']) {
                               foreach ($feed_item['story_tags'] as $tag_array) foreach ($tag_array as $tag)
                                   $story = str_replace($tag['name'], "<strong>" . $tag['name'] . "</strong>", $story);
                           }
                           echo str_replace($feed_item['from']['name'], "<strong>" . $feed_item['from']['name'] . "</strong>", $story);
                       }
                   }
                   if ($feed_item['place'] != NULL) {
                       echo " at <strong><span title='{$feed_item['place']['location']['street']}'>{$feed_item['place']['name']}</span></strong>";
                   }
                   if ($feed_item['with_tags'] != NULL) {
                       echo " <strong>with</strong> ";
                       foreach ($feed_item['with_tags']['data'] as $tag)
                           if ($tag) {
                               $r++;
                               echo $tag['name'];
                               if (count($feed_item['with_tags']['data']) != 1) {
                                   if (count($feed_item['with_tags']['data']) == ($r + 1)) {
                                       echo " and ";
                                   } else if (count($feed_item['with_tags']['data']) > ($r + 1)) {
                                       echo ", ";
                                   }
                               }
                           }
                   }
                   if($feed_item['type'] == "status") {
                       echo "<br>";
                   } else if ($feed_item['type'] == "link") { 
                       echo "<div style='font-size:small;margin-left:80px;width:300px'>";
                       if ($feed_item['story'] == NULL) {
                           echo "<a href='" . $feed_item['link'] . "'>" . $feed_item['name'] . "</a><br>" . $feed_item['description'] . "</div>";
                       } else {
                           foreach ($feed_item['properties'] as $property)
                               echo $property['text'] . "<br>";
                       }
                       echo "</div>";
                   }else if ($feed_item['type'] == "photo") {
                       $image = preg_replace("/_s.jpg$/", "_b.jpg", $feed_item['picture']);
                       echo "<br><img max-height='600px' src='" . $image . "'><br>";
                   } else if ($feed_item['type'] == "video") {
                       $source = str_replace('&autoplay=1', '', $feed_item['source']);
                       echo "<br><embed autoStart='1' width='420' height='315' src='" . $source . "'><br>";
                   } else {
                       echo "This is an item of type " . $feed_item['type'] . " which is not handled yet. Please inform the developer.";
                       print_r($feed_item);
                   }
                   if (preg_match("/likes a link/", $feed_item['story'])) {
                       echo "<div style='font-size:small;margin-left:80px;width:300px'><a href='" . $feed_item['link'] . "'>" . $feed_item['name'] . "</a><br>" . $feed_item['description'] . "</div>";
                   }
                   echo "<div style='margin-left:80px'>[<a href='javascript:LikeSomething(\"{$feed_item['id']}\");'>Like</a>]</div>";
                   if($feed_item['likes']['count'] != NULL || $feed_item['comments']['count'] != NULL) {
                       echo "<div style='margin-left:80px;background-color:#dde3ee;border-width:2px;border-bottom-width:2em;border-color:#dde3ee;border-style:solid'>";
                   }
                   if($feed_item['likes']['count'] != NULL) {
                       echo "<div id='likes_{$feed_item['id']}'>";
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
                           echo $like_people . " and one other like this.";
                       } else {
                           echo $like_people . " and " . $others . " others like this.";
                       }
                       echo "</div>";
                   }
                   $like_people = "";
                   if ($feed_item['comments']['count'] != 0) {
                       $j = 0;
                       if (count($feed_item['comments']['data']) < $feed_item['comments']['count']) {
                           $l = 0;
                           $comments_data = $facebook->api("/" . $feed_item['id'], array('fields' => 'comments'));
                           echo "<span id='show_{$feed_item['id']}' onClick='document.getElementById(\"{$feed_item['id']}\").style.display = \"block\";";
                           echo "document.getElementById(\"show_{$feed_item['id']}\").style.display = \"none\"'>Show all <strong>{$feed_item['comments']['count']}</strong> comments.<br></span>";
                           echo "<div id='{$feed_item['id']}' style='display:none'>";
                           echo "<span onClick='document.getElementById(\"{$feed_item['id']}\").style.display = \"none\";";
                           echo "document.getElementById(\"show_{$feed_item['id']}\").style.display = \"inline\"'>Hide all comments except last <strong>two</strong>.<br></span>";
                           foreach ($comments_data['comments']['data'] as $comment) {
                               if ($comment == $feed_item['comments']['data'][0]) {
                                   break;
                               }
                               if ($l == 0) {
                                   $l++;
                               } else {
                                   echo "<div style='display:block;clear:left;visibility:hidden'><hr></div>";
                               }
                               echo "<div style='float:left;padding-right:3px'><img src='http://graph.facebook.com/" . $comment['from']['id'] . "/picture'></div>";
                               echo "<strong>" . $comment['from']['name'];
                               echo "</strong> " . str_replace("\n", "<br>", $comment['message']);
                               echo "<span id='likes_{$comment['id']}'>";
                               if ($comment['likes'] != NULL) {
                                   if ($comment['likes'] == 1) {
                                       echo "<br><strong>1 person</strong> likes this comment.";
                                   } else {
                                       echo "<br><strong>{$comment['likes']} people</strong> like this comment.";
                                   }
                               }
                               echo "</span><br>[<a href='javascript:LikeSomething(\"{$comment['id']}\");'>Like</a>]";
                           }
                           echo "<div style='clear:left;visibility:hidden'><hr></div></div>";
                       }
                       foreach ($feed_item['comments']['data'] as $comment) {
                           if ($j == 0) {
                               $j++;
                           } else {
                               echo "<div style='display:block;clear:left;visibility:hidden'><hr></div>";
                           }
                           echo "<div style='float:left;padding-right:3px'><img src='http://graph.facebook.com/" . $comment['from']['id'] . "/picture'></div>";
                           echo "<strong>" . $comment['from']['name'];
                           echo "</strong> " . str_replace("\n", "<br>", $comment['message']);
                           echo "<span id='likes_{$comment['id']}'>";
                           if ($comment['likes'] != NULL) {
                               if ($comment['likes'] == 1) {
                                   echo "<br><strong>1 person</strong> likes this comment.";
                               } else {
                                   echo "<br><strong>{$comment['likes']} people</strong> like this comment.";
                               }
                           }
                           echo "</span><br>[<a href='javascript:LikeSomething(\"{$comment['id']}\");'>Like</a>]";
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
