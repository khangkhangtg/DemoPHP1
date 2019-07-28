<?php

require './libs/Medoo.php';

require './libs/DiDom/Exceptions/InvalidSelectorException.php';
require './libs/DiDom/ClassAttribute.php';
require './libs/DiDom/Document.php';
require './libs/DiDom/Element.php';
require './libs/DiDom/Encoder.php';
require './libs/DiDom/Errors.php';
require './libs/DiDom/Query.php';
require './libs/DiDom/StyleAttribute.php';

require './libs/Curl/ArrayUtil.php';
require './libs/Curl/CaseInsensitiveArray.php';
require './libs/Curl/Curl.php';
require './libs/Curl/Decoder.php';
require './libs/Curl/Encoder.php';
require './libs/Curl/MultiCurl.php';
require './libs/Curl/StringUtil.php';
require './libs/Curl/Url.php';

use Medoo\Medoo;

use DiDom\Document;
use DiDom\Element;

use Curl\Curl;

$database = new Medoo([
    'database_type' => 'mysql',
    'database_name' => 'truyentranh',
    'server' => 'localhost',
    'username' => 'root',
    'password' => ''
]);

define('BASE_URL', 'http://truyentranhtuan.com');

$url = 'http://truyentranhtuan.com/one-piece';

if(get_data($url, $content)) {
    // echo htmlspecialchars($content);
    // echo get_name($content);

    // $store = array(
    //     'store_name' => get_name($content),
    //     'store_link' => $url
    // );

    // $data = insert_store($store);

    // var_dump($data);

    get_all_chapters($store_id, $content);
}

function get_all_chapters ($store_id, $content) {
    $dom = new Document();
    $dom->load($content);

    $chapters = $dom->find('#manga-chapter')[0];
    $chapters_name = $chapters->find('.chapter-name');
    $chapters_date = $chapters->find('.date-name');
    

    // echo htmlspecialchars($chapters_name);
    // echo $chapters_name[0]->find('a')[0]->getAttribute('href');
    // echo $chapters_date[0]->text();

    $partern = '/\d+/';
    preg_match($partern, $chapters_name[0]->text(), $matches);

    $last = $matches[0];

    for($i = 0; $i < $chapters->count('.chapter-name'); ++$i){
        // $chapter = insert_chapter($store_id, [
        //     'chapter_name' => $chapters_name[$i]->text(),
        //     'chapter_link' => $chapters_name[$i]->find('a')[0]->getAttribute('href'),
        //     'chapter_date' => $chapters_date[$i]->text()
        // ]);

        preg_match($partern, $chapters_name[$i]->text(), $match);
        
        if($match[0] != $last) {
            while($match[0] <= $last){
                echo $last . '<br>';
                --$last;
            }
        } else {
            echo $match[0] . ' ' . $last . '<br>';
            --$last;
        }

        

        // echo '<pre>';
        // var_dump($chapter);
        // echo '</pre>';
    }

}

function get_name ($content) {
    $name = '';

    $dom = new Document();
    $dom->load($content);

    $name = $dom->find('#infor-box')[0]
                ->find('div')[2]
                ->find('h1')[0]
                ->text();

    return $name;
}

function download_file ($url, $path) {
    $curl = new Curl();

    echo "Start download: " . $url . PHP_EOL;

    $curl->setTimeout(60);
    $curl->setConnectTimeout(60);

    $re = $curl->download($url, $path);

    if ($re) {
        echo 'Download successfully' . $url . PHP_EOL;
    }else {
        echo 'Download fail' . $url . PHP_EOL;
    }

    $curl->close();
}

function get_data ($url, &$content) {
    $curl = new Curl();

    $curl->setConnectTimeout(60);
    $curl->setTimeout(60);
    $curl->setOpt(CURLOPT_ENCODING , 'Mozilla/5.0 (Windows NT 10.0; WOW64) 
                                        AppleWebKit/537.36 (KHTML, like Gecko) 
                                        Chrome/74.0.3729.134 
                                        Safari/537.36 
                                        Vivaldi/2.5.1525.41');
    $curl->setReferer('http://www.google.com');
    $curl->setUserAgent('http://www.google.com');

    $curl->get($url);

    if(!$curl->error) {
        $content = $curl->response;
    }

    $curl->close();

    return !$curl->error;
}

$store = array();
$store['store_name'] = 'HAJIME NO IPPO MANGA';
$store['store_link'] = 'https://www.mangareader.net/hajime-no-ippo';

// var_dump(insert_store($store));

$chapter = array(
    'chapter_name' => 'Hajime no Ippo 1',
    'chapter_link' => 'https://www.mangareader.net/hajime-no-ippo/1',
    'chapter_date' => '07/07/2009'
);

// var_dump(insert_chapter(1, $chapter));

$image = array(
    'image_link' => 'https://i2.mangareader.net/hajime-no-ippo/1/hajime-no-ippo-1652000.jpg', 
    'image_path' => 'abc'
);

// var_dump(insert_image(1, $image));

function insert_store ($store) {
    $name = $store['store_name'];
    $link = $store['store_link'];

    $sql = "INSERT INTO store(store_name, store_link)
                SELECT * FROM ( SELECT '$name', '$link' ) AS tmp 
            WHERE NOT EXISTS ( 
                SELECT store_link FROM store WHERE store_link = '$link') 
            LIMIT 1";

    // echo $sql;

    // $sql = 

    global $database;

    $database->query($sql);

    $data = $database->query("SELECT * FROM store WHERE store_link = '$link'")->fetch();

    return $data;
}

function insert_chapter ($store_id, $chapter) {
    $chapter_name = $chapter['chapter_name'];
    $chapter_link = $chapter['chapter_link'];
    $chapter_date = $chapter['chapter_date'];

    $sql = "
        INSERT INTO chapter(chapter_name, chapter_link, chapter_date, store_id)
        SELECT '$chapter_name', '$chapter_link', '$chapter_date', '$store_id' FROM DUAL
        WHERE NOT EXISTS (
            SELECT * FROM chapter WHERE chapter_link = '$chapter_link'
        ) LIMIT 1
    ";

    global $database;
    $database->query($sql);

    $data = $database->query("SELECT * FROM chapter WHERE chapter_link = '$chapter_link'")->fetch();

    return $data;
}

function insert_image ($chapter_id, $image) {
    $image_link = $image['image_link'];
    $image_path = $image['image_path'];

    $sql = "
        INSERT INTO image (image_link, image_path, chapter_id)
        SELECT '$image_link', '$image_path', '$chapter_id' FROM DUAL
        WHERE NOT EXISTS (
            SELECT * FROM image WHERE image_link = '$image_link'
        ) LIMIT 1
    ";

    global $database;
    $database->query($sql);

    $data = $database->query("SELECT * FROM image WHERE image_link = '$image_link'")->fetch();

    return $data;
}