<?php header("Content-type: text/html; charset=utf-8");
require 'facebook.php';

$facebook = new Facebook(array(
                             'appId'  => '',
                             'secret' => '',
                         ));

$user = $facebook->getUser();
$token = $facebook->getAccessToken();

if ($_GET['limit']) {
    $limit = $_GET['limit'];
    if (intval($_GET['limit']) > 150) {
        $limit = '150';
    }     
} else {
    $limit = '20';
}

if ($user) {
    try {
        $user_feed = $facebook->api('/me/home', array('limit' => $limit));
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
    <style>
      .black_overlay{
        display: none;
        position: absolute;
        top: 0%;
        left: 0%;
        width: 100%;
        height: 100%;
        background-color: black;
        z-index:1001;
        -moz-opacity: 0.8;
        opacity:.80;
        filter: alpha(opacity=80);
      }
      .white_content {
      	display: none;
        position: absolute;
        top: 25%;
        left: 25%;
        width: 50%;
        height: 50%;
        padding: 16px;
        border: 16px solid blue;
        background-color: white;
        z-index:1002;
      }
    </style>     
  </head>
  <body style="word-wrap:break-word">
    <div id="fb-root"></div>
    <script src="http://connect.facebook.net/en_US/all.js"></script>
    <script>
    FB.init({
        appId  : '',
        status : true, // check login status
        cookie : true, // enable cookies to allow the server to access the session
        xfbml  : true, // parse XFBML
        channelURL : 'http://xqz.ca/channel.php', // channel.html file
        oauth  : true // enable OAuth 2.0
    });
function LikeSomething(id) {
    FB.api('/'+id+'/likes', 'post');
    if (id.match(/_(.+)_/)) {
        postid = id.replace(/_(.+)_(.+)$/, "_$1");
        setTimeout("reloadComments(\""+postid+"\")", 1500);
    } else {
        document.getElementById('like_link_'+id).innerHTML = "[<a href='javascript:UnlikeSomething(\""+id+"\");'>Unlike</a>]";
        setTimeout("reloadLikes('"+id+"')", 1500);
    }
}
function ReplyTo(comment, post) {
    var textarea = document.getElementById("textarea_"+post);
    textarea.value = "#"+comment+" "+textarea.value;
}
function UnlikeSomething(id) {
    FB.api('/'+id+'/likes', 'delete');
    document.getElementById('like_link_'+id).innerHTML = "[<a href='javascript:LikeSomething(\""+id+"\");'>Like</a>]";
    setTimeout("reloadLikes('"+id+"')", 1500);
}
function CommentSomething(id) {
    FB.api('/'+id+'/comments', 'post', { message: document.forms['comment_form_'+id].elements['message'].value});
    document.forms['comment_form_'+id].elements['message'].value = "";
    setTimeout("reloadComments(\""+id+"\")", 1500);
}
function toggleExtraComments(id) {
    if (document.getElementById("extra_comments_"+id).style.display == "none") {
        document.getElementById("extra_comments_"+id).style.display = "inline";
        document.getElementById("toggle_link_"+id).innerHTML = "Hide extra comments.";
    } else {
        document.getElementById("extra_comments_"+id).style.display = "none";
        document.getElementById("toggle_link_"+id).innerHTML = "Show extra comments.";
    }
}
function showCommentForm(id) {
    document.getElementById("post_comment_"+id).style.display = "inline";
}
function reloadComments(id) {
    FB.api('/'+id, function (response) {
        var j = 0;
        var newCommentHtml = "";
        if (response['comments']['data'].length < response['comments']['count']) {
            newCommentHtml +="<a href='javascript:showComments(\""+response['id']+"\", \""+response['comments']['data'][0]['id']+"\")'>Show all "+response['comments']['count']+" comments.</a><br></div>";
        }
        for (comment in response['comments']['data']) {
            var re = /^#(\d+)\s/;
            if (re.test(response['comments']['data'][comment]['message'])) {
                newCommentHtml += "<div title='"+response['comments']['data'][comment]['message'].match(re)[1]+"' class='put_in'>";
                var comment_message = response['comments']['data'][comment]['message'].replace("\n", "<br>").replace(re, "");
            } else {
                var comment_message = response['comments']['data'][comment]['message'].replace("\n", "<br>");
            }
            newCommentHtml += "<div style='float:left;padding-right:3px'><img src='http://graph.facebook.com/"+response['comments']['data'][comment]['from']['id']+"/picture'></div>";
            newCommentHtml += "<strong>"+response['comments']['data'][comment]['from']['name'];
            newCommentHtml += "</strong> " + comment_message;
            newCommentHtml += "<span id='likes_"+response['comments']['data'][comment]['id']+"'>";
            if (response['comments']['data'][comment]['likes'] != null) {
                if (response['comments']['data'][comment]['likes'] == 1) {
                    newCommentHtml += "<br><strong>1 person</strong> likes this comment.";
                } else {
                    newCommentHtml += "<br><strong>"+response['comments']['data'][comment]['likes']+" people</strong> like this comment.";
                }
            }
            var comment_id = response['comments']['data'][comment]['id'].match(/_(\d+)$/);
            newCommentHtml += "</span><br>[<a href='javascript:LikeSomething(\""+response['comments']['data'][comment]['id']+"\");'>Like</a>][<a href='javascript:ReplyTo(\""+comment_id[1]+"\", \""+id+"\")'>Reply To</a>]";
            newCommentHtml += "<div style='display:block;clear:left;visibility:hidden'><hr></div>";
            newCommentHtml += "<div id='"+comment_id[1]+"' style='padding-left:50px'></div>";
            if (re.test(response['comments']['data'][comment]['message'])) {
                newCommentHtml += "</div>";
            }
        }
        newCommentHtml += "<div style='visibility:hidden;clear:left;display:block'><hr></div>";
        document.getElementById('full_comments_'+id).innerHTML = newCommentHtml;
        thread_comments();
    });
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
                    document.getElementById("likes_"+id).style = 'margin-left:80px;background-color:#dde3ee;border-width:2px;border-color:#dde3ee;border-style:solid';
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
function showNotifications(id) {
    FB.api("/"+id+"/notifications", function(response){
        if (document.getElementById('shownotificationlink').innerHTML == "Show Notifications")
            document.getElementById('shownotificationlink').innerHTML = "Refresh Notifications";
        if (response.error) {
            FB.ui({method: 'permissions.request',
            perms: 'manage_notifications',
            display: 'popup'
                  });
        }
        notehtml = "<ul>";
        for (notification in response['data']){
            notehtml += "<li><a href='javascript:;' onclick='markNotificationUnread(\""+response['data'][notification]['id']+"\");window.open(\""+response['data'][notification]['link']+"\", \"_blank\"); return false;'>"+response['data'][notification]['title']+"</a></li>";
        }
        notehtml += "</ul>";
        document.getElementById('notificationsgohere').innerHTML = notehtml;
           }
        );
}
function markNotificationUnread(id) {
            FB.api("/"+id+"?unread=0", "post");
}
function showComments(id, lastcomment) {
    var l = 0;
    FB.api("/"+id, function(response) {
        var commentHtml = "<a href='javascript:toggleExtraComments(\""+id+"\")'><div style='display:inline' id='toggle_link_"+id+"'>Hide extra comments.</div></a><br><div style='display:inline' id='extra_comments_"+id+"'>";
        for (comment in response['comments']['data']) {
            if (response['comments']['data'][comment]['id'] == lastcomment) {
                break;
            }
            var re = /^#(\d+)\s/;
            if (re.test(response['comments']['data'][comment]['message'])) {
                commentHtml += "<div title='"+response['comments']['data'][comment]['message'].match(re)[1]+"' class='put_in'>";
                var comment_message = response['comments']['data'][comment]['message'].replace("\n", "<br>").replace(re, "");
            } else {
                var comment_message = response['comments']['data'][comment]['message'].replace("\n", "<br>");
            }
            commentHtml += "<div style='float:left;padding-right:3px'><img src='http://graph.facebook.com/"+response['comments']['data'][comment]['from']['id']+"/picture'></div><strong>"+response['comments']['data'][comment]['from']['name']+"</strong> "+comment_message+"<span id='likes_"+response['comments']['data'][comment]['id']+"'>";
            if (response['comments']['data'][comment]['likes'] != null) {
                if (response['comments']['data'][comment]['likes'] == 1) {
                    commentHtml += "<br><strong>1 person</strong> likes this comment.";
                } else {
                    commentHtml += "<br><strong>"+response['comments']['data'][comment]['likes']+" people</strong> like this comment.";
                }
            }
            var comment_id = response['comments']['data'][comment]['id'].match(/_(\d+)$/);
            commentHtml += "</span><br>[<a href='javascript:LikeSomething(\""+response['comments']['data'][comment]['id']+"\");'>Like</a>][<a href='javascript:ReplyTo(\""+comment_id[1]+"\", \""+id+"\")'>Reply To</a>]";
            commentHtml += "<div style='display:block;clear:left;visibility:hidden'><hr></div>";
            commentHtml += "<div id='"+comment_id[1]+"' style='padding-left:50px'></div>";
            if (re.test(response['comments']['data'][comment]['message'])) {
                commentHtml += "</div>";
            }
        }
        document.getElementById('comments_'+id).innerHTML = commentHtml + "</div><div style='display:block;clear:left;visibility:hidden'><hr></div>";
        thread_comments();
    });
}   
    </script>
    <?php

    function show_feed_item($item) {
        if (!(preg_match("/now friends/", $item['story'])) && 
            !(preg_match("/wrote on/", $item['story'])) &&
            !(preg_match("/hbd/", strtolower($item['message']))) &&
            !(preg_match("/happy birthday/", strtolower($item['message']))) &&
            !(preg_match("/likes/", $item['story']) && !(preg_match("/likes a link/", $item['story']))) &&
            !(preg_match("/commented on a/", $item['story'])) &&
            !($item['story'] != NULL && $item['type'] == "link" && !($item['properties'])) &&
            !($item['story'] == NULL && $item['message'] == NULL && $item['type'] == "photo") &&
            !(preg_match("/tagged in/", $item['story']) && $item['type'] == "status" && preg_match("/post/", $item['story'])) &&
            !(preg_match("/video/", $item['story']) && $item['type'] == "status") &&
            !(preg_match("/change/", $item['story']) && preg_match("/profile picture/", $item['story'])) &&
            !($item['application']['canvas_name'] && $item['application']['canvas_name'] != "fbtouch" && $item['application']['canvas_name'] != "video") &&
            !($item['application']['namespace'] && $item['application']['namespace'] != "fbtouch" && $item['application']['namespace'] != "twitter" && $item['application']['namespace'] != "bandpage")) {
            return true;
        }
        return false;
    }

    if ($user) {
        echo "<a href='" . $logoutUrl . "'>Logout of Facebook</a><div style='display:inline;margin-left:30%;'><a id='shownotificationlink' href='javascript:showNotifications({$user});'>Show Notifications</a></div><span style='float:right'>Approximate Posts to Show: [<a href='/feedview/'>20</a>] [<a href='/feedview/50/'>50</a>] [<a href='/feedview/100/'>100</a>] [<a href='/feedview/150/'>150</a>]</span><br><br>\n";
        echo "<div id='notificationsgohere'></div>";
        $data = $user_feed['data'];
        $first = "0";
        $i = 0;
        while($first == "0") {
            $feed_item = $data[$i];
            if (show_feed_item($feed_item)) {
                $first = $data[$i];
            } else {
                $i++;
            }
        }
        foreach ($data as $feed_item)
            if (show_feed_item($feed_item)) {
                $wall = preg_replace("/\_(.+)/", "", $feed_item['id']);
                if($feed_item != $first) {
                    echo "<div style='display:block;clear:left'><hr></div>\n";
                }
                $endOfUrl = str_replace("_", "/posts/", $feed_item['id']);
                echo "<span style='float:right'>";
                echo "[<a href='javascript:;' onclick='window.open(\"http://facebook.com/{$endOfUrl}\", \"_blank\"); return false;'>View on Facebook</a>]</span>\n";
                echo "<div style='float:left;padding-right:3px;padding-left:3px'>\n"; 
                echo "<img height='70px' src='http://graph.facebook.com/" . $feed_item['from']['id'] . "/picture'>\n</div>\n";
                //print_r($feed_item);
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
                    $message = $feed_item['message'];
                    if ($feed_item['message_tags'] != NULL) {
                        foreach ($feed_item['message_tags'] as $tag_array) foreach ($tag_array as $tag)
                            $message = str_replace($tag['name'], "<strong>" . $tag['name'] . "</strong>", $message);
                    }
                    echo "</strong> " . str_replace("\n", "<br>", $message) . "\n";
                } else {
                    if ($feed_item['type'] == "link") {
                        echo str_replace($feed_item['name'], "<a href='javascript:;' onclick='window.open(\"" . $feed_item['link'] . "\", \"_blank\");'>" . $feed_item['name'] . "</a>", str_replace($feed_item['from']['name'], "<strong>" . $feed_item['from']['name'] . "</strong>", $feed_item['story']));
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
                    if ($feed_item['type'] == "checkin") {
                        echo " is";
                    }
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
                if($feed_item['type'] == "status" || $feed_item['type'] == "checkin") {
                    echo "<br>";
                } else if ($feed_item['type'] == "link" || ($feed_item['type'] == "photo" && !($feed_item['picture']))) { 
                    echo "<div style='font-size:small;margin-left:80px;width:300px'>\n";
                    if ($feed_item['story'] == NULL || $feed_item['type'] == "photo") {
                        echo "<a href='javascript:;' onclick='window.open(\"" . $feed_item['link'] . "\", \"_blank\");'>" . $feed_item['name'] . "</a><br>" . $feed_item['description'];
                        if ($feed_item['type'] == "photo") {
                            echo $feed_item['caption'];
                        }
                        echo "\n</div>\n";
                    } else {
                        if ($feed_item['properties']) {
                            foreach ($feed_item['properties'] as $property)
                                echo $property['text'] . "<br>";
                        }
                        if (preg_match("/http:\/\/www.facebook.com\/event.php/", $feed_item['link'])) {
                            echo "<div id='rsvp_{$feed_item['object_id']}'>\nAttend? [<a href='javascript:FB.api(\"/\"+{$feed_item['object_id']}+\"/attending\", \"post\");document.getElementById(\"rsvp_{$feed_item['object_id']}\").innerHTML=\"You are attending this event.\"'>Yes</a>] [<a href='javascript:FB.api(\"/\"+{$feed_item['object_id']}+\"/maybe\", \"post\");document.getElementById(\"rsvp_{$feed_item['object_id']}\").innerHTML=\"You might be attending this event.\"'>Maybe</a>] [<a href='javascript:FB.api(\"/\"+{$feed_item['object_id']}+\"/declined\", \"post\");document.getElementById(\"rsvp_{$feed_item['object_id']}\").innerHTML=\"You are <strong>not</strong> attending this event.\"'>No</a>]\n</div><br>\n";
                        }
                    }
                    echo "</div>";
                } else if ($feed_item['type'] == "photo") {
                    if ($feed_item['story'] == NULL && $feed_item['message'] == NULL) {
                        //echo "added some pictures to the album {$feed_item['name']}, including this one";
                        print_r($feed_item);
                    }
                    if ($feed_item['picture']) {
                        $image = preg_replace("/_s.jpg$/", "_b.jpg", $feed_item['picture']);
                        echo "<br><img max-height='600px' src='" . $image . "'><br>";
                    }
                } else if ($feed_item['type'] == "video" || $feed_item['type'] == "swf") {
                    $source = str_replace('&autoplay=1', '', $feed_item['source']);
                    $source = str_replace('&auto_play=true', '', $source);
                    if (preg_match("/rootmusic/", $source)) {
                        echo "<a href=\"#\" onClick=\"document.getElementById('".$source."').style.display = 'inline'\">Show embedded music</a><div style='display:none' id='".$source."'>";
                    }
                    if (preg_match("/youtube/", $source) && preg_match("/videoseries/", $source)) {
                        echo "<iframe src='" . $source . "' width='420' height='315' frameborder='0'>Gay Youtube Playlist Embed Thing Here</iframe>";
                    } else if (preg_match("/webm$/", $source)) {
                        echo "<video src='".$source."' poster='/404.jpg' controls></video>";
                    } else {
                        echo "<br><embed autoStart='1' width='420' height='315' src='" . $source . "'><br>";
                    }
                    if (preg_match("/rootmusic/", $source)) {
                        echo "</div>";
                    }
                } else {
                    echo "This is an item of type " . $feed_item['type'] . " which is not handled yet. Please inform the developer.";
                    print_r($feed_item);
                }
                if (preg_match("/likes a link/", $feed_item['story'])) {
                    echo "<div style='font-size:small;margin-left:80px;width:300px'><a href='javascript:;' onclick='window.open(\"" . $feed_item['link'] . "\", \"_blank\");'>" . $feed_item['name'] . "</a><br>" . $feed_item['description'] . "</div>";
                }
                $already_liked = FALSE;
                if ($feed_item['likes']['data'] != NULL) {
                    foreach ($feed_item['likes']['data'] as $like) {
                        if ($like['id'] == $user) {
                            $already_liked = TRUE;
                        }
                    }
                }
                if ($already_liked) {
                    echo "<div style='margin-left:80px'><div style='display:inline' id='like_link_{$feed_item['id']}'>[<a href='javascript:UnlikeSomething(\"{$feed_item['id']}\");'>Unlike</a>]</div>";
                } else {
                    echo "<div style='margin-left:80px'><div style='display:inline' id='like_link_{$feed_item['id']}'>[<a href='javascript:LikeSomething(\"{$feed_item['id']}\");'>Like</a>]</div>";
                }
                if ($feed_item['likes']['count'] == NULL && $feed_item['comments']['count'] == NULL) {
                    echo " [<a href='javascript:showCommentForm(\"{$feed_item['id']}\")'>Comment</a>]";
                }
                echo " " . date("F j, Y \a\t H:i", strtotime($feed_item['created_time']));
                echo "</div>";
                if($feed_item['likes']['count'] != NULL || $feed_item['comments']['count'] != NULL) {
                    echo "<div style='margin-left:80px;background-color:#dde3ee;border-width:2px;border-color:#dde3ee;border-style:solid'>";
                }
                echo "<div id='likes_{$feed_item['id']}'>";
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
                        echo $like_people . " and one other like this.";
                    } else {
                        echo $like_people . " and " . $others . " others like this.";
                    }
                }
                echo "</div>";
                $like_people = "";
                if ($feed_item['comments']['count'] != 0) {
                    $j = 0;
                    echo "<div id='full_comments_{$feed_item['id']}' style='display:inline'>\n";
                    if (count($feed_item['comments']['data']) < $feed_item['comments']['count']) {
                        echo "<div id='comments_{$feed_item['id']}' style='display:inline'>\n";
                        echo "<a href='javascript:showComments(\"{$feed_item['id']}\", \"{$feed_item['comments']['data'][0]['id']}\")'>Show all {$feed_item['comments']['count']} comments.</a>\n";
                        echo "<br>\n</div>\n";
                    }
                    if ($feed_item['comments']['data'] != NULL) {
                        foreach ($feed_item['comments']['data'] as $comment) {
                            if ($j == 0) {
                                $j++;
                            } 
                            if (preg_match("/^#([0-9]+) /", $comment['message'], $reply_to_matches)) {
                                echo "<div title='{$reply_to_matches[1]}' class='put_in'>";
                                $comment_message = str_replace("\n", "<br>", preg_replace("/^#[0-9]+ /", "", $comment['message']));
                            } else {
                                $comment_message = str_replace("\n", "<br>", $comment['message']);
                            }
                            echo "<div style='float:left;padding-right:3px'>\n<img src='http://graph.facebook.com/" . $comment['from']['id'] . "/picture'>\n</div>\n";
                            echo "<strong>" . $comment['from']['name'];
                            echo "</strong> " . $comment_message . "\n";
                            echo "<span id='likes_{$comment['id']}'>\n";
                            if ($comment['likes'] != NULL) {
                                if ($comment['likes'] == 1) {
                                    echo "<br>\n<strong>1 person</strong> likes this comment.\n";
                                } else {
                                    echo "<br>\n<strong>{$comment['likes']} people</strong> like this comment.\n";
                                }
                            }
                            $comment_id = preg_replace("/[0-9]+_[0-9]+_/", "", $comment['id']);
                            echo "</span>\n<br>\n[<a href='javascript:LikeSomething(\"{$comment['id']}\");'>Like</a>][<a href='javascript:ReplyTo(\"{$comment_id}\", \"{$feed_item['id']}\")'>Reply To</a>]\n";
                            echo "\n<div style='display:block;clear:left;visibility:hidden'><hr></div>\n";
                            echo "<div id='{$comment_id}' style='padding-left:50px'></div>";
                            if (preg_match("/^#[0-9]+ /", $comment['message'])) {
                                echo "</div>";
                            }
                        }
                    }
                    echo "<div style='visibility:hidden;clear:left;display:block'><hr></div>\n";
                }
                echo "</div>\n</div>\n<div id='post_comment_{$feed_item['id']}' style='display:";
                if($feed_item['likes']['count'] != NULL || $feed_item['comments']['count'] != NULL) {
                    echo "inline";
                } else {
                    echo "none";
                }
                echo "'>\n<form style='background-color:#dde3ee;margin-left:80px;border-width:2px;border-color:#dde3ee;border-style:solid' method='post' action='https://graph.facebook.com/{$feed_item['id']}/comments' id='comment_form_{$feed_item['id']}'><input type='hidden' name='access_token' value='{$token}'><textarea id='textarea_{$feed_item['id']}' name='message' style='width:90%'></textarea><input type='button' style='background-color:#009;color:#FFF;float:right;height:4em;width:9%' name='send' value='Comment' onClick='CommentSomething(\"{$feed_item['id']}\");'></form></div>";
                if($feed_item['likes']['count'] != NULL || $feed_item['comments']['count'] != NULL) {
                    echo "</div><br>";
                }
        }
        echo "</div>";
} else {
    echo "<a href='" . $loginUrl . "&scope=read_stream,publish_stream'>Login with Facebook</a>";
}
echo "\n";
echo '<script type="text/javascript">function thread_comments() {var threads = document.getElementsByClassName("put_in");for (var i=0;i<threads.length;i++) {parent = document.getElementById(threads[i].title);if (parent) {parent.innerHTML = document.getElementById(threads[i].title).innerHTML + threads[i].innerHTML;threads[i].innerHTML = "";}}}setTimeout(thread_comments, 5000);</script>';
        ?>
    </body>
</html>
