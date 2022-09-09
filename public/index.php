<?php
$src_path = realpath(dirname(dirname(__FILE__)))."/src";
require($src_path."/classes/Router.php");
require($src_path."/classes/AppSettings.php");
require($src_path."/classes/Afocha.php");
AppSettings::load();

$uri = $_SERVER['REQUEST_URI'];
$uri_parts = explode("/", $uri);

if (count($uri_parts) < 2 || $uri_parts[1] == "") {
    // App home page
    require($src_path."/includes/connect.php");
    require($src_path."/includes/get_session.php");

    if (!empty(AppSettings::getParam('homepage_fname'))) include($src_path."/pages/".AppSettings::getParam('homepage_fname'));
    else include($src_path."/pages/default.php");
}
else if ($uri_parts[1] == "about-us") {
    include(AppSettings::publicPath()."/about.php");
}
else if ($uri_parts[1] == "signin") {
    include(AppSettings::publicPath()."/login.php");
}
else if ($uri_parts[1] == "signup") {
    include(AppSettings::publicPath()."/register.php");
}
else if ($uri_parts[1] == "faq") {
    include($src_path."/pages/faq.php");
}
else if ($uri_parts[1] == "unsubscribe") {
    include($src_path."/unsubscribe.php");
}
else if ($uri_parts[1] == "wallet") {
    include($src_path."/wallet.php");
}
else if ($uri_parts[1] == "accounts") {
    include($src_path."/accounts.php");
}
else if ($uri_parts[1] == "profile") {
    include($src_path."/manage_profile.php");
}
else if ($uri_parts[1] == "cards") {
    include($src_path."/cards.php");
}
else if ($uri_parts[1] == "redeem" || $uri_parts[1] == "check") {
    include($src_path."/redeem_card.php");
}
else if ($uri_parts[1] == "api") {
    include($src_path."/api.php");
}
else if ($uri_parts[1] == "explorer") {
    include($src_path."/explorer.php");
}
else if ($uri_parts[1] == "download") {
    include($src_path."/download.php");
}
else if ($uri_parts[1] == "import") {
    include($src_path."/import_game.php");
}
else if ($uri_parts[1] == "directory") {
    include($src_path."/directory.php");
}
else if ($uri_parts[1] == "manage") {
    include($src_path."/manage_game.php");
}
else if ($uri_parts[1] == "manage_blockchains") {
    include($src_path."/manage_blockchains.php");
}
else if ($uri_parts[1] == "manage_currencies") {
    include($src_path."/manage_currencies.php");
}
else if ($uri_parts[1] == "analytics") {
    include($src_path."/analytics.php");
}
else if ($uri_parts[1] == "testnet") {
    $blockchain = 'abweb';
    $diff = 8;
    $index = 0;
    $hash = hash_hmac('sha256', $index, $index-1);
    $next_hash = hash_hmac('sha256', $index+1, $index);
    $tx = hash('sha256', time());
    $a0 = substr($hash, $diff);
    $a1 = substr($next_hash, $diff);
    $nounce = substr($hash, 0, $diff);
    echo "<div>";
    echo $blockchain." block #".$index;
    echo "<hr>";
    echo "Tblock hash: ".str_pad(strval($a0), '68','0', STR_PAD_LEFT)."</br>";
    echo "Nblock hash: ".str_pad(strval($a1), '68','0', STR_PAD_LEFT)."</br>";
    echo "Mined at ".date('Y-m-d h:i:s T', time())."</br>";
    echo "coinbase_tx: ".$tx."</br>";
    echo "Nounce: ". hexdec($nounce);
    echo "<hr>";
    
$row = 1;
if (($handle = fopen("address.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = count($data);
        echo "<p> $num fields in line $row: <br /></p>\n";
        $row++;
        for ($c=0; $c < $num; $c++) {
            echo $data[$c] . "<br />\n";
        }
    }
    fclose($handle);
}
    echo "<hr>";
    $mysqli = new mysqli("localhost", "root", "", "afocha_db");
    echo $mysqli->host_info . "\n";
}
else if ($uri_parts[1] == "public") {
    include($src_path."/v1.api.php");
}
else if ($uri_parts[1] == "groups") {
    include($src_path."/manage_groups.php");
}
else {
    $extension_pos = strpos($uri, ".php");
    if ($extension_pos !== false) {
        $requested_filename = substr($uri, 0, $extension_pos).".php";
    }
    else $requested_filename = false;
    
    if ($requested_filename && is_file($src_path.$requested_filename)) {
        $whitelisted_directories = [
            $src_path,
            $src_path."/ajax",
            $src_path."/cron",
            $src_path."/scripts",
            $src_path."/strategies",
            $src_path."/tests"
        ];
        
        if (in_array(dirname($src_path.$requested_filename), $whitelisted_directories) || dirname(dirname($src_path.$requested_filename)) == $src_path."/modules") {
            include($src_path.$requested_filename);
        }
        else Router::Send404();
    }
    else {
        require($src_path."/includes/connect.php");
        require($src_path."/includes/get_session.php");
        
        $selected_category = $app->run_query("SELECT * FROM categories WHERE category_level=0 AND url_identifier=:url_identifier;", [
            'url_identifier' => $uri_parts[1]
        ])->fetch();
        
        if ($selected_category) {
            include($src_path."/directory.php");
        }
        else {
            $db_game = $app->fetch_game_by_identifier($uri_parts[1]);
            
            if ($db_game) {
                if (in_array($db_game['game_status'], ["running","published","completed"])) {
                    $blockchain = new Blockchain($app, $db_game['blockchain_id']);
                    $game = new Game($blockchain, $db_game['game_id']);
                    include($src_path."/game_page.php");
                }
                else Router::Send404();
            }
            else Router::Send404();
        }
    }
}
?>