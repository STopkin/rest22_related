<?php

set_time_limit(0);
include_once('simple_html_dom.php');

$link = mysqli_connect('localhost', 'root', 'vertrigo', 'kinomir');
mysqli_set_charset($link, 'utf8');
/*$sql = "select * from event_heap";
$q = mysqli_query($link, $sql);
while ($r = mysqli_fetch_assoc($q)) print_r($r);
return;
*/
define ("KINOMIR", 'https://www.kino-mir.ru');

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // следовать перенаправлениям...
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

for ($i=0; $i<=7; $i++) {

    $date = date("d-m-Y", time() + $i * 3600 * 24);

    curl_setopt($ch, CURLOPT_URL, KINOMIR."?date=$date");
    $content = curl_exec($ch);
    //echo substr(nl2br(htmlspecialchars($content)),0,100);
    echo "<h1>$date</h1>";
    if ($content === FALSE) {
        die('Выполнение не начато');
    }
    $htmldom = str_get_html($content);
    foreach($htmldom->find('div.js-film') as $element) {
        $external_id = $element->attr['data-film-id'];
        echo "<h3>$external_id found</h3>";
        $sql = "select * from event_heap where external_id=$external_id";
        $q = mysqli_query($link, $sql);
        $row = mysqli_fetch_array($q);
        if (empty($row)) {

            $name = $element->find('span.film__title');
            $title = $name[0]->text();
            $desc = $element->find('span.film__description__text');
            $descr = $desc[0]->text();
            $shedules = array();
            $shedulesw = serialize($shedules);
            mysqli_query($link, "insert into event_heap (event, description, shedules, external_id) values('$title', '$descr', '$shedulesw', $external_id)");
            $id = mysqli_insert_id($link);
        }
        else {
            $id = $row['id'];
            $shedules = unserialize($row['shedules']);
        }
        echo "<h4>".($postUrl = KINOMIR."/ajax/timeline/$date/$external_id")."</h4>";
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        $contentAjax = curl_exec($ch);
        if ($contentAjax === FALSE) {
            die('Выполнение не начато');
        }
        $object = json_decode($contentAjax);
        echo '<pre>';
        if (isset($object->$external_id->cinemas))
        foreach (array_keys(get_object_vars($object->$external_id->cinemas)) as $cinemaID) {

            $new_shedule = array();
            $cinemaObj = $object->$external_id->cinemas->$cinemaID;
            $new_shedule['place'] = $cinemaObj->name;
            $new_shedule['date'] = date('Y-m-d', time()+$i*3600*24);
            $prices = $times = array();

            foreach (array_keys(get_object_vars($cinemaObj->halls)) as $hallID) {

                foreach (array_keys(get_object_vars($cinemaObj->halls->$hallID->sessions)) as $sessID) {

                    $sessionObj = $cinemaObj->halls->$hallID->sessions->$sessID;
                    list(,$time) = explode(' ', $sessionObj->time);
                    list($h,$m) = explode(':', $time);
                    $times[] = "$h:$m";
                    foreach ($sessionObj->price as $price) {
                        list($rub,) = explode('.', $price);
                        $prices[] = $rub;
                    }
                }
            }
            $new_shedule['times'] = implode(', ', $times);
            sort($prices, SORT_NUMERIC);
            if (count($prices)>1)
                $new_shedule['price'] = $prices[0] . ' — ' . $prices[count($prices)-1];
            else
                $new_shedule['price'] = $prices[0];

            // поиск таких же расписаний для данного евента, для предотвращения повтора
            $skip = false;
            foreach($shedules as $shedule) {
                if ($shedule['date']==$new_shedule['date'] && $shedule['place']==$new_shedule['place'])
                {
                    $skip = true;
                    break;
                }
            }
            if (!$skip)
            {
                $shedules[] = $new_shedule;
                echo "added";
                print_r($new_shedule);
            }
            else
                print "skipped<br>";

        }
        echo '</pre>';
//        break;
        $shedulesw = serialize($shedules);
        mysqli_query($link, "update event_heap set shedules='$shedulesw', done=0 where id=$id");
    }

//    break;
}

curl_close($ch);

