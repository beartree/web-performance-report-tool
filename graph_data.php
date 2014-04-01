<?php
require('../global.php');
//require_once('mysqli_connect.php');

$tier = 1;$tc = 1;$span = 6;$per1sec = 0;
$y_axis = array();
$x_axis = array();
$spans = array(7,1,3,6,12,0);
$colors = array("red","green","blue","purple","black","DarkSlateBlue","FireBrick","Brown","CadetBlue","CornflowerBlue","Crimson","Indigo","Teal","Chocolate","Tomato","OrangeRed","Coral","DimGray", "GoldenRod", "IndianRed", "Maroon", "OliveDrab","MidnightBlue","SaddleBrown","SteelBlue","Peru","Fuchsia","LightSeaGreen","OliveDrab", "YellowGreen" );
$color_key = array_rand($colors);
$color = $colors[$color_key];
$all_case_tips = "['t1c1', 't1c2', 't1c3', 't1c4', 't1c5', 't2c1', 't2c2', 't2c3', 't2c4', 't2c5', 't2c6', 't2c7', 't2c8', 't2c9', 't2c10', 't2c11', 't2c12', 't2c13', 't2c14', 't2c15', 't3c1', 't3c2', 't3c3', 't5c1', 't5c2', 't5c3', 't5c4', 't5c5']";
$all_case_tips1 = "['t1c1'], ['t1c2'], ['t1c3'], ['t1c4'], ['t1c5'], ['t2c1'], ['t2c2'], ['t2c3'], ['t2c5'], ['t2c6'], ['t2c7']";
$today=date('Y-m-d',strtotime('+1 days',strtotime(today)));
$week_1=date('Y-m-d',strtotime('-15 days',strtotime(today)));
$mths_1=date('Y-m-d',strtotime('-31 days',strtotime(today)));
$mths_3=date('Y-m-d',strtotime('-91 days',strtotime(today)));
$mths_6=date('Y-m-d',strtotime('-183 days',strtotime(today)));
$mths_12=date('Y-m-d',strtotime('-365 days',strtotime(today)));
$span_type = 1;$filter = "avg";$show_sla = 0;

if(isset($_COOKIE['TIER']))      $tier       = $_COOKIE['TIER'];
if(isset($_COOKIE['TC']))        $tc         = $_COOKIE['TC'];
if(isset($_COOKIE['SPAN']))      $span       = $_COOKIE['SPAN'];
if(isset($_COOKIE['PER1SEC']))   $per1sec    = $_COOKIE['PER1SEC'];
if(isset($_COOKIE['SPAN_TYPE'])) $span_type  = $_COOKIE['SPAN_TYPE'];
if(isset($_COOKIE['DATE_SUB']))  $date_sub   = $_COOKIE['DATE_SUB'];
if(isset($_COOKIE['DATE_FROM'])) $date_from  = $_COOKIE['DATE_FROM'];
if(isset($_COOKIE['DATE_TO']))   $date_to    = $_COOKIE['DATE_TO'];
if(isset($_COOKIE['FILTER']))    $filter     = $_COOKIE['FILTER'];
if(isset($_COOKIE['SHOW_SLA']))  $show_sla   = $_COOKIE['SHOW_SLA'];

if (isset($_POST['tier']))      $tier       =  $_POST['tier'];
if (isset($_POST['tc']))        $tc         =  $_POST['tc'];
if (isset($_POST['span']))      $span       =  $_POST['span'];
if (isset($_POST['per1sec']))   $per1sec    =  $_POST['per1sec'];
if (isset($_POST['span_type'])) $span_type  =  $_POST['span_type'];
if (isset($_POST['date_sub']))  $date_sub   =  $_POST['date_sub'];
if (isset($_POST['date_from'])) $date_from  =  $_POST['date_from'];
if (isset($_POST['date_to']))   $date_to    =  $_POST['date_to'];
if (isset($_POST['filter']))    $filter     =  $_POST['filter'];
if (isset($_POST['show_sla']))  $show_sla   =  $_POST['show_sla'];
if (isset($_POST['show_all']))  $show_all   =  $_POST['show_all'];

