<!DOCTYPE HTML>
<html>
<head>
<title> セッション</title>
<meta charset="utf-8">
<link rel="stylesheet" href="style.css">
</head>
<body>
<p> ログアウトしました</p>
<a href="index.html">ログインページに戻る</a>
<?php
 
session_start();
 
$_SESSION=array();
session_destroy();
 
?>
</body>
</html>
