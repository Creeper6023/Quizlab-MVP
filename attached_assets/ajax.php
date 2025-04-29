<?php

if ($_SERVER["REQUEST_METHOD"] != "POST") exit("not allow");

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/quizlab2/DeepSeek.php');
require_once(__ROOT__.'/quizlab2/prompt.php');


$prompt = "你是一個專業的中文老師";
session_start();
unset($_SESSION['ai']);
if (!isset($_SESSION['ai'])) {
	$_SESSION['ai'] = create_ai();
	$_SESSION['ai']->new_chat($prompt);
}

$method = htmlspecialchars($_POST['method']);
if ($method == "send_message") 
{
	$subject = htmlspecialchars($_POST['subject']);
	$question = htmlspecialchars($_POST['question']);
	$model_answer = htmlspecialchars($_POST['model_answer']);
	$student_input = htmlspecialchars($_POST['student_input']);
	$content = check_answer($subject, $question, $model_answer, $student_input);
	echo $_SESSION['ai']->send_message($content);
} 
else if ($method == "similar_question") 
{
	$subject = htmlspecialchars($_POST['subject']);
	$question = htmlspecialchars($_POST['question']);
	$model_answer = htmlspecialchars($_POST['model_answer']);
	$student_input = htmlspecialchars($_POST['student_input']);
	$content = similar_question($subject, $question, $model_answer, $student_input);
	echo $_SESSION['ai']->send_message($content);
} 
else if ($method == "new_question") 
{
	$subject = htmlspecialchars($_POST['subject']);
	$question = htmlspecialchars($_POST['question']);
	$model_answer = htmlspecialchars($_POST['model_answer']);
	$student_input = htmlspecialchars($_POST['student_input']);
	$content = new_question($subject, $question, $model_answer, $student_input);
	echo $_SESSION['ai']->send_message($content);
} 
else if ($method == "new_questions")
{
	$subject = htmlspecialchars($_POST['subject']);
	$keywords = htmlspecialchars($_POST['keywords']);
	$count = htmlspecialchars($_POST['count']);
	$content = new_questions($subject, $keywords, $count);

	echo $_SESSION['ai']->send_message($content);
}
else if ($method == "new_chat") 
{
	$_SESSION['ai']->new_chat($prompt);
	echo "OK";
} 
else 
{
	echo "no method $method";
}

?>