if ($per1sec == 1 || $filter == 'hits'){
        $show_sla = 0;
        $show_all = 0;
}
if ($span_type != 1){
	$show_all = 0;
}
if ($show_all == 1){
	$show_sla = 0;
	$span = 7; 
}
setcookie('TIER',$tier,time()+3600);
setcookie('TC',$tc,time()+3600);
setcookie('SPAN',$span,time()+3600);
setcookie('PER1SEC',$per1sec,time()+3600);
setcookie('SPAN_TYPE',$span_type,time()+3600);
setcookie('DATE_SUB',$date_sub,time()+3600);
setcookie('DATE_FROM',$date_from,time()+3600);
setcookie('DATE_TO',$date_to,time()+3600);
setcookie('FILTER',$filter,time()+3600);
setcookie('SHOW_SLA',$show_sla,time()+3600);

$sql_start_date = 'SELECT start_time FROM qaw_perf_reports order by id asc LIMIT 1';
$rs=mysql_query($sql_start_date,$dbc);
$row = mysql_fetch_assoc($rs);
$start_date_time = $row['start_time'];
$start_date = date('Y-m-d',strtotime($start_date_time));

switch($span){
        case '1'   : $mths  = $mths_1   	;break;
        case '3'   : $mths  = $mths_3   	;break;
	case '6'   : $mths  = $mths_6   	;break;
	case '12'  : $mths  = $mths_12  	;break;
	case '0'   : $mths  = $start_date	;break;
        case '7'   : $mths  = $week_1           ;break;
        default    : $mths  = $mths_6   	;break;
}


$sql_sla = 'SELECT temp.m.sla from temp.qaw_perf_sla m WHERE tier = %s';
$sql_sla = sprintf($sql_sla, $tier);
$rs = mysql_query($sql_sla, $dbc);
$row = mysql_fetch_assoc($rs);
$sla = $row['sla'];

$date1 = $mths ; $date2 = $today;
if (($span_type == 2) && ($date_sub == 1)) { 
  $epoc_from = strtotime($date_from);
  $epoc_to   = strtotime($date_to);
  if ($epoc_from === false || $epoc_from < 0 || $epoc_to === false || $epoc_to < 0) {
      $error = 'Unable to parse dates';
  } else if ($epoc_from > $epoc_to) {
      $error = '[From date] cannot be greater than [To date]';
  } else {
      $date1 = date('Y-m-d', $epoc_from);
      $date2 = date('Y-m-d', $epoc_to);
  }
}

$date_range_1 = date('d-M-y', strtotime($date1));
$date_range_2 = date('d-M-y', strtotime($date2));
$date_range = $date_range_1.' to '.$date_range_2;


$sql1='SELECT d.release_name as rname,r.start_time as stime,d.tier as tier,d.tc as tc ,d.avg as avg,d.per1sec as per1sec_each
      FROM qaw_perf_reports r INNER JOIN qaw_perf_data d 
      ON d.rid=r.id';

$sql2='SELECT d.release_name as rname,r.start_time as stime,sum(d.hits),sum(d.1sec),(sum(d.1sec)/sum(d.hits)*100) as per1sec
      FROM qaw_perf_reports r INNER JOIN qaw_perf_data d 
      ON d.rid=r.id';

