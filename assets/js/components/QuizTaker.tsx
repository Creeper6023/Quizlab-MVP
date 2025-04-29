import React, { useState, useEffect } from 'react';
import { startQuiz, saveAnswer, completeQuiz, getQuizResults } from '../services/api';

interface Question {
  id: number;
  question_text: string;
  answer_text?: string;
  score?: number;
  feedback?: string;
}

interface Quiz {
  id: number;
  title: string;
  description: string;
}

interface QuizAttempt {
  id: number;
  quiz_id: number;
  status: string;
  total_score: number;
}

interface QuizTakerProps {
  userData: {
    id: number;
    username: string;
    role: string;
  };
}

const QuizTaker: React.FC<QuizTakerProps> = ({ userData }) => {
  const [activeQuiz, setActiveQuiz] = useState<Quiz | null>(null);
  const [questions, setQuestions] = useState<Question[]>([]);
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [currentAnswer, setCurrentAnswer] = useState('');
  const [attemptId, setAttemptId] = useState<number | null>(null);
  const [quizCompleted, setQuizCompleted] = useState(false);
  const [quizResults, setQuizResults] = useState<any>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);


  const getParamsFromUrl = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const quizId = urlParams.get('id');
    const attemptId = urlParams.get('attempt_id');
    return {
      quizId: quizId ? parseInt(quizId) : null,
      attemptId: attemptId ? parseInt(attemptId) : null
    };
  };

  const { quizId, attemptId: urlAttemptId } = getParamsFromUrl();

  useEffect(() => {
    if (urlAttemptId) {

      loadExistingAttempt(urlAttemptId);
    } else if (quizId) {

      startNewQuiz(quizId);
    }
  }, [quizId, urlAttemptId]);

  const startNewQuiz = async (quizId: number) => {
    try {
      setLoading(true);
      const result = await startQuiz(quizId);
      
      setActiveQuiz(result.quiz);
      setQuestions(result.questions);
      setAttemptId(result.attempt_id);
      setCurrentQuestionIndex(0);
      setCurrentAnswer('');
      setQuizCompleted(false);
      setQuizResults(null);
      setLoading(false);
    } catch (err) {
      setError('Failed to start quiz');
      setLoading(false);
    }
  };

  const loadExistingAttempt = async (attemptId: number) => {
    try {
      setLoading(true);


      setAttemptId(attemptId);

      setLoading(false);
    } catch (err) {
      setError('Failed to load quiz attempt');
      setLoading(false);
    }
  };

  const handleSaveAnswer = async () => {
    if (!attemptId || currentQuestionIndex >= questions.length) return;
    
    const currentQuestion = questions[currentQuestionIndex];
    
    try {
      await saveAnswer({
        attempt_id: attemptId,
        question_id: currentQuestion.id,
        answer_text: currentAnswer
      });
      

      const updatedQuestions = [...questions];
      updatedQuestions[currentQuestionIndex] = {
        ...currentQuestion,
        answer_text: currentAnswer
      };
      setQuestions(updatedQuestions);
      
      setSuccessMessage('Answer saved');
      

      setTimeout(() => {
        setSuccessMessage(null);
      }, 2000);
    } catch (err) {
      setError('Failed to save answer');
    }
  };

  const handleNextQuestion = async () => {

    await handleSaveAnswer();
    
    if (currentQuestionIndex < questions.length - 1) {

      setCurrentQuestionIndex(currentQuestionIndex + 1);

      const nextQuestion = questions[currentQuestionIndex + 1];
      setCurrentAnswer(nextQuestion.answer_text || '');
    }
  };

  const handlePreviousQuestion = () => {
    if (currentQuestionIndex > 0) {

      setCurrentQuestionIndex(currentQuestionIndex - 1);

      const prevQuestion = questions[currentQuestionIndex - 1];
      setCurrentAnswer(prevQuestion.answer_text || '');
    }
  };

  const handleCompleteQuiz = async () => {
    if (!attemptId) return;
    

    await handleSaveAnswer();
    
    if (!window.confirm('Are you sure you want to complete this quiz? This action cannot be undone.')) {
      return;
    }
    
    try {
      setLoading(true);
      const result = await completeQuiz(attemptId);
      
      setQuizCompleted(true);
      setQuizResults(result);
      setLoading(false);
      

      window.location.href = `/student/view_results.php?attempt_id=${attemptId}`;
    } catch (err) {
      setError('Failed to complete quiz');
      setLoading(false);
    }
  };


  useEffect(() => {
    if (questions.length > 0 && currentQuestionIndex < questions.length) {
      const question = questions[currentQuestionIndex];
      setCurrentAnswer(question.answer_text || '');
    }
  }, [currentQuestionIndex, questions]);

  if (loading) {
    return <div className="loading">Loading quiz...</div>;
  }

  if (error) {
    return <div className="alert alert-danger">{error}</div>;
  }

  if (!activeQuiz && !attemptId) {
    return (
      <div className="quiz-taker">
        <p>No quiz selected. Please choose a quiz from the list.</p>
      </div>
    );
  }

  if (quizCompleted) {
    return (
      <div className="quiz-taker">
        <div className="quiz-completed">
          <h3>Quiz Completed!</h3>
          <p>Your quiz has been submitted and is being graded.</p>
          {quizResults && (
            <div className="quiz-score">
              <h4>Your Score: {quizResults.score.toFixed(2)}%</h4>
            </div>
          )}
          <a href="/student" className="btn btn-primary">Return to Dashboard</a>
        </div>
      </div>
    );
  }

  return (
    <div className="quiz-taker">
      {activeQuiz && (
        <div className="quiz-header">
          <h3>{activeQuiz.title}</h3>
          {activeQuiz.description && <p>{activeQuiz.description}</p>}
          <div className="quiz-progress">
            Question {currentQuestionIndex + 1} of {questions.length}
          </div>
        </div>
      )}
      
      {successMessage && <div className="alert alert-success">{successMessage}</div>}
      
      <div className="quiz-body">
        {questions.length > 0 && (
          <div className="question-container">
            <div className="question-text">
              {questions[currentQuestionIndex].question_text}
            </div>
            
            <div className="answer-input">
              <textarea
                rows={6}
                value={currentAnswer}
                onChange={(e) => setCurrentAnswer(e.target.value)}
                placeholder="Type your answer here..."
              ></textarea>
            </div>
            
            <div className="answer-actions">
              <button 
                className="btn btn-primary"
                onClick={handleSaveAnswer}
              >
                Save Answer
              </button>
            </div>
          </div>
        )}
      </div>
      
      <div className="quiz-navigation">
        <button 
          className="btn btn-outline"
          onClick={handlePreviousQuestion}
          disabled={currentQuestionIndex === 0}
        >
          <i className="fas fa-arrow-left"></i> Previous
        </button>
        
        {currentQuestionIndex < questions.length - 1 ? (
          <button 
            className="btn btn-primary"
            onClick={handleNextQuestion}
          >
            Next <i className="fas fa-arrow-right"></i>
          </button>
        ) : (
          <button 
            className="btn btn-success"
            onClick={handleCompleteQuiz}
          >
            Complete Quiz <i className="fas fa-check"></i>
          </button>
        )}
      </div>
    </div>
  );
};

export default QuizTaker;
