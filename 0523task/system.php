<?php

////////////////////////////////////////////////////////////////
// 準備
////////////////////////////////////////////////////////////////

// データベースへのログイン情報
$dsn = "mysql:host=localhost; dbname=sampledb; charset=utf8";
$user = "testuser";
$pass = "testpass";

//セッション情報を扱う
session_start();
if (isset($_SESSION["id"])) {
    echo "{$_SESSION['id']}さん、こんにちは！";
    echo "<a href='logout.php'>ログアウト</a>";
} else {
    header("Location: index.html");
}

// テーブル表示処理のために必要な初期化
$block = "";
$place = "";

////////////////////////////////////////////////////////////////
// 本処理
////////////////////////////////////////////////////////////////

// データを受け取る
$origin = [];
if (isset($_GET)) {
    $origin += $_GET;
}

// 受け取ったデータを処理する
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

// DBに接続する
try {
    $dbh = new PDO($dsn, $user, $pass);
    if (isset($input["mode"])) {
        if ($input["mode"] === "register") {
            register();
            header("Location:system.php");
            exit();
        } else if ($input["mode"] === "delete") {
            delete();
            header("Location:system.php");
            exit();
        }
    }
    display();
    old_display();
} catch (PDOException $e) {
    echo "接続失敗..." . $e->getMessage();
}

////////////////////////////////////////////////////////////////
// 関数
////////////////////////////////////////////////////////////////

// エラー画面
function error()
{
    // 関数内でも変数で使えるようにする
    global $input;

    // 空の変数用意
    $error_message = "";

    //入力チェック
    if ($input["title"] == "") {
        $error_message .= "タイトルが未入力です<br>";
    }
    if ($input["deadline"] == "") {
        $error_message .= "締め切りが未入力です<br>";
    }

    // errorのテンプレート読み込み
    $error = fopen("tmpl/error.tmpl", "r");
    $size = filesize("tmpl/error.tmpl");
    $data = fread($error, $size);
    fclose($error);

    // 文字置き換え
    $data = str_replace("!message!", $error_message, $data);

    echo $data;

    // 処理終了
    exit;
}

// 登録処理
function register()
{
    // 関数内でも変数で使えるようにする
    global $dbh;
    global $input;

    // 登録できる時だけ
    if (isset($input["title"]) && isset($input["deadline"])) {
        // sql文を書く
        $sql = <<<sql
        insert into todo (title, deadline, priority, memo) values(?, ?, ?, ?);
        sql;

        // 実行する
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(1, $input["title"]);
        $stmt->bindParam(2, $input["deadline"]);
        $stmt->bindParam(3, $input["priority"]);
        $stmt->bindParam(4, $input["memo"]);
        $stmt->execute();
    }
    else{
        // error対処
        error();
    }
}

// 削除処理
function delete()
{
    // 関数内でも変数で使えるようにする
    global $dbh;
    global $input;

    // sql文を書く
    $sql = <<<sql
    update todo set flag = 0 where id = ?;
    sql;

    // 実行する
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(1, $input["id"]);
    $stmt->execute();
}

// テーブル表示処理
function task()
{
    global $block;
    global $place;
    $fh2 = fopen('task.html', "r");
    $fs2 = filesize('task.html');
    $top = fread($fh2, $fs2);
    fclose($fh2);

    // task.htmlの置き換え
    $top = str_replace("!block!", $block, $top);
    $top = str_replace("!place!", $place, $top);
    echo $top;
}

// 現在のタスク一覧表示処理
function display()
{
    // 関数内でも変数を使えるようにする
    global $dbh;
    global $block;

    // sql文を書く
    // flag = 1
    // 締め切りが今の時刻より前のタスク
    // 締め切りが現在に近いタスク順
    // 優先度が高い順
    // 優先度は指定されていなくても大丈夫なように
    $sql = <<<sql
    select * from todo where flag = 1 
    and deadline > current_time() 
    order by deadline asc,
    case priority
    when '高' then 1
    when '中' then 2
    when '低' then 3
    else 4
    end;
    sql;

    // 実行する
    $stmt = $dbh->prepare($sql);
    $stmt->execute();

    // テンプレートファイルの読み込み
    $fh = fopen('tmpl/insert.tmpl', "r");
    $fs = filesize('tmpl/insert.tmpl');
    $insert_tmpl = fread($fh, $fs);
    fclose($fh);

    // 繰り返してすべての行を取ってくる
    while ($row = $stmt->fetch()) {
        // 差し込み用テンプレートを初期化する
        $insert = $insert_tmpl;

        // 値を変数に入れなおす
        $id = $row["id"];
        $title = $row["title"];
        $deadline = $row["deadline"];
        $priority = $row["priority"];
        $memo = $row["memo"];

        // テンプレートファイルの文字置き換え
        $insert = str_replace("!id!", $id, $insert);
        $insert = str_replace("!title!", $title, $insert);
        $insert = str_replace("!deadline!", $deadline, $insert);
        $insert = str_replace("!priority!", $priority, $insert);
        $insert = str_replace("!memo!", $memo, $insert);

        // task.htmlに差し込む変数に格納する
        $block .= $insert;
    }
}

// 過去のタスク一覧の表示処理
function old_display()
{
    // 関数内でも変数を使えるようにする
    global $dbh;
    global $place;

    // sql文を書く
    // flag = 1
    // 締め切りが今の時刻より前のタスク
    // 締め切りが現在に近いタスク順
    // 優先度が高い順
    // 優先度は指定されていなくても大丈夫なように
    $sql = <<<sql
    select * from todo where flag = 1 
    and deadline <= current_time() 
    order by deadline desc,
    case priority
    when '高' then 1
    when '中' then 2
    when '低' then 3
    else 4
    end;
    sql;

    // 実行する
    $stmt = $dbh->prepare($sql);
    $stmt->execute();

    // テンプレートファイルの読み込み
    $fh = fopen('tmpl/insert.tmpl', "r");
    $fs = filesize('tmpl/insert.tmpl');
    $old_insert_tmpl = fread($fh, $fs);
    fclose($fh);

    // 繰り返してすべての行を取ってくる
    while ($row = $stmt->fetch()) {
        // 差し込み用テンプレートを初期化する
        $old_insert = $old_insert_tmpl;

        // 値を変数に入れなおす
        $id = $row["id"];
        $title = $row["title"];
        $deadline = $row["deadline"];
        $priority = $row["priority"];
        $memo = $row["memo"];

        // テンプレートファイルの文字置き換え
        $old_insert = str_replace("!id!", $id, $old_insert);
        $old_insert = str_replace("!title!", $title, $old_insert);
        $old_insert = str_replace("!deadline!", $deadline, $old_insert);
        $old_insert = str_replace("!priority!", $priority, $old_insert);
        $old_insert = str_replace("!memo!", $memo, $old_insert);

        // task.htmlに差し込む変数に格納する
        $place .= $old_insert;
    }

    // 表示する
    task();
}