if (((($span_type == 1) && ($span != 0)) && ($show_all != 1) || ($span_type == 2)) && ($per1sec != 1)) {
	$sql1 .= ' WHERE r.start_time BETWEEN "%s" and "%s" AND tier="%s" AND tc="%s" ORDER BY r.ctime asc';
	$sql1=sprintf($sql1,$date1,$date2,$tier,$tc);
}
if ((($span_type == 1) && ($span == 0)) && ($per1sec != 1) && ($show_all != 1)) {
        $sql1 .= ' WHERE tier=%s and tc=%s ORDER BY r.ctime asc';
	$sql1=sprintf($sql1,$tier,$tc);
}
if ((($span_type == 1) && ($show_all == 1)) && ($per1sec != 1)) {
	$sql1 .= ' WHERE r.start_time BETWEEN  "%s" and "%s" GROUP BY tier, tc, stime ORDER BY tier, tc';
	$sql1 = sprintf($sql1, $date1, $date2);
}
if (((($span_type == 1) && ($span != 0)) || ($span_type == 2)) && ($per1sec == 1)) {
	$sql2 .= ' WHERE r.start_time BETWEEN "%s" AND "%s" GROUP BY d.rid ORDER BY r.ctime asc';
	$sql2=sprintf($sql2,$date1,$date2);
}
if ((($span_type == 1) && ($span == 0)) && ($per1sec == 1)) {
	$sql2 .= ' GROUP BY d.rid ORDER BY r.ctime asc';
}

if ($per1sec !=1) { $rs=mysql_query($sql1,$dbc); } else { $rs=mysql_query($sql2,$dbc); } 

