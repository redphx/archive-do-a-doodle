<?php
error_reporting(FALSE);

set_time_limit(0);
ob_start();
header('Content-Type: text/html; charset=UTF-8');

$file = $_FILES['file'];

if (!$file) die('Chưa upload ảnh');

if (strtolower(substr($file['name'],-4,4)) != '.gif' && strtolower(substr($file['name'],-4,4)) != '.png') die('Tên file phải tận cùng là .gif hoặc .png');

$type = checkMIME($file['type']);

if (!$type) die('Ảnh phải là ảnh GIF hoặc PNG');

if ($file['size'] > 1024*50) die('Dung lượng ảnh phải nhỏ hơn 50KB');

$grayon = $_POST['grayon'];
if (!in_array($grayon,array('auto','2','5','10'))) $grayon = 'auto';

define('IMAGE_FILE',$file['tmp_name']);
define('IMAGE_NAME',$file['name']);
define('IMAGE_TYPE',$type);
define('TMP_DIR','jxfhgkjxhgkjxhfgflseiroieorweopiqasd');


function defineGrayonSize($grayon) {
	if ($grayon == 'auto') {
		if (W <= 36 && H <= 26) {
			define('SIZE',10);
		}
		elseif (W <= 80 && H <= 60) {
			define('SIZE',5);
		}
		else {
			define('SIZE',2);
		}
	}
	else {
		define('SIZE',$grayon);
	}
}

function checkMIME($type) {
	switch ($type) {
		//case 'image/jpeg' : $t = 'jpg'; break;
		//case 'image/pjpeg' : $t = 'jpg'; break;
		case 'image/x-png' : $t = 'png'; break;
		case 'image/png' : $t = 'png'; break;
		case 'image/gif' : $t = 'gif'; break;
		default : $t = ''; break;
	}
	if (!$t) return false;
	return $t;
}


function startDraw() {
	global $ymsg,$id,$victim,$color_map,$log,$checked,$grayon;
	// 364x254
	// 182x127
	//$color = hexdec($color);
	// R D
	
	switch (IMAGE_TYPE) {
		//case 'jpg' : $image = imagecreatefromjpeg($url); break;
		case 'png' : $image = imagecreatefrompng(IMAGE_FILE); break;
		case 'gif' : $image = imagecreatefromgif(IMAGE_FILE); break;
	}
	
	//$image = imagecreatefromgif(IMAGE_FILE);
	list($w,$h) = getimagesize(IMAGE_FILE);
	define('W',$w);
	define('H',$h);
	
	if (W > 180 || H > 130) die('Ảnh phải có kích thước nhỏ hơn hoặc bằng 180x130');
	
	defineGrayonSize($grayon);
	
	
	//copy(IMAGE_FILE,'_'.IMAGE_NAME.'_'.substr(md5(time()),0,10).'.doodle');
	
	define('START_X',5+round((364-W*SIZE)/2));
	define('START_Y',5+round((254-H*SIZE)/2));
	
	$trnprt_indx = imagecolortransparent($image);
	//imagecolorset($image,$trnprt_indx,255,255,255);
	
	$img_arr = array();
	$color_map = array();
	$colors = array();
	$log = array();
	
	$img_arr = array();
	for ($y=0;$y<H;$y++) {
		for ($x=0;$x<W;$x++) {
			$index = imagecolorat($image, $x, $y);
			$c = imagecolorsforindex($image,$index);
			$c = hexdec(sprintf("0x%02X%02X%02X",$c['red'],$c['green'],$c['blue']));
			if ($c != 0xFFFFFF && $index != $trnprt_indx) {
				$img_arr[$c][$y][$x] = 1;
				$color_map[$y][$x] = $c;
				if (!in_array($c,$colors)) $colors[] = $c;
			}
			else {
				$color_map[$y][$x] = -1;
			}
		}
	}
	
	$arr = array();
	$checked = array();
	//ksort($img_arr);
	foreach ($img_arr as $color => $a) {
		foreach ($a as $y => $ax) {
			foreach ($ax as $x => $v){
				if ($a[$y][$x] && !$checked[$y][$x]) {
					$line = array();
					getLine($line,$a,0,'R',$x,$y);
					
					$arr[] = drawLineNew(array_search($color,$colors),$line);
				}
			}
		}
	}
	
	$i = 0;
	$s = 'l='.SIZE.'O'.implode('l',$colors).'O';
	
	foreach ($arr as $a) {
		++$i;
		//echo $drawContents = '"'.rtrim(implode(',',$a),',0,0,0,0').',0,0"';
		$drawContents = implode('l',$a);
		//$drawContents = str_replace('-','0',$drawContents);
		if (count($a) != 8) {
			while (strpos($drawContents,'l0l0l0l0') !== false)
				$drawContents = str_replace('l0l0l0l0','l0l0',$drawContents);
		}
		$s .= $drawContents.'O';
	}
	$s = substr($s,0,-1);
	return $s;
}

