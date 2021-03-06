<?php

class Parking {

	private $places;
	private $updatetime;

	public function __construct( $day = null ) {
		$day = (int) $day;
		$this->set( $day );
	}

	private function set( $day ) {
		$url = 'http://www.icoro.com/idea/nagaokamatsuri/2015/json.php';
		if( $day ) $url = $url . '?day=' . $day;

		$json = @file_get_contents( $url );
		if( !$json ) return;

		$array = json_decode( $json, true );

		if( $array['status'] != 'OK' ) return;

		$this->places = $array['places'];
		$this->updatetime = $array['updatetime'];
	}
	
	public function status( $id ) {

		$status_str = '閉鎖中';
		
/*
echo '<!--';
var_dump($this->places);
echo '-->';
*/
		if( $place = $this->places[$id] ) {
		
			$updatetime_str = '';

			// statusが空白だった場合、閉鎖中にする
			if( $place['status'] == '' ) {
				$place['status'] = '閉鎖中';
			}

			// statusが閉鎖中以外だった場合は更新時間を追加する
			if( $place['status'] != '閉鎖中' ) {
//				$updatetime_str = ' (' . substr( $place['updatetime'], 0, 5 ) . ' 現在)';
				$updatetime_str = ' (' . $this->updatetime . ' 現在)';
			}

			$status_str = $place['status'] . $updatetime_str;
/*
			switch ( $place['status'] ) {
				case 1: $status_str = '<span style="color: green;">空車あり</span>' . $updatetime; break;
				case 2: $status_str = '<span style="color: #f60;">残りわずか</span>' . $updatetime; break;
				case 3: $status_str = '<span style="color: red;">満車</span>' . $updatetime; break;
			}
*/			
		}

//		$li = '<li>空き状況: ' . $status_str . '</li>';
//		echo $li;
		return $status_str;

	}

}
?>