if ($rs && $show_all != 1) {
   while ($row = mysql_fetch_assoc($rs)) {
    $x_axis[] = $row['stime'];
    if($per1sec !=1) {
      if ($filter == "avg") {
	 $y_axis[] = $row['avg'];
	 $y_axis1[] = $sla;
         $tooltip[] = '<b>Release: </b>'.$row['rname'].'<br/><b>Start Time: </b>'.$row['stime'].'<br/><b>Average(ms): </b>'.$row['avg'].'ms';
         $tooltip1[] = '<b>Release: </b>'.$row['rname'].'<br/><b>Start Time: </b>'.$row['stime'].'<br/><b>Average(ms): </b>'.$sla.'ms';
      } else if ($filter == "hits") {
         $y_axis[] = $row['per1sec_each'];
         $tooltip[] = '<b>Release: </b>'.$row['rname'].'<br/><b>Start Time: </b>'.$row['stime'].'<br/><b>Above 1 sec(&#37;): </b>'.$row['per1sec_each'].'&#37;';
      }
    } else {
       $y_axis[] = $row['per1sec'];
       $tooltip[] = '<b>Release: </b>'.$row['rname'].'<br/><b>Start Time: </b>'.$row['stime'].'<br/><b>Above 1 sec(&#37;): </b>'.$row['per1sec'].'&#37;';
    }
   }
   switch($filter){
      case 'avg' :  $y_title = 'Average Response Time (ms)';
		    $y_max = max($y_axis)+20;
		    $units = ' ms';
		    $title = 'Tier '.$tier.' : Test Case '.$tc.' (Avg Response)';
		    break;
      case 'hits' : $y_title = 'Percentage above 1 Second';
                    $y_max = 5;
                    $units = ' %';
		    $title = 'Tier '.$tier.' : Test Case '.$tc.' (% above 1 sec)';
                    break;
      default    :  '';break;
   }
   $x_count = count($x_axis);
   $y_data = "[" . join(", ", $y_axis) . "]";
   if ($filter == "avg" ) $y_data1 = "[" . join(", ", $y_axis1) . "]";
   $x_data = "['" . join("', '", $x_axis) . "']";
   $tooltip_data = "['" . join("', '", $tooltip) . "']";
   if ($filter == "avg" ) $tooltip_data1 = "['" . join("', '", $tooltip1) . "']";
}else if($rs && $show_all == 1){
   while ($row = mysql_fetch_assoc($rs)){
       $x_axis[] = $row['stime'];
       if($per1sec !=1) {
         if ($filter == "avg") {
	    $y_axis[$row['tier']][$row['tc']][] = $row['avg'];
	    $tooltip[] = 't'.$row['tier'].'c'.$row['tc'];
       }else if ($filter == "hits"){
	    $y_axis[$row['tier']][$row['tc']][] = $row['per1sec_each'];
       }
       }
   }
   switch($filter){
      case 'avg' :  $y_title = 'Average Response Time (ms)';
                    $y_max = max(max(max($y_axis)))+20;
                    $units = ' ms';
                    $title = 'All Test Cases';
                    break;
      case 'hits' : $y_title = 'Percentage above 1 Second';
                    $y_max = 5;
                    $units = ' %';
                    $title = 'All Test Cases';
                    break;
      default    :  '';break;
   }
   $x_axis = array_unique($x_axis);
   $x_count = count($x_axis);
   $last_col = end($colors);
   $all_colors = "[";
   foreach($colors as $col){
	if ($col != $last_col) $all_colors .= "'".$col."', ";
	else $all_colors .= "'".$col."']";
   }
   $tooltip_data = "['" . join("', '", $tooltip) . "']";
}else {
   echo "Error :" . mysqli_error();
}
if ($per1sec == 1) { $y_title = 'Percentage above 1 second (%)'; $y_max=1.5; $units = '%';$title = 'Percentage above 1 Second';}
?>
<script type="text/javascript">
<?php if ($show_all != 1){?>
function draw_graph() {
   var gutterLeft = 100;
   var gutterRight = 60;
   var gutterTop   = 50;
   var gutterBottom = 20;


  var line = new RGraph.Line('graph_popup_in',<? print($y_data) ;  if ($filter == "avg" && $per1sec != 1 && $show_sla == 1) print ", ".$y_data1?>);
  line.Set('chart.title','<? h($title); ?>');
  line.Set('chart.title.vpos',0.1);
  line.Set('chart.labels', ['<? h($date1) ;?>','<? h($date2) ;?>']);
  line.Set('chart.labels.specific.align', 'left');
  //line.Set('chart.title.xaxis', '<? h($x_title); ?>');
  line.Set('chart.title.yaxis', '<? h($y_title); ?>');
  line.Set('chart.tooltips',<? echo ($tooltip_data) ;?>);
  line.Set('chart.units.post', '<? h($units); ?>');
  line.Set('chart.backdrop', true);
  line.Set('chart.text.size', 8);
  line.Set('chart.backdrop.size', 2);
  line.Set('chart.colors', ['<? h($color); ?>' <? if ($filter == "avg" && $per1sec != 1 && $show_sla == 1) echo ", 'magenta'" ?>]);
  line.Set('chart.tooltips.effect', 'fade');
  line.Set('chart.text.angle', 0);
  line.Set('chart.gutter.right', 60);
  line.Set('chart.gutter.left', 100);
  line.Set('chart.title.xaxis.pos',0.06);
  line.Set('chart.title.yaxis.pos',0.1);
  line.Set('chart.background.grid', true);
  line.Set('chart.background.grid.color', "#ccc");
  line.Set('chart.background.grid.autofit', true);
  // line.Set('chart.background.grid.autofit.align', true);
  line.Set('chart.gutter.bottom', 20);
  line.Set('chart.gutter.top', 50);
  line.Set('chart.hmargin', 0);
  line.Set('chart.linewidth', 1);
  //line.Set('chart.shadow', true);
  line.Set('chart.shadow.offsetx', 4);
  //line.Set('chart.tooltips.css.class', 'line_chart_tooltips_css');
  line.Set('chart.ymax', <? if ($show_sla == 1) print(max($y_max, $sla)); else print ($y_max); ?>);
  line.Set('chart.scale.decimals',2);
  line.Set('chart.shadow.blur', 2);
  line.Set('chart.tickmarks', 'circle');
  line.Draw();

}
<?php }else{ ?>
function draw_graph(){
   var gutterLeft = 100;
   var gutterRight = 60;
   var gutterTop   = 50;
   var gutterBottom = 20;
   var once = true;
   <?php
	$i = 0;
	foreach ($y_axis as $v1) {
		foreach ($v1 as $v2) {
			$y_data = "[" . join(", ", $v2) . "]";
			$all_data[] = $y_data?>
   <?php	}
	 }?>
			var line = new RGraph.Line('graph_popup_in',<? $last = end($all_data); foreach($all_data as $data) {if ($data != $last) print($data.',') ; else print ($data); }?>);
 			line.Set('chart.title','<? h($title); ?>');
                        line.Set('chart.title.vpos',0.1);
                        line.Set('chart.labels', ['<? h($date1) ;?>','<? h($date2) ;?>']);
                        line.Set('chart.labels.specific.align', 'left');
                        //line.Set('chart.title.xaxis', '<? h($x_title); ?>');
                        line.Set('chart.title.yaxis', '<? h($y_title); ?>');
                        line.Set('chart.tooltips',<? echo  $tooltip_data; ?>);
                        line.Set('chart.units.post', '<? h($units); ?>');
                        line.Set('chart.backdrop', true);
                        line.Set('chart.text.size', 8);
                        line.Set('chart.backdrop.size', 2);
                        line.Set('chart.colors', <? echo $all_colors; ?>);
                        line.Set('chart.key', <? echo $all_case_tips; ?>);
			line.Set('chart.key.position.x', 1045);
                        line.Set('chart.tooltips.effect', 'fade');
                        line.Set('chart.text.angle', 0);
                        line.Set('chart.gutter.right', 60);
                        line.Set('chart.gutter.left', 100);
                        line.Set('chart.title.xaxis.pos',0.06);
                        line.Set('chart.title.yaxis.pos',0.1);
                        line.Set('chart.background.grid', true);
                        line.Set('chart.background.grid.color', "#ccc");
                        line.Set('chart.background.grid.autofit', true);
                        // line.Set('chart.background.grid.autofit.align', true);
                        line.Set('chart.gutter.bottom', 20);
                        line.Set('chart.gutter.top', 50);
                        line.Set('chart.hmargin', 0);
                        line.Set('chart.linewidth', 1);
                        //line.Set('chart.shadow', true);
                        line.Set('chart.shadow.offsetx', 4);
                        //line.Set('chart.tooltips.css.class', 'line_chart_tooltips_css');
                        line.Set('chart.ymax', <? print($y_max); ?>);
                        line.Set('chart.scale.decimals',2);
                        line.Set('chart.shadow.blur', 2);
                        line.Set('chart.tickmarks', 'circle');
                        line.Draw();

}

<?php }?>
</script>
<!--break-->
<table id="opts">
 <tr>
    <td rowspan="2" style="padding-right:10px;border-right:solid 1px;"><input type="submit" class="per1sec button yellow" value="Total &#37; above 1 sec" <? if($per1sec==1) {?> disabled="disabled" <?}?>/></td>
    <td style="padding-left:10px;"><b>Tier 1 Cases : </b></td>
    <?php for ($i=1;$i<6;$i++) {?>
    <td><input type="submit" name="tier1_tc<? h($i); ?>" class="testcase 1_<? h($i); ?> button green" value="<? h($i) ;?>" 
         <? if(($tier==1) && ($i==$tc) && ($per1sec!=1)) {?> disabled="disabled"  style="color:#666;"<?}?>/>
    </td>
    <?php } ?>
    <td></td><td></td><td></td><td></td><td></td><td></td><td></td></td><td></td><td></td><td></td><td></td><td></td>
    <td rowspan="4" style="padding-left:10px;">
	<div><input type="radio" name="avg"  class="filter avg"  value="avg" <? if ($per1sec==1) {?> disabled="disabled"<?}?><? if ($filter == "avg") {?> checked="true" <?}?>/><b> Avg response Time</b></div>
	<div><input type="radio" name="hits" class="filter hits" value="hits" <? if ($per1sec==1) {?> disabled="disabled"<?}?><? if ($filter == "hits") {?> checked="true" <?}?>/><b> % Hits &gt; 1 Second</b></div>
    </td>
 </tr>
 <tr>
    <td style="padding-left:10px;"><b>Tier 2 Cases : </b></td>
    <?php for ($j=1;$j<17;$j++) {?>
    <td><input type="submit" name="tier2_tc<? h($j); ?>" class="testcase 2_<? h($j); ?> button green" value="<? h($j) ;?>"
         <? if(($tier==2) && ($j==$tc) && ($per1sec!=1)) {?> disabled="disabled" style="color:#666;"<?}?>/>
    </td>
    <?php } ?>
 </tr>

 <tr>
    <td style="padding-left:10px;"><input type = "checkbox" class="show_sla" value="1"  <? if ($per1sec==1) {?> disabled="disabled"<?}?><? if ($show_sla == 1) {?> checked="true" <?}?>/><b> Show SLA </b></td>
    <td style="padding-left:10px;"><b>Tier 3 Cases : </b></td>
    <?php for ($j=1;$j<4;$j++) {?>
    <td><input type="submit" name="tier3_tc<? h($j); ?>" class="testcase 3_<? h($j); ?> button green" value="<? h($j) ;?>"
         <? if(($tier==3) && ($j==$tc) && ($per1sec!=1)) {?> disabled="disabled" style="color:#666;"<?}?>/>
    </td>
    <?php } ?>
 </tr>

 <tr>
    <?php if ($span == 7) {?><td style="padding-left:10px;"><input type = "checkbox" class="show_all" value="1"  <? if ($per1sec==1) {?> disabled="disabled"<?}?><? if ($show_all == 1) {?> checked="true" <?}?>/><b> Show All </b></td> <?php }else echo "<td/>" ;?> 
    <td style="padding-left:10px;"><b>Tier 5 Cases : </b></td>
    <?php for ($j=1;$j<6;$j++) {?>
    <td><input type="submit" name="tier5_tc<? h($j); ?>" class="testcase 5_<? h($j); ?> button green" value="<? h($j) ;?>"
         <? if(($tier==5) && ($j==$tc) && ($per1sec!=1)) {?> disabled="disabled" style="color:#666;"<?}?>/>
    </td>
    <?php } ?>
 </tr>