function checkUR($x,$y) {
	global $color_map, $log, $checked;
	$i = $x+1;
	$j = $y-1;
	if ( $color_map[$j][$x] != $color_map[$y][$x] && $color_map[$y][$i] != $color_map[$y][$x] ) {
		if ( ($color_map[$j][$x] != $color_map[$y][$i]))
			return true;
		elseif ($color_map[$j][$i+1] && $color_map[$j-1][$i] && $color_map[$j][$i+1] != $color_map[$y][$x] && $color_map[$j-1][$i] != $color_map[$y][$x])
			return true;
		elseif ($j == H - 1 || $i == W - 1)
			return true;
		//elseif ( ($log[$j][$x] != 'dot' || $log[$y][$i] != 'dot') && $checked[$j][$x] && $checked[$y][$i] && !in_array('DR',$log[$j][$x]) && !in_array('UL',$log[$y][$i]) )
		//	return true;
		elseif ($color_map[$j][$x] == -1 && $color_map[$y][$i] == -1)
			return true;
	}
	return false;	
	//($color_map[$y-1][$x] != $color_map[$y][$x+1]) || ( ($log[$y-1][$x] != 'dot' || $log[$y][$x+1] != 'dot') && $checked[$y-1][$x] && $checked[$y][$x+1] && !in_array('DR',$log[$y-1][$x]) && !in_array('UL',$log[$y][$x+1]) ) || ($color_map[$y-1][$x] == -1 && $color_map[$y][$x+1] == -1)
}

function checkDR($x,$y) {
	global $color_map, $log, $checked;
	$i = $x+1;
	$j = $y+1;
	if ( $color_map[$j][$x] != $color_map[$y][$x] && $color_map[$y][$i] != $color_map[$y][$x] ) {
		if ( ($color_map[$j][$x] != $color_map[$y][$i]))
			return true;
		elseif ($color_map[$j][$i+1] && $color_map[$j+1][$i] && $color_map[$j][$i+1] != $color_map[$y][$x] && $color_map[$j+1][$i] != $color_map[$y][$x])
			return true;
		elseif ($j == H - 1 || $i == W - 1)
			return true;
		//elseif ( ($log[$j][$x] != 'dot' || $log[$y][$i] != 'dot') && $checked[$j][$x] && $checked[$y][$i] && !in_array('UR',$log[$j][$x]) && !in_array('DL',$log[$y][$i]) )
		//	return true;
		elseif ($color_map[$j][$x] == -1 && $color_map[$y][$i] == -1)
			return true;
	}
	return false;
}


