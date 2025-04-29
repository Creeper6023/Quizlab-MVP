const API_BASE = '';
async function apiRequest(
  endpoint: string, 
  method: string = 'GET', 
  data: any = null
): Promise<any> {
  const url = `${API_BASE}${endpoint}`;
  
  const options: RequestInit = {
    method,
    headers: {
      'Content-Type': 'application/json',
    },
    credentials: 'include'
  };
  
  if (data && (method === 'POST' || method === 'PUT')) {
    options.body = JSON.stringify(data);
  }
  
  try {
    const response = await fetch(url, options);
    const result = await response.json();
    
    if (!result.success) {
      throw new Error(result.message || 'API request failed');
    }
    
    return result;
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}

export async function getQuiz(quizId: number) {
  const result = await apiRequest(`/ajax.php?action=get_quiz&id=${quizId}`);
  return result.quiz;
}

export async function createQuestion(questionData: any) {
  const result = await apiRequest('/ajax.php?action=create_question', 'POST', questionData);
  return result.question;
}

export async function updateQuestion(questionData: any) {
  const result = await apiRequest('/ajax.php?action=update_question', 'POST', questionData);
  return questionData;
}

export async function deleteQuestion(questionId: number) {
  const result = await apiRequest('/ajax.php?action=delete_question', 'POST', { id: questionId });
  return result;
}

export async function publishQuiz(quizId: number) {
  const result = await apiRequest('/ajax.php?action=publish_quiz', 'POST', { id: quizId });
  return result;
}

export async function unpublishQuiz(quizId: number) {
  const result = await apiRequest('/ajax.php?action=unpublish_quiz', 'POST', { id: quizId });
  return result;
}

export async function startQuiz(quizId: number) {
  const result = await apiRequest(`/ajax.php?action=start_quiz&quiz_id=${quizId}`);
  return result;
}

export async function saveAnswer(answerData: any) {
  const result = await apiRequest('/ajax.php?action=save_answer', 'POST', answerData);
  return result;
}

export async function completeQuiz(attemptId: number) {
  const result = await apiRequest(`/ajax.php?action=complete_quiz&attempt_id=${attemptId}`);
  return result;
}

export async function getQuizResults(attemptId: number) {
  const result = await apiRequest(`/ajax.php?action=get_quiz_results&attempt_id=${attemptId}`);
  return result;
}

export async function getUsers() {
  const result = await apiRequest('/ajax.php?action=get_users');
  return result;
}

export async function createUser(userData: any) {
  const result = await apiRequest('/ajax.php?action=create_user', 'POST', userData);
  return result;
}

export async function getUserData(userId: number) {
  return { id: userId, username: 'user', role: 'student' };
}