</table>
<!--break-->
<div>
  <input type="radio" name="span1" class="span_type 1" value="span1" <? if ($span_type==1) {?> checked="true" <?}?>/> Select Time frame 
  <input type="radio" name="span2" class="span_type 2" value="span2" <? if ($span_type==2) {?> checked="true" <?}?>/> Specify Time frame
  <span style="font-size:11px;color:#333333;"> [In a format accepted by strtotime()]</span>
</div>
<div id="span_options">
<?php if (isset($span_type) && ($span_type == 1)) {?>
<table>
 <tr>
    <?php foreach($spans as $k) {
		switch($k){
                  case '7'   : $val  = "Last 2 Weeks"     ;break;
        	  case '1'   : $val  = "Last 1 Month"    ;break;
	          case '3'   : $val  = "Last 3 Months"   ;break;
	          case '6'   : $val  = "Last 6 Months"   ;break;
	          case '12'  : $val  = "Last 12 Months"  ;break;
		  case '0'   : $val  = "Since the Start" ;break;
        	  default    : $val  = "Last 6 Months"   ;break;
		}
    ?>
    <td><input type="submit" name="span_<? h($k); ?>" class="span <? h($k); ?> button magenta" value="<? h($val) ;?>"
         <? if (($k==$span)) {?> disabled="disabled" style="color:#000;"<?}?>/>
    </td>
    <?php } ?>
 </tr>
</table>
<? } elseif (isset($span_type) && ($span_type == 2)) {?>
<table>
 <tr>
     <td><b>From : </b></td><td><input type="text" name="date_from" class="date date_from" value="<? if (isset($date_from)) { h($date_from); } else {  h($date1) ;}?>"/></td>
     <td><b> To : </b></td><td><input type="text" name="date_to" class="date date_to" value="<? if (isset($date_to)){  h($date_to) ; } else {  h($date2) ;}?>"/></td>
     <td> <input type="submit" name="submit_date" id="submit_date" value="Submit"/></td>
 </tr>

</table>
<? } ?>
</div>
<div id="dates_display"><?h($date_range)?></div>
<div id="sub_error"><? if (isset($error)) { h($error); }?></div>