function checkUL($x,$y) {
	global $color_map, $log, $checked;
	$i = $x-1;
	$j = $y-1;
	if ( $color_map[$j][$x] != $color_map[$y][$x] && $color_map[$y][$i] != $color_map[$y][$x] ) {
		if ( ($color_map[$j][$x] != $color_map[$y][$i]))
			return true;
		elseif ($color_map[$j][$i-1] && $color_map[$j-1][$i] && $color_map[$j][$i-1] != $color_map[$y][$x] && $color_map[$j-1][$i] != $color_map[$y][$x])
			return true;
		elseif ($j == H - 1 || $i == W - 1)
			return true;
		//elseif ( ($log[$j][$x] != 'dot' || $log[$y][$i] != 'dot') && $checked[$j][$x] && $checked[$y][$i] && !in_array('UR',$log[$j][$x]) && !in_array('DL',$log[$y][$i]) )
		//	return true;
		elseif ($color_map[$j][$x] == -1 && $color_map[$y][$i] == -1)
			return true;
	}
	return false;
}

function checkDL($x,$y) {
	global $color_map, $log, $checked;

	$i = $x-1;
	$j = $y+1;
	
	if ( $color_map[$j][$x] != $color_map[$y][$x] && $color_map[$y][$i] != $color_map[$y][$x] ) {
		if ( ($color_map[$j][$x] != $color_map[$y][$i]))
			return true;
		elseif ($color_map[$j][$i-1] && $color_map[$j+1][$i] && $color_map[$j][$i-1] != $color_map[$y][$x] && $color_map[$j+1][$i] != $color_map[$y][$x])
			return true;
		elseif ($j == H - 1 || $i == W - 1)
			return true;
		//elseif ( ($log[$j][$x] != 'dot' || $log[$y][$i] != 'dot') && $checked[$j][$x] && $checked[$y][$i] && !in_array('DR',$log[$j][$x]) && !in_array('UL',$log[$y][$i]) )
		//	return true;
		elseif ($color_map[$j][$x] == -1 && $color_map[$y][$i] == -1)
			return true;
	}
	return false;
	
}



