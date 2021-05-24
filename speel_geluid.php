<?php

function speel_piep(){
	echo '<iframe src="geluiden/piep.mp3" allow="autoplay" volume="50" style="display:none" id="iframeAudio"></iframe>';
}

function speel_boem(){
	$i = random_int(1,3);
	echo '<iframe src="geluiden/boem'.$i.'.m4a" allow="autoplay" volume="50" style="display:none" id="iframeAudio"></iframe>';
	echo "BOEMM";
}


?>
