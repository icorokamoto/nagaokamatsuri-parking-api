<?php

//mb_internal_encoding("UTF-8");
date_default_timezone_set('Asia/Tokyo');

$config = array(
	'version'      => '2013.07.29',
	'litephp_path' => './inc/Lite.php', // Lite.phpのpath
	'cache_dir'    => './cache/',       // cacheディレクトリのpath
	'cache_time'   => 60*10,            // cacheの有効時間(秒単位)
	'shd_path'     => './inc/simple_html_dom.php',
	'year'         => 2013
);

$url[0] = 'http://nagaokamatsuri.com/pnavi';
$url[2] = $url[0] . '2.html';
$url[3] = $url[0] . '3.html';

//defaultの日付
$mktime = mktime( 0, 0, 0, 8, 3, $config['year'] );
if( time() < $mktime ) {
	$config['default_day'] = 2;
} else {
	$config['default_day'] = 3;
}

//日付の設定
$day = 0;

if( isset( $_GET['day'] ) ) {
	$day = (int) $_GET['day'];
}

if( $day != 2 && $day != 3 ) {
	$day = $config['default_day'];
}

$config['url']      = $url[$day];
$config['cache_id'] = $day;
$config['day']      = $day;

$json = get_json();

/*
echo '<pre>';
var_dump( json_decode( $json, true ) );
echo '</pre>';
*/

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

//header("Content-Type: text/javascript; charset=utf-8"); 

echo $json;

/**
 * jsonデータを生成する
 * @param	none
 * @return	string $encoded_data
 */
function get_json() {

	global $config;

	$cacheLite = get_CacheLiteObject();

	//キャッシュを取得
	$encoded_data = null;
//	$encoded_data = $cacheLite->get( $config['cache_id'] );

	//キャッシュがない or 期限切れの場合は新たに生成
	if ( !$encoded_data ) {
		$data = get_parkingStatus();
		$encoded_data = json_encode( $data );
		if($data['status'] != 'NagaokaMatsuriIsOver') {
			if( $data['status'] == 'OK' ) {
				$cacheLite->save( $encoded_data, $config['cache_id'] );
				logging( $data );
			} else {
				$encoded_data = $cacheLite->get( $config['cache_id'], 'default', true );
			}
		}
	}

	return $encoded_data;
}


/**
 * 駐車場情報の配列を生成する
 * @param	none
 * @return	array $result
 */