function getLine(&$line,$a,$n,$org_d,$x,$y) {
	global $color_map,$log,$checked;

	$checked[$y][$x] = true;
	array_push($line,$x,$y);
	
	$log[$y][$x] = array();
	
	$ad = array('R','D','L','U');
	shuffle($ad);
	$bd = array('UR','DR','DL','UL');
	shuffle($bd);
	
	$direction = array_merge(array($org_d),$ad,$bd);
	
	//$direction = array($org_d,'R','D','L','U','DR','DL','UL','UR');
	$direction = array_unique($direction);

	$change = 0;
	
	foreach ($direction as $d) {
		if (count($line) >= 200) return;
		
		// Up
		if ($d == 'U') {
			$i = $x;
			$j = $y-1;
			if ($j >= 0) {
				if ($a[$j][$i] && !$checked[$j][$i]) {
					if ($org_d != $d) {
						array_push($line,$x,$y);
						++$change;
					}
					getLine($line,$a,1,$d,$i,$j);
					array_push($log[$y][$x],'U');
				}
			}
		}
		
		// Right
		if ($d == 'R') {
			$i = $x+1;
			$j = $y;
			if ($i < W) {
				if ($a[$j][$i] && !$checked[$j][$i]) {
					if ($org_d != $d) {
						array_push($line,$x,$y);
						++$change;
					}
					getLine($line,$a,1,$d,$i,$j);
					array_push($log[$y][$x],'R');
				}
			}
		}
		
		// Down
		if ($d == 'D') {
			$i = $x;
			$j = $y+1;
			if ($j < H) {
				if ($a[$j][$i] && !$checked[$j][$i]) {
					if ($org_d != $d) {
						array_push($line,$x,$y);
						++$change;
					}
					getLine($line,$a,1,$d,$i,$j);
					array_push($log[$y][$x],'D');
				}
			}
		}
		
		// Left
		if ($d == 'L') {
			$i = $x-1;
			$j = $y;
			if ($i >= 0) {
				if ($a[$j][$i] && !$checked[$j][$i]) {
					if ($org_d != $d) {
						array_push($line,$x,$y);
						++$change;
					}
					getLine($line,$a,1,$d,$i,$j);
					array_push($log[$y][$x],'L');
				}
			}
		}
		
		
		// Up Right
		if ($d == 'UR') {
			$i = $x+1;
			$j = $y-1;
			if ($i < W && $j >= 0) {
				// 01
				// 10
				if ($a[$j][$i] && !$checked[$j][$i]) {
					if (checkUR($x,$y)) {
							if ($org_d != $d) {
								array_push($line,$x,$y);
								++$change;
							}
							getLine($line,$a,1,$d,$i,$j);
							array_push($log[$y][$x],'UR');
					}
				}
			}
		}
		
		// Down Right
		if ($d == 'DR') {
			$i = $x+1;
			$j = $y+1;
			if ($i < W && $j < H) {
				if ($a[$j][$i] && !$checked[$j][$i]) {
					if (checkDR($x,$y)) {
					
						if ($org_d != $d) {
							array_push($line,$x,$y);
							++$change;
						}
						getLine($line,$a,1,$d,$i,$j);
						array_push($log[$y][$x],'DR');
					}
				}
			}
		}
		
		// Down Left
		if ($d == 'DL') {
			$i = $x-1;
			$j = $y+1;
			if ($i >= 0 && $j < H) {
				if ($a[$j][$i] && !$checked[$j][$i]) {
					// 01
					// 10
					if (!$a[$y+1][$x] && !$a[$y][$x-1]) {
						if (checkDL($x,$y)) {
							if ($org_d != $d) {
								array_push($line,$x,$y);
								++$change;
							}
							getLine($line,$a,1,$d,$i,$j);
							array_push($log[$y][$x],'DL');
						}
					}
				}
			}
		}
		
		// Up Left
		if ($d == 'UL') {
			$i = $x-1;
			$j = $y-1;
			if ($i >= 0 && $j >= 0) {
				if ($a[$j][$i] && !$checked[$j][$i]) {
					if (checkUL($x,$y)) {
						if ($org_d != $d) {
							array_push($line,$x,$y);
							++$change;
						}
						getLine($line,$a,1,$d,$i,$j);
						array_push($log[$y][$x],'UL');
					}
				}
			}
		}
		
		
		if ($change) array_push($line,$x,$y);
		elseif ($n == 0) $log[$y][$x] = 'dot';
	}
	
}

function drawLineNew($color,$line) {
	//echo $prevX.' '.$prevY.' '.$x.' '.$y.'<br>';
	$count = count($line);
	$draw = array($color,START_X + $line[0]*SIZE,START_Y + $line[1]*SIZE,0,0);
	for ($i=2;$i<$count;$i+=2) {
		$prevX = $line[$i-2];
		$prevY = $line[$i-1];
		$x = $line[$i];
		$y = $line[$i+1];
		
		$xDist = ($x - $prevX) * SIZE;
		$yDist = ($y - $prevY) * SIZE;
		//$dist = sqrt($xDist * $xDist + $yDist * $yDist);
		//if (abs(5 < $dist)) {
		array_push($draw,$xDist,$yDist);
		//}
		//else die('ERROR');
	}
	array_push($draw,0,0);

	// Strip array
	$i = 6;
	while ($i<count($draw)-3) {
		$n = 0;
		$m = $i;
		$x = $draw[$m];
		$y = $draw[$m+1];
		if ($x || $y) {
			while ($draw[$m+2] == $x && $draw[$m+3] == $y) {
				++$n;
				$m += 2;
			}
			if ($n != 0) {
				$n++;
				array_splice($draw,$i,$n*2,array($x*$n,$y*$n));
			}
		}
		$i += 2;
	}
	
	return $draw;
}

$s = startDraw();

header('Content-disposition: attachment; filename='.substr(IMAGE_NAME,0,-4).'.txt');
header('Content-type: text/plain; charset=UTF-8');
header('Content-Length: '.strlen($s));
echo $s;

?>