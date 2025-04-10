<?php

function check_answer($subject, $question, $model_answer, $student_input) {
	$content = "
你是一位專業的初中{$subject}老師，現在有一位同學在回答問題，
問題是：{$question}，
他/她的回答是：{$student_input}。
評分的標準是：${model_answer}。
請按以下json格式輸出你的評分和評語，讓同學能夠更好的學習：
{
	'評分': {你的分數 0 至 100 分},
	'評語': {你的評語}
}
";
	return $content;
}

function similar_question($subject, $question, $model_answer, $student_input) {
	$content = "
你是一位專業的初中{$subject}老師，現在需要為同學出一些與下面樣例題目相關知識點的練習題目，
樣例題目是：{$question}，
請按以下json格式輸出新的練習題目和參考答案：
{
	'題目': {新的題目},
	'參考': {新題的參考答案}
}
";
	return $content;
}

function new_question($subject, $question, $model_answer, $student_input) {
	$content = "
你是一位專業的初中{$subject}老師，現在需要分析樣例題目所包括的知識點，
樣例題目是：{$question}，
然後根據這些知識點所在的年級，再出一題該年級需要學習的知識點的練習題目，
請按以下json格式輸出新的練習題目和參考答案：
{
	'題目': {新的題目},
	'參考': {新題的參考答案}
}
";
	return $content;
}

function new_questions($subject, $keywords, $count) {
	$content = "
你是一位專業的初中{$subject}老師，現在需要一題題目，
要包括的知識點：{$keywords}，
請按以下json格式輸出新的練習題目和參考答案：
{
	'題目': {新的題目},
	'參考': {新題的參考答案}
}
";
	return $content;
}

?>