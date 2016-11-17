<?php

set_time_limit(0);
include_once('simple_html_dom.php');
include_once('mysql_connect.php');

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_URL, 'http://www.kino-mir.ru/ajax/get_performance');

for ($i=0; $i<=7; $i++) {

    $date = date("Y-n-j", time()+$i*3600*24);
    echo "$date<br/>";
	
	curl_setopt($ch, CURLOPT_POSTFIELDS, "data[Ajax][date]=$date");
    $content = curl_exec($ch);
	var_dump($content);
    if ($content === FALSE) {
        die('Выполнение не начато: '.$date);
    }
    $htmldom = str_get_html($content);

    // сначала ищем все блоки movie_raspisanie
    foreach($htmldom->find('div.pshow') as $element) {
        // нужно получить его
        //$text = $element->innertext();
        $id = $element->attr['id'];
        $external_id = str_replace('pshow_', '', $id);
		echo "$id found<br/>";
        // проверим, а не парсили ли мы уже такой event
        $sql = "select * from event_heap where external_id=".$external_id;
        $q = mysql_query($sql);
        $row = mysql_fetch_array($q);
        if (empty($row)) {

			$name = $element->find('.perf_show_name a');
			$title = $name[0]->text();
			$desc = $element->find('.perf_show_desc .desc');
			$descr = $desc[0]->text();
            $shedules = array();
            $shedulesw = serialize($shedules);
            mysql_query("insert into event_heap (event, description, shedules, external_id) values('$title', '$descr', '$shedulesw', $external_id)");
            $id = mysql_insert_id();
        }
        else {
            $id = $row['id'];
            $shedules = unserialize($row['shedules']);
        }

        // теперь надо парсить расписание
        // найти все места, перечислить все времена, указать макс и мин цену

        foreach($element->find('div.pb') as $element2) {
            
            $new_shedule = array();
            foreach ($element2->find('.kt_name') as $header_div)
                $new_shedule['place'] = preg_replace("/&\waquo;|»|«/", '', $header_div->text());
            $times_ar = array();
            foreach ($element2->find('span.perf_time') as $times)
                $times_ar[] = trim($times->text());
            $new_shedule['times'] = implode(', ', $times_ar);
            $new_shedule['date'] = date('Y-m-d', time()+$i*3600*24);
            $prices = array();
            foreach ($element2->find('span.perf_price') as $price_div) {
                preg_match("/(\d+)\sруб/", $price_div->plaintext, $matches);
                if (!empty($matches))
                    $prices[] = $matches[1];
            }
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
				print '<br>';
            }
            else
                print "skipped<br>";

        }
        $shedulesw = serialize($shedules);
        mysql_query("update event_heap set shedules='$shedulesw', done=0 where id=$id");
    }
    //break;
}
curl_close($ch);

