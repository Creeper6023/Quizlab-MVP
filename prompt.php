<?php
/**
 * This file contains functions to generate prompts for the DeepSeek API.
 */

/**
 * Generate a prompt to check a student's answer against a model answer
 */
function check_answer($subject, $question, $model_answer, $student_answer) {
    return <<<EOT
You are a highly qualified teacher in $subject with an expert knowledge of assessment and grading.

Please compare the student's answer to the model answer for the following question. Calculate a numerical score from 0-100 based on how well the student's answer matches the required response.

Question:
```
$question
```

Model Answer:
```
$model_answer
```

Student Answer:
```
$student_answer
```

Provide assessment as JSON with these fields:
{
  "score": 0-100,
  "feedback": "brief feedback with strengths/improvements",
  "key_points_addressed": ["key points covered"],
  "key_points_missed": ["key points missing"],
  "improvement_suggestions": "specific improvement tips"
}
Respond with ONLY the JSON.
EOT;
}

/**
 * Generate a prompt to create a similar question
 */
function similar_question($subject, $original_question, $model_answer, $difficulty_level = 'medium') {
    return <<<EOT
You are a highly qualified teacher in $subject with expertise in creating educational assessments.

Based on the following original question and its model answer, please create a similar but distinct question of $difficulty_level difficulty.

Original Question:
```
$original_question
```

Original Model Answer:
```
$model_answer
```

Please provide your new question and its model answer in the following JSON format:
```json
{
  "new_question": "<the new similar question>",
  "new_model_answer": "<detailed model answer for the new question>",
  "skills_assessed": [<list of skills or knowledge being tested>],
  "difficulty_level": "$difficulty_level",
  "estimated_time": "<estimated time in minutes to answer the question>"
}
```

Make sure the new question tests similar concepts but is not identical to the original question. Your response should contain ONLY the JSON object, nothing else.
EOT;
}

/**
 * Generate a prompt to create a completely new question related to a subject
 */
function new_question($subject, $topic = '', $difficulty_level = 'medium', $question_type = 'open-ended') {
    return <<<EOT
You are a highly qualified teacher in $subject with expertise in creating educational assessments.

Please create a new, engaging $question_type question about $topic with $difficulty_level difficulty level.

Please provide your new question and its model answer in the following JSON format:
```json
{
  "question": "<the new question>",
  "model_answer": "<detailed model answer>",
  "topic": "$topic",
  "skills_assessed": [<list of skills or knowledge being tested>],
  "difficulty_level": "$difficulty_level",
  "estimated_time": "<estimated time in minutes to answer the question>"
}
```

Make sure the question is appropriate for educational purposes, clear in its expectations, and the model answer is comprehensive. Your response should contain ONLY the JSON object, nothing else.
EOT;
}

/**
 * Generate a prompt to create multiple new questions related to a subject
 */
function new_questions($subject, $keywords = '', $count = 3) {
    return <<<EOT
You are a highly qualified teacher in $subject with expertise in creating educational assessments.

Please create $count new, engaging questions related to the following keywords: $keywords. 
The questions should be diverse in difficulty and type.

Please provide your new questions and their model answers in the following JSON format:
```json
{
  "questions": [
    {
      "question": "<question 1>",
      "model_answer": "<detailed model answer 1>",
      "difficulty_level": "<easy, medium, or hard>",
      "question_type": "<type of question: open-ended, multiple-choice, etc.>"
    },
    {
      "question": "<question 2>",
      "model_answer": "<detailed model answer 2>",
      "difficulty_level": "<easy, medium, or hard>",
      "question_type": "<type of question: open-ended, multiple-choice, etc.>"
    }
    // Additional questions as needed to reach the requested count
  ]
}
```

Make sure the questions are appropriate for educational purposes, clear in their expectations, and the model answers are comprehensive. Your response should contain ONLY the JSON object, nothing else.
EOT;
}
?>