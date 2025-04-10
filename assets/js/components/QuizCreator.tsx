import React, { useState, useEffect } from 'react';
import { createQuestion, updateQuestion, deleteQuestion, getQuiz } from '../services/api';

interface Question {
  id: number;
  quiz_id: number;
  question_text: string;
  model_answer: string;
  points: number;
}

interface Quiz {
  id: number;
  title: string;
  description: string;
  is_published: boolean;
  questions: Question[];
}

interface QuizCreatorProps {
  userData: {
    id: number;
    username: string;
    role: string;
  };
}

const QuizCreator: React.FC<QuizCreatorProps> = ({ userData }) => {
  const [activeQuiz, setActiveQuiz] = useState<Quiz | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  // Form state for adding/editing questions
  const [editMode, setEditMode] = useState(false);
  const [currentQuestion, setCurrentQuestion] = useState<Question | null>(null);
  const [questionText, setQuestionText] = useState('');
  const [modelAnswer, setModelAnswer] = useState('');
  const [points, setPoints] = useState(10);

  // Get the quiz ID from URL if available
  const getQuizIdFromUrl = (): number | null => {
    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');
    return id ? parseInt(id) : null;
  };

  const quizId = getQuizIdFromUrl();

  useEffect(() => {
    if (quizId) {
      loadQuiz(quizId);
    }
  }, [quizId]);

  const loadQuiz = async (id: number) => {
    try {
      setLoading(true);
      const quiz = await getQuiz(id);
      setActiveQuiz(quiz);
      setLoading(false);
    } catch (err) {
      setError('Failed to load quiz data');
      setLoading(false);
    }
  };

  const handleAddQuestion = () => {
    setEditMode(false);
    setCurrentQuestion(null);
    setQuestionText('');
    setModelAnswer('');
    setPoints(10);
  };

  const handleEditQuestion = (question: Question) => {
    setEditMode(true);
    setCurrentQuestion(question);
    setQuestionText(question.question_text);
    setModelAnswer(question.model_answer);
    setPoints(question.points);
  };

  const handleDeleteQuestion = async (questionId: number) => {
    if (!window.confirm('Are you sure you want to delete this question?')) {
      return;
    }

    try {
      await deleteQuestion(questionId);
      
      // Update the active quiz by removing the deleted question
      if (activeQuiz) {
        setActiveQuiz({
          ...activeQuiz,
          questions: activeQuiz.questions.filter(q => q.id !== questionId)
        });
      }
      
      setSuccessMessage('Question deleted successfully');
      
      // Clear success message after 3 seconds
      setTimeout(() => {
        setSuccessMessage(null);
      }, 3000);
    } catch (err) {
      setError('Failed to delete question');
    }
  };

  const handleSubmitQuestion = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!activeQuiz) return;
    
    try {
      if (editMode && currentQuestion) {
        // Update existing question
        const updatedQuestion = await updateQuestion({
          id: currentQuestion.id,
          quiz_id: activeQuiz.id,
          question_text: questionText,
          model_answer: modelAnswer,
          points
        });
        
        // Update the question in the active quiz
        setActiveQuiz({
          ...activeQuiz,
          questions: activeQuiz.questions.map(q => 
            q.id === currentQuestion.id ? updatedQuestion : q
          )
        });
        
        setSuccessMessage('Question updated successfully');
      } else {
        // Create new question
        const newQuestion = await createQuestion({
          quiz_id: activeQuiz.id,
          question_text: questionText,
          model_answer: modelAnswer,
          points
        });
        
        // Add the new question to the active quiz
        setActiveQuiz({
          ...activeQuiz,
          questions: [...activeQuiz.questions, newQuestion]
        });
        
        setSuccessMessage('Question added successfully');
      }
      
      // Reset form
      setQuestionText('');
      setModelAnswer('');
      setPoints(10);
      setEditMode(false);
      setCurrentQuestion(null);
      
      // Clear success message after 3 seconds
      setTimeout(() => {
        setSuccessMessage(null);
      }, 3000);
    } catch (err) {
      setError('Failed to save question');
    }
  };

  if (loading) {
    return <div className="loading">Loading quiz editor...</div>;
  }

  if (!quizId) {
    return (
      <div className="quiz-creator">
        <p>Select a quiz to edit or create a new one.</p>
      </div>
    );
  }

  if (!activeQuiz) {
    return (
      <div className="quiz-creator">
        <p>Quiz not found or you don't have permission to edit it.</p>
      </div>
    );
  }

  return (
    <div className="quiz-creator">
      <div className="quiz-editor-header">
        <h3>
          Editing Quiz: {activeQuiz.title}
          <span className={`quiz-status ${activeQuiz.is_published ? 'published' : 'draft'}`}>
            {activeQuiz.is_published ? 'Published' : 'Draft'}
          </span>
        </h3>
        {activeQuiz.description && <p>{activeQuiz.description}</p>}
      </div>
      
      {error && <div className="alert alert-danger">{error}</div>}
      {successMessage && <div className="alert alert-success">{successMessage}</div>}
      
      <div className="questions-section">
        <div className="section-header">
          <h4>Questions</h4>
          <button className="btn btn-sm btn-primary" onClick={handleAddQuestion}>
            <i className="fas fa-plus"></i> Add Question
          </button>
        </div>
        
        <div className="question-form-container">
          {(questionText || modelAnswer || editMode) && (
            <form onSubmit={handleSubmitQuestion} className="question-form">
              <h5>{editMode ? 'Edit Question' : 'Add New Question'}</h5>
              
              <div className="form-group">
                <label htmlFor="questionText">Question</label>
                <textarea 
                  id="questionText"
                  value={questionText}
                  onChange={(e) => setQuestionText(e.target.value)}
                  rows={3}
                  required
                ></textarea>
              </div>
              
              <div className="form-group">
                <label htmlFor="modelAnswer">Model Answer</label>
                <textarea 
                  id="modelAnswer"
                  value={modelAnswer}
                  onChange={(e) => setModelAnswer(e.target.value)}
                  rows={5}
                  required
                ></textarea>
                <small className="form-text text-muted">
                  This is the ideal answer that student responses will be compared against for grading.
                </small>
              </div>
              
              <div className="form-group">
                <label htmlFor="points">Points</label>
                <input 
                  type="number" 
                  id="points"
                  min="1" 
                  max="100"
                  value={points}
                  onChange={(e) => setPoints(parseInt(e.target.value))}
                  required
                />
              </div>
              
              <div className="form-actions">
                <button type="submit" className="btn btn-primary">
                  {editMode ? 'Update Question' : 'Add Question'}
                </button>
                <button 
                  type="button" 
                  className="btn btn-outline"
                  onClick={() => {
                    setQuestionText('');
                    setModelAnswer('');
                    setPoints(10);
                    setEditMode(false);
                    setCurrentQuestion(null);
                  }}
                >
                  Cancel
                </button>
              </div>
            </form>
          )}
        </div>
        
        {activeQuiz.questions.length > 0 ? (
          <div className="questions-list">
            {activeQuiz.questions.map((question, index) => (
              <div key={question.id} className="question-item">
                <div className="question-item-header">
                  <div className="question-number">Question {index + 1}</div>
                  <div className="question-points">{question.points} points</div>
                </div>
                <div className="question-item-body">
                  <p className="question-text">{question.question_text}</p>
                  <div className="model-answer">
                    <strong>Model Answer:</strong>
                    <p>{question.model_answer}</p>
                  </div>
                </div>
                <div className="question-item-actions">
                  <button 
                    className="btn btn-sm btn-outline"
                    onClick={() => handleEditQuestion(question)}
                  >
                    <i className="fas fa-edit"></i> Edit
                  </button>
                  <button 
                    className="btn btn-sm btn-danger"
                    onClick={() => handleDeleteQuestion(question.id)}
                  >
                    <i className="fas fa-trash"></i> Delete
                  </button>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="empty-state">
            <i className="fas fa-question-circle"></i>
            <p>This quiz doesn't have any questions yet.</p>
            <p>Use the "Add Question" button to create your first question.</p>
          </div>
        )}
      </div>
    </div>
  );
};

export default QuizCreator;
