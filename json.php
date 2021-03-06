<?php

//mb_internal_encoding("UTF-8");
date_default_timezone_set('Asia/Tokyo');

$config = array(
	'version'      => '2015.08.01',
	'litephp_path' => './inc/Lite.php', // Lite.phpのpath
	'cache_dir'    => './cache/',       // cacheディレクトリのpath
	'cache_time'   => 60*10,            // cacheの有効時間(秒単位)
	'shd_path'     => './inc/simple_html_dom.php',
	'year'         => 2015
);

//$url[0] = 'http://nagaokamatsuri.com/pnavi';
//$url[2] = $url[0] . '2.html';
//$url[3] = $url[0] . '3.html';

// 2015年はトップページに駐車場情報を掲載するっぽい。
// これに伴い、42行目をコメントアウト。43行目に置き換え。
$url[0] = 'http://nagaokamatsuri.com/';

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

//$config['url']      = $url[$day];
$config['url']      = $url[0];
$config['cache_id'] = $day;
$config['day']      = $day;

$json = get_json();

//jsonを出力
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

//header("Content-Type: text/javascript; charset=utf-8"); 

echo $json;

//jsonを出力 : 動作確認用
/* 
echo '<pre>';
var_dump( json_decode( $json, true ) );
echo '</pre>';
*/

/**
 * jsonデータを生成する
 * @param	none
 * @return	string $encoded_data
 */
function get_json() {

	global $config;

	$cacheLite = get_CacheLiteObject();

	//キャッシュを取得
	$encoded_data = $cacheLite->get( $config['cache_id'] );

	//動作確認用
	//$encoded_data = null;

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

	// HTMLの読み取り開始
	// http://simplehtmldom.sourceforge.net/manual.htm
	include_once( $config['shd_path'] );
	$html = str_get_html( $html );
	
	// 時刻を取得
//	$time_row = $html->getElementById('parking_title')->next_sibling();
//	$src = $time_row->plaintext;
	$src = trim( h( $html->find('#parking_title', 0)->plaintext ) );

//	$regexp = '/\d{2}:\d{2}:\d{2}/';
	$regexp = '/\d{2}:\d{2}/';
	preg_match( $regexp, $src, $match );
//	$updatetime = $match[0];
	$result['updatetime'] = $match[0];

	//駐車場情報を取得
//	$rs = $html->find('.pnavi_box04 tr');
	$rs = $html->find('#parkingbox dl');

	//駐車場情報を配列に設定
	$array_places = array();

	foreach( $rs as $r ) {

		// 空白のセルだった場合は飛ばす
//		if(!$r->find('th a', 0)) continue;

		// name
//		$a['name'] = trim( h( $r->find('th a', 0)->plaintext ) );
		$a['name'] = trim( h( $r->find('dt', 0)->plaintext ) );

		// id
		switch( $a['name'] ) {
			case '国営越後丘陵公園':   $id = 1; break;
			case '越路支所':           $id = 2; break;
			case '越路体育館':         $id = 3; break;
			case 'JA越後さんとう':     $id = 4; break;
			case '南部工業団地':       $id = 5; break;
			case '倉敷機械':           $id = 6; break;
			case '北部体育館':         $id = 7; break;
			case '見附駅付近':         $id = 8; break;
			case '上越マテリアル用地': $id = 9; break;
			case '三島支所 他':        $id = 10; break;
			case '長岡造形大学':       $id = 11; break;
			case 'リリックホール':     $id = 12; break;
			case '県立近代美術館':     $id = 13; break;
			case 'さいわいプラザ':     $id = 14; break;
			case '旧健康センター':     $id = 15; break;
			default: $id = 0;
		}

		if( $id != 0 ) {
			// status_str
			$a['status'] = trim( h( $r->find('dd', 0)->plaintext ) );

			$array_places[$id] = $a;
		}
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

	$str = date("Y/m/d H:i:s") . ',' . $data['updatetime'];

	foreach( $data['places'] as $p ) {
		$name   = $p['name'];
		$status = $p['status'];
//		$time   = $p['updatetime'];
//		$str .= ',' . $name . ',' . $status . ',' . $time;
		$str .= ',' . $name . ',' . $status;
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
