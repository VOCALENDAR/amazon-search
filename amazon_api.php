<!DOCTYPE html>
<html lang="ja">
<head>
    <title>Amazon API test</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="amazon_api.css">
</head>
<body>

<?php

$category = isset($_GET['category']) ? $_GET['category'] : "Music";
$keyword  = isset($_GET['keyword']) ? $_GET['keyword'] : "初音ミク";

$response = ""; // XML戻り値
$xmlArray = array(); // XMLパース後(JSON)
$xmlObject = ""; // JSON->連想配列
$ItemArray = array();
$releasedate = "";

?>

<section id="searchOptions">

<form method="GET">
<input type="text" name="keyword" value="<?php echo $keyword;?>">
<input type="hidden" name="category" value="<?php echo $category; ?>">

<ul id="categorySelector">
<li <?php if ($category == "Music") echo "class='active'"; ?>><label><input type="radio" name="category" value="Music" <?php if ($category == "Music") echo "checked"; ?>><a href="?category=Music&keyword=<?php echo $keyword; ?>">Music</a></label></li>
<li <?php if ($category == "DVD") echo "class='active'"; ?>><label><input type="radio" name="category" value="DVD" <?php if ($category == "DVD") echo "checked"; ?>><a href="?category=DVD&keyword=<?php echo $keyword; ?>">DVD</a></li>
<li <?php if ($category == "Books") echo "class='active'"; ?>><label><input type="radio" name="category" value="Books" <?php if ($category == "Books") echo "checked"; ?>><a href="?category=Books&keyword=<?php echo $keyword; ?>">Books</a></li>
<li <?php if ($category == "Hobbies") echo "class='active'"; ?>><label><input type="radio" name="category" value="Hobbies" <?php if ($category == "Hobbies") echo "checked"; ?>><a href="?category=Hobbies&keyword=<?php echo $keyword; ?>">Hobbies</a></li>
<li <?php if ($category == "Toys") echo "class='active'"; ?>><label><input type="radio" name="category" value="Toys" <?php if ($category == "Toys") echo "checked"; ?>><a href="?category=Toys&keyword=<?php echo $keyword; ?>">Toys</a></li>
</ul>

</form>

</section>

<?php

function ItemLookup ($category, $keywords, $page = 1)
{
    global $releasedate;
    $params = array();


    // 必須
    $access_key_id = ACCESS_KEY_ID;
    $secret_access_key = YOUR_SECRET_KEY;
    $params['AssociateTag'] = 'vocalendar-22';
    $baseurl = 'http://ecs.amazonaws.jp/onca/xml';

    // パラメータ
    $params['Service'] = 'AWSECommerceService';
    $params['Keywords'] = $keywords;
    $params['Timestamp'] = gmdate('Y-m-d\TH:i:s\Z');
    $params['ItemPage'] = $page;
    $params['AWSAccessKeyId'] = $access_key_id;
    $params['Operation'] = 'ItemSearch';

    switch ($category) {
        case "Books":
            $params['SearchIndex'] = 'Books';
            $params['ResponseGroup'] = 'ItemAttributes,Offers';
            $params['Sort'] = 'daterank';
            $releasedate = 'PublicationDate';
            break;
        case "Hobbies":
            $params['SearchIndex'] = 'Hobbies';
            $params['ResponseGroup'] = 'ItemAttributes,Offers';
            $params['Sort'] = '-release-date';
            $releasedate = 'ReleaseDate';
            break;
        case "Toys":
            $params['SearchIndex'] = 'Toys';
            $params['ResponseGroup'] = 'ItemAttributes,Offers';
            $params['Sort'] = '-releasedate';
            $releasedate = 'ReleaseDate';
            break;
        case "Electronics":
            $params['SearchIndex'] = 'Electronics';
            $params['ResponseGroup'] = 'ItemAttributes,Offers';
            $params['Sort'] = '-releasedate';
            $releasedate = 'ReleaseDate';
            break;
        case "DVD":
            $params['SearchIndex'] = 'DVD';
            $params['ResponseGroup'] = 'ItemAttributes,Offers';
            $params['Sort'] = '-releasedate';
            $releasedate = 'ReleaseDate';
            break;
        default:
            $params['SearchIndex'] = 'Music';
            $params['ResponseGroup'] = 'ItemAttributes,Offers';
            $params['Sort'] = '-orig-rel-date';
            $releasedate = 'ReleaseDate';
            break;
    }

    ksort($params);

    // 送信用URL・シグネチャ作成
    $canonical_string = '';
    foreach ($params as $k => $v) {
        $canonical_string .= '&' . urlencode_rfc3986($k) . '=' . urlencode_rfc3986($v);
    }
    $canonical_string = substr($canonical_string, 1);
    $parsed_url = parse_url($baseurl);
    $string_to_sign = "GET\n{$parsed_url['host']}\n{$parsed_url['path']}\n{$canonical_string}";
    $signature = base64_encode(hash_hmac('sha256', $string_to_sign, $secret_access_key, true));
    $url = $baseurl . '?' . $canonical_string . '&Signature=' . urlencode_rfc3986($signature);

    // xml取得
    $xml = request($url);
    // xml出力
    return $xml;
}

function urlencode_rfc3986($str)
{
    return str_replace('%7E', '~', rawurlencode($str));
}

function request($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    $response = curl_exec($ch);
    curl_close($ch);

    $xmlObject = simplexml_load_string ($response);
    $xmlArray = json_decode (json_encode( $xmlObject ),TRUE);
    return $xmlArray;

    //return simplexml_load_string($response); //オブジェクトとして返す場合
}


for ($page = 1; $page <= 3; $page++) {

    $xmlArray = ItemLookup($category, $keyword, $page);
    if (is_array($xmlArray)) {
        $ItemArray = array_merge($ItemArray, $xmlArray['Items']['Item']);
    }

}

echo "<ul id='SearchResult'>". PHP_EOL;
if( is_array( $ItemArray ) ){

    foreach ($ItemArray as $ItemTemp) {
        echo "<li>".PHP_EOL;
        echo "<h2 class='ReleaseDate'>".$ItemTemp['ItemAttributes'][$releasedate]."</h2>".PHP_EOL;
        echo "<h1 class='title'><a href='".$ItemTemp['DetailPageURL']."' target='_blank'>".$ItemTemp['ItemAttributes']['Title']."</a></h1>".PHP_EOL;
        echo "</li>".PHP_EOL;
    }

} else {
    print "<li>It IS NOT the array!</li>". PHP_EOL;
}
echo "</ul>". PHP_EOL;

?>

</body>
</html>