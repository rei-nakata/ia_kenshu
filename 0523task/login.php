<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php
// データを受け取る
$origin = [];
if (isset($_POST)) {
    $origin += $_POST;
}

// 受け取ったデータを処理する
$input = [];
foreach ($origin as $key => $value) {
    // 文字コード
    $mb_code = mb_detect_encoding($value);
    $value = mb_convert_encoding($value, "UTF-8", $mb_code);

    // XSS対策
    $value = htmlentities($value, ENT_QUOTES);

    // 改行処理
    $value = str_replace("\r\n", "<br>", $value);
    $value = str_replace("\n", "<br>", $value);
    $value = str_replace("\r", "<br>", $value);

    // 処理が終わったデータを$inputに入れなおす
    $input[$key] = $value;
}

// 値の正誤判定で用いるユーザーデータ
$db_id = "nakata";
$db_pass = "1234";

// 分岐
if ($input["id"] != "" && $input["pass"] != "") {
    if ($input["id"] == $db_id && $input["pass"] == $db_pass) {
        echo "ログイン成功<br>";
        session_start(); //セッション情報を扱う
        $_SESSION["id"] = $input["id"]; //セッション情報を保存する
        echo "{$_SESSION['id']}さん、こんにちは！";
        echo "<p><a href='system.php'>マイページに進む</a></p>";
    } else {
        echo "IDまたはパスワードが間違っています。";
        echo <<<form
    <form>
    <input type="button" value="ログイン画面に戻る" onclick="history.back()">
    </form>
    form;
    }
}
else{
    echo "IDまたはパスワードが入力されていません。";
    echo <<<form
    <form>
    <input type="button" value="ログイン画面に戻る" onclick="history.back()">
    </form>
    form;
}
?>
</body>
</html>