function get_parkingStatus() {

	global $config, $places;

	$result = array(
		'version'        => $config['version'],
		'status'         => '',
		'day'            => $config['day'],
		'generated_date' => date("Y/m/d H:i:s")
	);

	// 8/4以降はデータの取得を行わない
	$mktime = mktime( 0, 0, 0, 8, 4,  $config['year'] );
	if( time() > $mktime ) {
//		$result['status'] = 'Nagaoka Matsuri ' .  $config['year'] . ' is over! See you next year!';
		$result['status'] = 'NagaokaMatsuriIsOver';
		return $result;
	}

	$html = file_get_contents( $config['url'] );

	// ファイルの取得に失敗した場合はエラーを返す
	if( !$html ) {
		$result['status'] = 'File Read Error';
		return $result;
	}

	// ページの文字コードをUTF8に変更
//	$html = mb_convert_encoding( $html, "UTF-8", "SJIS" );

/*
	//javascript部分からstatusを取得
	$regexp = '/var marker(?P<id>(\d)+)[\n ]*=.*markerImg(?P<status>\d).*\}\);/sU';
	preg_match_all( $regexp, $html, $match );

	$array_status = array_combine( $match['id'], $match['status'] );
	$array_status_str = array();

	foreach( $array_status as $key => $status ) {
		$status = (int) $status;
		switch ( $status ) {
			case 1: $str = '空車あり'; break;
			case 2: $str = '残りわずか'; break;
			case 3: $str = '満車'; break;
			default: $str = '閉';
		}
		$array_status_str[$key] = $str;
	}
*/

	// http://simplehtmldom.sourceforge.net/manual.htm
	include_once( $config['shd_path'] );
	$html = str_get_html( $html );
	
	//時刻を取得
	$time_row = $html->getElementById('pnavi_map')->next_sibling();
	$src = $time_row->plaintext;

	$regexp = '/\d{2}:\d{2}:\d{2}/';
	preg_match( $regexp, $src, $match );
	$updatetime = $match[0];

	//駐車場情報を取得
	$rs = $html->find('.pnavi_box04 tr');

	//駐車場情報を配列に設定
	$array_places = array();

	foreach( $rs as $r ) {
		// id
/*
		$src = $r->find('td a', 0)->getAttribute('href');
		$regexp = '/ParkingInfo(\d+)\(\);/';
		preg_match( $regexp, $src, $match );
		$id = (int) $match[1];
*/

		// 空白のセルだった場合は飛ばす
		if(!$r->find('th a', 0)) continue;

		// name
		$a['name'] = trim( h( $r->find('th a', 0)->plaintext ) );

		// id
		switch( $a['name'] ) {
			case '国営越後丘陵公園':   $id = 1; break;
			case '越路支所':           $id = 2; break;
			case '越路体育館':         $id = 3; break;
			case 'JA越後さんとう':     $id = 4; break;
			case '南部工業団地':       $id = 5; break;
			case '倉敷機械':           $id = 6; break;
			case '北部体育館':         $id = 7; break;
			case '日本精機':           $id = 8; break;
			case '第一合繊':           $id = 9; break;
			case '上越マテリアル用地': $id = 10; break;
			case '三島支所 他':        $id = 12; break;
			case '長岡造形大学':       $id = 13; break;
			case 'リリックホール':     $id = 14; break;
			case '県立近代美術館':     $id = 15; break;
			case 'さいわいプラザ':     $id = 16; break;
			case '旧健康センター':     $id = 17; break;
		}

		//updatetime
/*		$src = $r->find('th', 0)->plaintext;
		$regexp = '/\d{2}:\d{2}:\d{2}/';
		preg_match( $regexp, $src, $match );
		$a['updatetime'] = $match[0];
*/
		$a['updatetime'] = $updatetime;

		//status
//		$a['status'] = (int) $array_status[$id];

		// status_str
//		$a['status_str'] = $array_status_str[$id];
		$a['status'] = trim( h( $r->find('td', 0)->plaintext ) );

		//status
/*
		switch ( $a['status_str'] ) {
			case '空車あり':   $a['status'] = 1; break;
			case '残りわずか': $a['status'] = 2; break;
			case '満　車':     $a['status'] = 3; break;
			default:           $a['status'] = 4;
		}
*/
		$array_places[$id] = $a;
	}

	$result['places'] = $array_places;
	$result['status'] = 'OK';

	return $result;
}


/**
 * ログを保存する
 * @param	$str
 * @return	boolean
 */
function logging( $data ) {

	global $config;

	$mktime = mktime( 8, 0, 0, 8, 2, $config['year'] );
	if( time() < $mktime ) return false;

	if ( $config['day'] != $config['default_day'] ) return false;

	$str = date("Y/m/d H:i:s");

	foreach( $data['places'] as $p ) {
		$name   = $p['name'];
		$status = $p['status'];
		$time   = $p['updatetime'];
		$str .= ',' . $name . ',' . $status . ',' . $time;
	}

	$str = $str . "\n";

	$file_path = './log/log' . $config['year'] . '080' . $config['day'] . '.csv';
	$state = file_put_contents( $file_path, $str, FILE_APPEND );

	return $state;
}


/**
 * キャッシュオブジェクトを生成する
 * @param	none
 * @return	object $obj
 */
function get_CacheLiteObject() {

	global $config;
	
	$options = array(
		'cacheDir' => $config['cache_dir'],
		'lifeTime' => $config['cache_time']
	);
	include_once( $config['litephp_path'] );
	$obj = new Icoro_Cache_Lite( $options );
	return $obj;
}


/**
 * 文字列をアレするアレ
 * @param	string $str
 * @return	string $str
 */
function h( $str ) {
	return htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
}

?>
