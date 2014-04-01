<?php
require('../global.php');
//--- Jennys table "cobrand_api_log" is in pricegrabber DB --//
//--- Just this 1 table is in another DB, hence not making  global addition--//
$dbc2  = mysql_connect($dbh, $dbu, $dbp, true) or dberr('Cannot connect to database');
mysql_select_db('pricegrabber',$dbc2) or dberr();
//------------------------------------------------------------//

if (isset($_POST['rt']) && ($_POST['rt']==1)){
  $display=15;
  if (isset($_POST['p'])){
     $pages=$_POST['p'] ;
  } else {
     $sql_r1='SELECT COUNT(*) AS count FROM qaw_perf_reports';
     $rs_r1=mysql_query($sql_r1,$dbc);
     $row_r1=mysql_fetch_array($rs_r1);
     $records=$row_r1['count'];
     if ($records > $display) {
       $pages = ceil($records/$display);
     } else {
       $pages=1;
     }
  }
  if (isset($_POST['s'])) {
      $start=$_POST['s'];
   } else {
      $start=0;
   }
   $sql_r2='SELECT id,release_name,cobrand_id,per1sec
            FROM qaw_perf_reports ORDER BY ctime desc LIMIT %d,%d';
   $sql_r2 = sprintf($sql_r2,$start,$display);
   $rs_r2 = mysql_query($sql_r2,$dbc);?>
   <h1 style="text-align:center;">Performance Reports</h1><br />
   <table class="report_table">
   <tr><th>No.</th><th>Release</th><th>Report</th><th>Cobrand</th><th>% 1sec</th></tr>
<?php if ($rs_r2) {
      $bg = '#eeeeee';
      $count = $start + 1;
       while ($rows_r2 = mysql_fetch_array($rs_r2)) {
        $bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee');?>
        <tr class="report_row" bgcolor='<? h($bg);?>'>
          <td><? h($count); $count++; ?></td>
          <td class="rel_name"><b><? h($rows_r2['release_name']);?></b></td>
          <td>
            <span class="report_button report_id_<? h($rows_r2['id']); ?>">
             <input type="image" src="./images/webperf_report.gif" id="report_icon" />
            </span>
          </td>
          <td><? h($rows_r2['cobrand_id']); ?></td>
          <td><? h($rows_r2['per1sec']); ?> %</td>
        </tr>
<?php  }
      } else { ?>
          <p>Error executing Query:<? echo mysql_error($dbc);?></p></br>
<?php } ?>
   </table><br />
<?php //-------Pagination links-------
   if ($pages > 1) {
      $current_page=($start/$display)+1;?>
      <div align="center"><p>
<?php if ($current_page != 1) { ?> 
         <input class="pagination s_<? h($start-$display); ?> p_<?h($pages);?>" type="button" value="&#60;&#60;" />
<?php }
    for ($i=1;$i<=$pages;$i++){
      if ($i != $current_page){?>
         <input class="pagination s_<?h(($display*($i-1)));?> p_<?h($pages);?>" type="button" value="<?h($i);?>" />
 <?php } else {?>
         <input type="button" value="<?h($i);?>" disabled="disabled"/>
 <?php }
     }
    if ($current_page != $pages) {?>
      <input class="pagination s_<?h($start+$display);?> p_<?h($pages);?>" type="button" value="&#62;&#62;" />
 <?php  } ?>
 </p></div>
<?php 
 }
}
 
$total_hits=0;$hits_above_second=0;$total_qps=0;$percentage=0;
$api_log_query = "SELECT tier,test_case as tc,ROUND(sum(total_time)/sum(total_request),2) as avg,
        	  ROUND(max(max_time),2) as max,sum(total_request) as hits,
        	  (sum(total_request)/3600) AS qps,SUM(above_max_response) as 1sec,
                  (((SUM(above_max_response))*100)/sum(total_request)) AS per1sec
                  FROM cobrand_api_log
                  WHERE  cobrand_id = %d
                  AND recorded between  '%s' AND '%s'
                  AND tier IN (1,2,3,5)
                  AND test_case !=0
                  GROUP BY tier, test_case";
