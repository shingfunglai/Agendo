<?php
	require_once("commonCode.php");

	function treta(){
		echo ('bla');
		exit;
	}

	$postName = 'myvar';
	$var = $_GET[$postName];
	if(isset($var)){
		treta();
	}

?>
<script type='text/javascript' src='js/jquery-1.5.2.min.js'></script>

<script type="text/javascript" src="js/commonCode.js"></script>
<script type="text/javascript">
// $(document).ready(function() {
window.onload = function (){resizeMe();}

window.onresize = function (){resizeMe();}

	function resizeMe() {
		var element = document.getElementById('all');
		// var height = String(window.innerHeight-5) + 'px';
		var height = String(document.body.offsetHeight-10) + 'px';
		// var width = String(window.innerWidth-5) + 'px';
		var width = String(document.body.offsetWidth-10) + 'px';
		element.style.height = height;
		element.style.width = width;
	}

// });
	function ajaxTest(url, value){
		// value = 'received' + value;
		// $.post(	'teste.php', 
				// {vari:'teste'},
				// function(){
					// alert(value);
				// });
		// url = url + "'" + value + "'";
		// alert(url);
		alert(ajaxSend(url+value));
		// alert(getAjaxResult());
	}

</script>
<?php
	// $time1 = microtime(true);
	// $sql2 = "select date_format(entry_datetime, '%w') from entry where entry_id=611";
	// echo $sql2."<br></br>";
	// $res2 = dbHelp::mysql_query2($sql2);
	// $arr2 = dbHelp::mysql_fetch_row2($res2);
	// echo $arr2[0]."<br></br>";
		
	echo metaphone('klansy');
	echo "-";
	echo metaphone('clansy');
	echo "</br>";
	echo soundex('klansy');
	echo "-";
	echo soundex('clansy');
	echo "<br></br>";
	echo metaphone('moeno');
	echo "-";
	echo metaphone('moreno');
	echo "</br>";
	echo soundex('moeno');
	echo "-";
	echo soundex('moreno');
	echo "<br></br>";
	
	echo "<a href='android/AccellValues.apk'>file</a>";
	// $myTime = strtotime('201011231415');
	// $timePlus = $myTime + 60*60;
	// echo date('YmdHi',$myTime)."<br>";
	// echo date('YmdHi',$timePlus)."<br>";
	// if ($myTime < $timePlus) echo "asdasdasd";
	// else echo "qweqweqwe</br>";
	
	
	// echo "<table id=all border=10>";
		// echo "<tr>";
			// echo "<td>";
				// $url = 'teste.php?myvar=';
				// echo "<input id='text' type='text' width='50' style='text-align:center;' onkeyup=\"ajaxTest('".$url."',this.value)\">";
			// echo "</td>";
		// echo "</tr>";
	// echo "</table>";
?>