<!DOCTYPE html>
<html>
<head>
<style>
input { width: 100%; }
</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
$(document).ready(function(){
	$("button").click(function(){
		$("button").prop("disabled", true);
		$.post("ajax.php",
		{
			method: $(this).val(),
			subject: $("#txt_subject").val(),
			question: $("#txt_question").val(),
			model_answer: $("#txt_model_answer").val(),
			student_input: $("#txt_student_input").val(),
		},
		function(data, status){
			data = data.replace("```json", "");
			data = data.replace("```", "");
			
			$("#messages").append("<p>" + data + "</p>");
			$("button").prop("disabled", false);
			
			alert(data);
			json = JSON.parse(data);
			$("#txt_question").val(json["題目"]);
			$("#txt_model_answer").val(json["參考"]);
		});
	});
});
</script>
</head>
<body>
<div id="messages"></div>
<div><input type="text" id="txt_subject" value="科學" /></div>
<div><input type="text" id="txt_question" value="乒乓球放在熱水裏會回復原狀的原理是甚麼？" /></div>
<div><input type="text" id="txt_model_answer" value="把乒乓球放入熱水中，乒乓球內的空氣受熱膨脹，凹陷的部份被推起。" /></div>
<div><input type="text" id="txt_student_input" /></div>
<div>
	<button id="btn_check" value="send_message">檢查答案</button>
	<button id="btn_similar" value="similar_question">相關題目</button>
	<button id="btn_new" value="new_question">新的題目</button></div>
</body>
</html>