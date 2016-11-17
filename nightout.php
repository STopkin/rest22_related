<?php

set_time_limit(0);
include_once('simple_html_dom.php');

$dblink = mysqli_connect('localhost', 'root', 'vertrigo', 'kinomir');
mysqli_set_charset($dblink, 'utf8');

define ("NIGHTOUT", 'http://nightout.ru');
$site = 'NIGHTOUT';

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // следовать перенаправлениям...
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

curl_setopt($ch, CURLOPT_URL, NIGHTOUT.'/brnl/anons');
$content = curl_exec($ch);
    
if ($content === FALSE) {
	die('Выполнение не начато');
}
$htmldom = str_get_html($content);
foreach($htmldom->find('div.anons-item') as $element) {
	
	$link = $element->find('div.ava__flex a');
	$link = $link[0]->attr['href'];
	$paths = explode('/', $link);

	$details = $element->find('div.afisha__bl__item1 a');

	$place = $details[0]->text();
	$title = $details[1]->text();

	$timeSpan = $element->find('span');
	$timeSpan = $timeSpan[0]->text();
	$time = preg_match("/(\d+:\d+)/", $timeSpan, $matches);
	if ($time)
		$time = $matches[1];

	$date = preg_match("/\/(\d+\-\d+\-\d+)\//", $link, $matches);
	if ($date)
		$date = $matches[1];

	$shedule = array('place'=>$place, 'date'=>$date, 'times'=>$time);
	$shedules = array();
	$shedules[] = $shedule;
	$shedulesw = serialize($shedules);

	curl_setopt($ch, CURLOPT_URL, NIGHTOUT.$link);
	$subContent = curl_exec($ch);
	if ($subContent === FALSE) {
		die('Не удалось скачать событие '.$link);
	}

	$htmldom2 = str_get_html($subContent);
	$img = $htmldom2->find('div.std-pad div.ava__flex img');
	$imgSrc = $img[0]->attr['src'];
	$descr = $htmldom2->find('div.std-pad div.page-text');
	$descr = $descr[0]->text();
	mysqli_query($dblink, "insert into event_heap (event, description, shedules, external_id, site) values('$title', '$descr', '$shedulesw', 0, '$site')");
	$newId = mysqli_insert_id($dblink);

	curl_setopt($ch, CURLOPT_URL, $imgSrc);
	$imgContent = curl_exec($ch);
	$ext = preg_match("/\.(\w+)$/", $imgSrc, $matches);
	if ($ext)
		$ext = $matches[1];
	@mkdir("nightout_images");
	file_put_contents("nightout_images/$newId.$ext", $imgContent);
	echo "$title $link добавлено<br>";
	//break;
}


curl_close($ch);

