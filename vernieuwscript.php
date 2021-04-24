<?php
function vernieuw_functie($kamer_id){
	eigen_veranderingen();
	echo "<script>
		function check_if_we_have_to_refresh(){
			var xmlhttp = new XMLHttpRequest();
			xmlhttp.onreadystatechange = function() {
				if (this.readyState == 4 && this.status == 200) {
					//document.getElementById('bla').innerHTML += aantal_veranderingen;	
					if(net_geladen == 1){
						aantal_veranderingen = parseInt(this.responseText);
						localStorage.setItem('aantal_veranderingen', aantal_veranderingen);
						net_geladen = 0;
					}						
					if(aantal_veranderingen != this.responseText){						
						aantal_veranderingen = parseInt(this.responseText);
						localStorage.setItem('aantal_veranderingen', aantal_veranderingen);
						//document.getElementById('bla').innerHTML += this.responseText;
						window.location.href = window.location.href;
					}
				}
			}
			xmlhttp.open('POST','check_vernieuwing.php?kamer_id=".$kamer_id."',true);
			xmlhttp.send();
		}
		aantal_veranderingen = parseInt(localStorage.getItem('aantal_veranderingen'));
		if(aantal_veranderingen == null){
			aantal_veranderingen = 0;
		}
		net_geladen = 1;
		localStorage.setItem('aantal_veranderingen', aantal_veranderingen);
		setInterval(check_if_we_have_to_refresh, 500);
		</script>
		";
}

function eigen_veranderingen(){
	global $aantal_eigen_veranderingen;
	echo "
		<script>
		var aantal_veranderingen = parseInt(localStorage.getItem('aantal_veranderingen'));
		if(aantal_veranderingen != null){
			localStorage.setItem('aantal_veranderingen', aantal_veranderingen + parseInt(".$aantal_eigen_veranderingen."));
		}
		</script>
		";
}


?>