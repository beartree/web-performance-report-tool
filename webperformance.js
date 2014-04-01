//Webperformance.js

function showLoading() {
  $("#load_spinner").show();
}

function hideLoading() {
  $("#load_spinner").hide();
}

$(document).ready(function(){
  var dataString2 = 'd=1' + '&req=dr' + '&rt=1';
  $.ajax({
         type: "POST",
         url: "./report.php",
         data: dataString2,
         success:function(result) {
		var splitResult=result.split("<!--break-->");
                var result1 = splitResult[0];
                var result2 = splitResult[1];
                $(".container2").html(result1).fadeIn("slow");
		$(".perf_report").html(result2).fadeIn("slow");
         }
  });

  $(".pagination").live("click",function(){
	var class_names = $(this).attr('class').split(' ');
	var s=class_names[1].match(/[0-9]+$/g);
	var p=class_names[2].match(/[0-9]+$/g);
	var dataString = 'rt=1' + '&s=' + s + '&p=' + p +  '&req=dr';
	$.ajax({
          type: "POST",
          url: "./report.php",
          data: dataString,
          success:function(result) {
		var splitResult=result.split("<!--break-->");
                var result1 = splitResult[0];
                var result2 = splitResult[1];
                $(".container2").html(result1).fadeIn("slow");
		$(".perf_report").css({ "display": "none" });
                $(".perf_report").html(result2).fadeIn("slow");
	  }
        });
   });

  $("#generate").click(function(){
	showLoading();
	var stime=$("#stime").val();
	var pid=$("#pid").val();
	var dataString = 'stime=' + stime + '&pid=' + pid + '&req=gr';
 	$.ajax({
               type: "POST",
               url: "./report.php",
               data: dataString,
               success:function(result) {
			$(".perf_report").css({ "display": "none" });
			$(".perf_report").html(result).fadeIn("slow");
			if ($(".noresults").length  != 0) {
 		          $("#save_report").attr('disabled', 'disabled');
        		}
			hideLoading();
	       }
	});
  });

  $(".report_button").live("click",function(){
	var class_names = $(this).attr('class').split(' ');
	var reportid=class_names[1].match(/[0-9]+$/g);
        var dataString = 'rid=' + reportid + '&req=dr';
        $.ajax({
               type: "POST",
               url: "./report.php",
               data: dataString,
               success:function(result) {
			$(".perf_report").css({ "display": "none" });
                        $(".perf_report").html(result).fadeIn("slow");
               }
        });
  });

  $("#save_report").live("click",function(){
	if ($("#rel_name").val().length==0) {
	   $("#save_error").text("Release Name Missing!").fadeIn("slow");
	} else {
	   var relname = $("#rel_name").val();
	   var start_time = $("#start_time").val();
	   var end_time = $("#end_time").val();
           var cobrand_id = $("#cobrand_id").val();
	   var dataString = 'relname=' + relname + '&start_time=' + start_time + '&end_time=' + end_time + '&cobrand_id=' + cobrand_id +'&save_submitted=1';
           $.ajax({
                type: "POST",
                url: "./report.php",
                data: dataString,
                success:function(result) {
		     var pattern = /Release name exists!/g;
		     if (result.match(pattern)) {
			$("#save_error").html(result).fadeIn("slow");
		     } else {
                        var dataString2 = 'd=1' + '&req=dr' + '&rt=1';
                        $.ajax({
                            type: "POST",
                            url: "./report.php",
                            data: dataString2,
                            success:function(result) {
                                    var splitResult=result.split("<!--break-->");
                                    var result1 = splitResult[0];
                                    var result2 = splitResult[1];
                                    $(".container2").html(result1).fadeIn("slow");
                                    $(".perf_report").css({ "display": "none" });
                                    $(".perf_report").html(result2).fadeIn("slow");
                            }
		     
                        });
		    }
                }
          });
	}
  });
	
  $("#rel_name").live("focus",function(){
        $("#save_error").fadeOut("slow");
  });

// Graph js
  var popupStatus = 0;
    function loadPopup(){
      if(popupStatus==0){
        $("#graph_bg").css({"opacity": "0.7"});
        $("#graph_bg").fadeIn("slow");
        $("#graph_popup").fadeIn("slow");
        popupStatus = 1;
      }
    }

    function disablePopup(){
      if(popupStatus==1){
        $("#graph_bg").fadeOut("slow");
        $("#graph_popup").fadeOut("slow");
        popupStatus = 0;
      }
    }

    function centerPopup(height,width){
     //request data for centering
     var windowWidth = document.documentElement.clientWidth;
     var windowHeight = document.documentElement.clientHeight;
     var popupHeight = height;
     var popupWidth = width;
     var windowScroll = document.documentElement.scrollTop;
     //centering
     $("#graph_popup").css({
       "position": "absolute",
       "top": (windowScroll+(windowHeight/2))-popupHeight/2,
       "left": windowWidth/2-popupWidth/2
     });
     //only need force for IE6
     $("#graph_bg").css({"height": windowHeight });
    }
   
    $("#graph_close").click(function(){ disablePopup(); });
    $("#graph_bg").click(function()  { disablePopup(); });
    $("#close_icon").live("click",function()  { disablePopup(); });
    $(document).keypress(function(e){
        if(e.keyCode==27 && popupStatus==1){
                disablePopup();
        }
    });

   function clearCanvas() {
      var elem = $("#graph_popup_in");
      var canvas = elem.get(0);
      var context = canvas.getContext("2d");
      context.clearRect(0, 0, canvas.width, canvas.height);
   }

   function load_graph_contents(result) {
      var splitResult=result.split("<!--break-->");
      var js_data = splitResult[0];
      var html_data1 = splitResult[1];
      var html_data2 = splitResult[2];
      $("body").append(js_data);
      $("#graph_popup_in").css({ "display": "none" ,"height": 450, "width" : 1100});
      $("#graph_popup_in").fadeIn("slow");
      clearCanvas();
      draw_graph();
      $("#graph_opts").html(html_data1);
      $("#span_filter").html(html_data2);
   }

   function ajax_graph(dataString,sub_error) {
     if (sub_error == 'undefined' ) sub_error = 'false';
     $.ajax({
       type: "POST",
       url: "./graph_data.php",
       data: dataString,
       success:function(result) {
         load_graph_contents(result);
         if (sub_error == 'true') $("#sub_error").text('');
       }
     });
    }

    $("#graph").click(function(){
	centerPopup(750,1140);
	loadPopup();
	var dataString = '';
        ajax_graph(dataString,'true');
    });
   
    $(".testcase").live("click",function(){
	var class_names = $(this).attr('class').split(' ');
        var tier_tc = class_names[1].split('_');
	var tier = tier_tc[0];
	var tc = tier_tc[1];
	var dataString = 'tier=' + tier + '&tc=' + tc + '&per1sec=0';
        ajax_graph(dataString);
    });
 
    $('.testcase').live('mouseover mouseout', function(event) {
	var class_names = $(this).attr('class').split(' ');
        var tier_tc = class_names[1];
	var tc = "";
	switch(tier_tc) {
          case "1_1":    tc="masterids | max_prices";break;
	  case "1_2":    tc="masterid | max_prices | offer_limit | other_formats | qlty | spec";break;
	  case "1_3":    tc="isbn | max_prices | offer_limit | other_formats | qlty | spec";break;
	  case "1_4":    tc="isbns | masterids | max_prices";break;
	  case "1_5":    tc="masterid | max_prices | offer_limit | qlty | spec";break;
	  case "2_1":    tc="q | vendor_limit | show_merchants | show_vendors | category_count_sort | max_prices | limit";break;
	  case "2_2":    tc="q | vendor_limit | show_merchants | show_vendors | sort_by | start | category_count_sort | max_prices | limit";break;
	  case "2_3":    tc="q | vendor_limit | show_merchants | show_vendors | hi_p | lo_p | start | category_count_sort | max_prices | limit";break;
	  case "2_4":    tc="q | vendor_limit | show_merchants | show_vendors | retid | start | category_count_sort | max_prices | limit";break;
	  case "2_5":    tc="q | vendor_limit | show_merchants | show_vendors | vendors[] | start | category_count_sort | max_prices | limit";break;
	  case "2_6":    tc="q | vendor_limit | show_merchants | show_vendors | vendor_limit | start | category_count_sort | max_prices | limit";break;
	  case "2_7":    tc="page_id | show_merchants | show_vendors | vendor_limit | category_count_sort | max_prices | limit";break;
	  case "2_8":    tc="page_id | show_merchants | show_vendors | vendor_limit | start | category_count_sort | max_prices | limit";break;
	  case "2_9":    tc="page_id | show_merchants | show_vendors | retid | vendor_limit | start | category_count_sort | max_prices | limit";break;
	  case "2_10":   tc="page_id | show_merchants | show_vendors | vendors[] | vendor_limit | start | category_count_sort | max_prices | limit";break;
	  case "2_11":   tc="page_id | show_merchants | show_vendors | vendor_limit | popup[] | category_count_sort | max_prices | limit";break;
	  case "2_12":   tc="page_id | show_merchants | show_vendors | vendor_limit | sort_by | category_count_sort | max_prices | limit";break;
	  case "2_13":   tc="page_id | show_merchants | show_vendors | vendor_limit | lo_p | hi_p | category_count_sort | max_prices"; break;
          case "2_14":   tc="page_id | show_merchants | show_vendors | start | max_prices | limit"; break;
	  case "2_15":   tc="topcat_id | show_merchants | show_vendors | start | category_count_sort | max_prices | limit"; break;
	  case "2_16":   tc="catezero_id | show_merchants | show_vendors | start |category_count_sort | max_prices | limit"; break;
	  case "3_1":    tc="merchant_info"; break;
	  case "3_2": 	 tc="masterids | max_prices | qlty | offer_limit | no_offers | spec"; break;
          case "3_3":    tc="shop_by_store"; break;
	  case "5_1":    tc="page_ids | loose_match | show_merchants | show_vendors | limit | start | max_prices | category_count_sort | vendor_limit"; break;
	  case "5_2":    tc="catzero_ids | page_ids | loose_match | show_merchants | show_vendors | limit | start | max_prices | category_count_sort | vendor_limit"; break;
	  case "5_3":    tc="catzero_ids | loose_match | show_merchants | show_vendors | limit | start | max_prices | category_count_sort | vendor_limit"; break;
	  case "5_4":    tc="topcat_ids | loose_match | show_merchants | show_vendors | limit | start | max_prices | category_count_sort | vendor_limit"; break;
	  case "5_5":    tc="topcat_ids | catzero_ids | page_ids | loose_match | show_merchants | show_vendors | limit | start | max_prices | category_count_sort | vendor_limit"; break;
          default : 	 tc="";
        }
        if (event.type == 'mouseover') {
       	   $("#tc_info").text(tc).fadeIn("slow");
        } else {
	     $("#tc_info").css({ "display": "none" });
        }
    });


     $(".per1sec").live("click",function(){
	var dataString = 'per1sec=1';
	$("#tc_info").fadeOut("slow");
        ajax_graph(dataString); 
     });
     
     $('.show_sla').live('change', function(){
        if($(this).is(':checked')){
           var dataString = 'show_sla=1'
           ajax_graph(dataString);
        }else {
           var dataString = 'show_sla=0'
           ajax_graph(dataString);
        }
     });
     
     $('.show_all').live('change', function(){
        if($(this).is(':checked')){
           var dataString = 'show_all=1'
           ajax_graph(dataString);
        }else {
           var dataString = 'show_all=0'
           ajax_graph(dataString);
        }
     });

     $(".filter").live("click",function(){
        var class_names = $(this).attr('class').split(' ');
        var filter = class_names[1];
        var dataString = 'filter=' + filter ;
     	ajax_graph(dataString,'true');
     });
   
     $(".span").live("click",function(){
        var class_names = $(this).attr('class').split(' ');
	var span = class_names[1];
	var dataString = 'span=' + span + '&span_type=1' ;
        ajax_graph(dataString);
     });

     $(".span_type").live("click",function(){
        var class_names = $(this).attr('class').split(' ');
        var span_type = class_names[1];
        var dataString = 'span_type=' + span_type ;
        ajax_graph(dataString);
     });

    $("#submit_date").live("click",function(){
	if (!$(".date_from").val() || !$(".date_to").val()) {
	    $("#sub_error").text("Missing Dates").fadeIn("slow");
        } else if($(".date_from").val() == $(".date_to").val()) {
            $("#sub_error").text("From and To cannot be the same").fadeIn("slow");
        } else {
	    var date_from = $(".date_from").val();
	    var date_to = $(".date_to").val();
            var dataString = 'date_sub=1' + '&date_from=' + date_from + '&date_to=' + date_to ;
	    ajax_graph(dataString);
      	}
    });
    
    $(".date").live("focus",function(){
	if ($(this).attr('value')  == "dd-mm-yyyy") { $(this).attr('value','') };
        $("#sub_error").fadeOut("slow");
    });
});
