 <?php
 $cache_expire = 60*60*24*365;
 header("Pragma: public");
 header("Cache-Control: maxage=".$expires);
 header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$cache_expire) . ' GMT');
 ?>
<script src="http://connect.facebook.net/en_US/all.js"></script>