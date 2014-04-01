<?php
require('../global.php');
echo_header('Web Perf Reports',null,array('webperformance.js'),array('webperformance.css'));?>
<script src="./rgraph/RGraph.common.core.js"></script>
<script src="./rgraph/RGraph.line.js"></script>
<script src="./rgraph/RGraph.common.tooltips.js"></script>
   <div id="main_container">
    <div id="heading"><span id="title">Web Performance Reports</span><a href="..">Go Back <img src="./images/back.png" alt="back" border="0"/></a></div>
    <div id="left_container">
     <div class="container1">
          <h1>Generate Web Performance Reports</h1>
          <p><b>Assumption : </b>Test run for 90 minutes.<br />Results from the last 60 mins of test will be populated</p>
          <p><b>Date Format : </b>YYYY-MM-DD HH:MM:SS</p><br />
          <div id="graph"><input type="image" src="./images/graph_icon.png" id="graph_icon"/></div>
          <div id="container1_b">
            <span><b>Start Time : </b></span><input type="text" name="stime" id="stime" value="<? echo date("Y-m-d H:i:s"); ?>" style="width:135px; text-align:center" />
            <span><b>PID : </b></span><input type="text" name="pid" id="pid" value="1799" style="width:40px; text-align:center" />
            <input type="hidden" name="req" value="gr" />
            <input type="submit" value="Generate Report" id="generate"/>
            <span id="load_spinner"><img src="./images/loading_small.gif" id="load_icon"/></span>
          </div>
     </div>
     <div class="container2"></div>
    </div>
    <div class="perf_report"></div>
   </div>
   <!-- Graph -->
   <div id="graph_popup">
        <p id="heading">Web Performance Trending</p>
        <div id="graph_opts"></div>
        <div id="tc_info"></div>
        <canvas id="graph_popup_in" width="1100" height="450">[No canvas support.Use FF or Chrome]</canvas>
        <div id="span_filter"></div>
   </div>
   <div id="graph_bg"></div>
   <!-- End -->
<?php echo_footer(); ?>
