<?php
/*

// 読み込み
<?php
include_once( ABSPATH . 'idea/nagaokamatsuri/2011/parking.php' );
$p = new Parking();
?>

//statusの表示
<?php $p->status(0); ?>

*/

class Parking {

	private $places;

	public function __construct( $day = null ) {
		$day = (int) $day;
		$this->set( $day );
	}

	private function set( $day ) {
		$url = 'http://www.icoro.com/idea/nagaokamatsuri/2012/json.php';
		if( $day ) $url = $url . '?day=' . $day;

		$json = @file_get_contents( $url );
		if( !$json ) return;

		$array = json_decode( $json, true );
		if( $array['status'] != 'OK' ) return;

/*
		if( $array['day'] == 3 ) {
			$array['places'][11] = $array['places'][10];
			$array['places'][10] = null;
		}
*/

		$this->places = $array['places'];
	}
	
	public function status( $id ) {

		$status_str = '開場していません';

		if( $place = $this->places[$id] ) {
			$updatetime = ' (' . substr( $place['updatetime'], 0, 5 ) . ' 現在)';
			switch ( $place['status'] ) {
				case 1: $status_str = '<span style="color: green;">空車あり</span>' . $updatetime; break;
				case 2: $status_str = '<span style="color: #f60;">残りわずか</span>' . $updatetime; break;
				case 3: $status_str = '<span style="color: red;">満車</span>' . $updatetime; break;
			}
			
		}

		$li = '<li>空き状況: ' . $status_str . '</li>';

		echo $li;
	}

/*
	private function h( $str ) {
		return htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
	}
*/

}
/*

$p2 = new Parking(2);

$p2->status(0);
*/

/*
echo '<pre>';
var_dump( $array );
echo '</pre>';
*/

?>