//On clicking Save
if (isset($_POST['save_submitted']) && ($_POST['save_submitted']==1)){
	$errors=array();
	if (isset($_POST['relname'])){
	  $rel_name = $_POST['relname'];
          $sql_s='SELECT release_name from qaw_perf_reports where release_name="%s"';
          $sql_s=sprintf($sql_s,$rel_name);
          $rs_s=mysql_query($sql_s,$dbc);
          $num_rows_s = mysql_num_rows($rs_s);
          if ($num_rows_s != 0) { ?>
		<span>Release name exists!</span>
    <?php } else {
	        $sql = $api_log_query;
		$start_time=$_POST['start_time'];
                $end_time=$_POST['end_time'];
                $cobrand_id=$_POST['cobrand_id'];
                $sql = sprintf($sql,$cobrand_id,$start_time,$end_time);
                $rs = mysql_query($sql,$dbc2) or dberr();
		if ($rs){
                   $num = mysql_num_rows($rs);
                   if ($num == 0 ) {?>
                        <span>No results:Cannot Save!</span>
           <?php   } else {
			$sql_i='INSERT INTO qaw_perf_reports (ctime,release_name,start_time,end_time,cobrand_id,per1sec) 
				VALUES (now(),"%s","%s","%s",%d,0)';
			$sql_i = sprintf($sql_i,$rel_name,$start_time,$end_time,$cobrand_id);
			$rs_i = mysql_query($sql_i,$dbc) or dberr();
			if ($rs_i) { 
			 $report_id = mysql_insert_id($dbc);	
		         while ($rows = mysql_fetch_assoc($rs)) {
                           $total_hits=$rows['TOTAL']+$total_hits;
                           $hits_above_second=$rows['1sec']+$hits_above_second;
                           $sql_i2='INSERT INTO qaw_perf_data (rid,release_name,tier,tc,avg,max,hits,qps,1sec,per1sec) 
				   VALUES ("%d","%s","%d","%d","%f","%f","%d","%f","%d","%f")';
                           $sql_i2=sprintf($sql_i2,$report_id,$rel_name,$rows['tier'],$rows['tc'],$rows['avg'],$rows['max'],
				   $rows['hits'],$rows['qps'],$rows['1sec'],$rows['per1sec']);
			   $rs_i2=mysql_query($sql_i2,$dbc) or dberr();
			 }
			 $percentage=round(($hits_above_second*100)/$total_hits,3);
			 $sql_u='UPDATE qaw_perf_reports SET per1sec = "%f" WHERE release_name = "%s" LIMIT 1';
			 $sql_u = sprintf($sql_u,$percentage,$rel_name);
			 $rs_u = mysql_query($sql_u,$dbc) or dberr();
		       }
		   }
		}		
	  }
	} else { ?>
	      <span>Release name missing!</span>
<?php   }
} else {
if (isset($_POST['req']) && ($_POST['req'] != null)) {
        $request_type = $_POST['req'];        
}
if ($request_type == "dr") {
	if (isset($_POST['d']) && ($_POST['d']==1)){
	  $sql_f='SELECT id from qaw_perf_reports ORDER BY ctime desc LIMIT 1';
          $rs_f=mysql_query($sql_f,$dbc) or dberr();
	  $row_f=mysql_fetch_assoc($rs_f);
	  $report_id=$row_f['id'];
	} else if (isset($_POST['s'])){
	  $pagination = $_POST['s'];
	  $sql_f='SELECT id from qaw_perf_reports ORDER BY ctime desc LIMIT 1 OFFSET %s';
	  $sql_f=sprintf($sql_f,$pagination);
          $rs_f=mysql_query($sql_f,$dbc) or dberr();
          $row_f=mysql_fetch_assoc($rs_f);
          $report_id=$row_f['id'];
        } else {
	   $report_id=$_POST['rid'] ;
	}
	$sql_q = 'SELECT release_name,start_time,end_time
		  FROM qaw_perf_reports
	          WHERE id = "%s" LIMIT 1';
	$sql_q=sprintf($sql_q,$report_id);
	$rs_q=mysql_query($sql_q,$dbc) or dberr();
	$row_q = mysql_fetch_assoc($rs_q);
	$release = $row_q['release_name'];
	$start_time = $row_q['start_time'];
	$end_time = $row_q['end_time'];
	$sql='SELECT tier,tc,avg,max,hits,qps,1sec,per1sec 
	      FROM qaw_perf_data
	      WHERE rid="%s"
	      GROUP BY tier,tc';
        $sql=sprintf($sql,$report_id);
	$header = '<b> Performance test against release : ['.$release.']</b><br/>
		   Results for last 60 mins of test run <br/>
		   <b>Sample Period Start  :  </b>['.$start_time.']<br /> 
                   <b>Sample Period End  : </b>['.$end_time.']<br />';
	$option = '<a href="javascript:window.print()"><img src="./images/Print.gif" id="print" style="border:0px"/></a>';
	$error = '<tr class="noresults"><td colspan="8"> Report missing/Corrupted </td></tr>';
	$rs=mysql_query($sql,$dbc) or dberr();
} else if ($request_type == "gr") {
	$cobrand_id=$_POST['pid'];
	$time=$_POST['stime'];
	$epoc_start_time=(strtotime($time)+1800);
	$epoc_end_time=(strtotime($time)+5400);
	$start_time=date("Y-m-d H:i:s",$epoc_start_time);
	$end_time=date("Y-m-d H:i:s",$epoc_end_time);
        $sql = $api_log_query;
	$sql = sprintf($sql,$cobrand_id,$start_time,$end_time);
	$header = '<div class="test_summary">
		   PERFORMANCE TEST RESULTS<br/>
		   Test Run : '.$time.'<br/>
		   Results for last 60 mins of test run<br/>
		   Sample Period : ['.$start_time.'] to ['.$end_time.']<br /></div>';
	$option = "<div class=\"save_button\">
		   <input type=\"text\" name=\"rel_name\"  style=\"width:110px\" id=\"rel_name\"/>
		   <input type=\"hidden\" id=\"start_time\" name=\"start_time\" value=\"$start_time\" />
		   <input type=\"hidden\" id=\"end_time\" name=\"end_time\" value=\"$end_time\" />
		   <input type=\"hidden\" id=\"cobrand_id\" name=\"cobrand_id\" value=\"$cobrand_id\" />
		   <input type=\"button\" id=\"save_report\" value=\"Save\"/>
		   <div id=\"save_error\"></div>
		   </div>";
	$error = '<tr class="noresults"><td colspan="8">No results fetched for the specified time range/PID.</td></tr>';
        $rs = mysql_query($sql,$dbc2) or dberr();
}?>
<!--break--> 
<div class="report_container">
  <div class="summary" style="background-color:<?echo $bg=($request_type=="gr")?"#000000":"#DDDDDD";?>;color:<?echo $fc=($request_type=="gr")?"#FFFFFF":"#000000";?>">
     <div class="summary_details"><? echo $header; ?></div>       
     <div class="summary_options"><? echo $option; ?></div>
  </div>
  <div class="results">
     <table class="results_table">
     <tr bgcolor="#888888">
       <th>Tier</th><th>Test Case</th><th>Avg</th><th>Max</th><th>Total Hits</th>
       <th>QPS</th><th>Hits > 1 sec</th><th>% hits > 1 sec</th>
     </tr>
  <?php 
     if ($rs) {
         $num = mysql_num_rows($rs);
         if ($num == 0) { 
	    echo $error; 
	 } else {
		while ($row = mysql_fetch_array($rs)) {
                  $total_hits=$row['hits']+$total_hits;
                  $hits_above_second=$row['1sec']+$hits_above_second;
                  $total_qps=$row['qps']+$total_qps;
                  if ($row['tier'] == 1) { $bgcolor='#FFFF99'; } else { $bgcolor='#CCFFCC'; }
		  if ($row['max'] > 1000 || $row['per1sec'] > 0) { $color='#FF0000'; } else { $color='#000000'; }
		?>
		  <tr class="tc_row" bgcolor="<? h($bgcolor);?>" onMouseOver="this.bgColor='#EEEEEE'">
		    <td><? h($row['tier']); ?></td><td><? h($row['tc']); ?></td><td><? h($row['avg']); ?></td>
		    <td style="color:<? h($color); ?>"><? h($row['max']); ?></td><td><? h($row['hits']); ?></td>
		    <td><? h($row['qps']); ?></td><td><? h($row['1sec']); ?></td>
		    <td style="color:<? h($color) ;?>"><? h($row['per1sec']); ?> % </td>
		  </tr>
          <? 	}
		$percentage=round(($hits_above_second*100)/$total_hits,3);	
	  }
    } else { ?>
	<tr class="noresults"><td>MYSQL Error : <? echo mysql_error($dbc) ;?></td></tr>
 <? } ?>
    </table>
  </div>
  <div class="stats">
     <b> Total Hits : </b><? h($total_hits); ?> | 
     <b> Hits > 1sec : </b><? h($hits_above_second); ?> | 
     <b> QPS : </b><? h($total_qps); ?> | 
     <b> Total % > 1 sec : </b><b><font color="red"><? h($percentage); ?>% </font></b>
  </div>
</div>
<?php
} ?>
