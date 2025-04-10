<!DOCTYPE html>
<html>
<head>
<style>
input { width: 100%; }
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
$(document).ready(function(){
	var c1 = 0, c2 = 0;
	$("button").click(function(){
		$("button").prop("disabled", true);
		c1 = c2 = parseInt($("#txt_count").val());
		for (var i = 0; i < c1; i++) {
			$.post("ajax.php",
			{
				method: $(this).val(),
				subject: $("#txt_subject").val(),
				keywords: $("#txt_keywords").val(),
				count: $("#txt_count").val()
			},
			function(data, status){
				data = data.replace("```json", "");
				data = data.replace("```", "");
				
				c2 = c2 - 1;
				if (c2 == 0) {
					$("button").prop("disabled", false);
				}
				
				try {
					json = JSON.parse(data);
					$("#messages").append("<p><div>題目：" + json["題目"] + "</div></p>");
					$("#messages").append("<p><div>參考：" + JSON.stringify(json["參考"]) + "</div></p><hr />");
				} catch (e) {
					$("#messages").append("<p>" + data + "</p><hr />");
				}
			});
		}
	});
});
</script>
</head>
<body>
<div id="messages"></div>
<div><input type="text" id="txt_subject" value="科學" /></div>
<div><input type="text" id="txt_keywords" value="初中、電力學" /></div>
<div><input type="text" id="txt_count" value="3" /></div>
<div>
	<button id="btn_new" value="new_questions">新的題目</button></div>
</body>
</html>