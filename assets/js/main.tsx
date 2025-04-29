import React from 'react';
import { createRoot } from 'react-dom/client';
import Dashboard from './components/Dashboard';
import QuizCreator from './components/QuizCreator';
import QuizTaker from './components/QuizTaker';
import AdminPanel from './components/AdminPanel';


const userData = (window as any).userData || null;


document.addEventListener('DOMContentLoaded', () => {

  const adminAppElement = document.getElementById('admin-app');
  if (adminAppElement && userData && userData.role === 'admin') {
    const root = createRoot(adminAppElement);
    root.render(<AdminPanel userData={userData} />);
  }


  const quizEditorAppElement = document.getElementById('quiz-editor-app');
  if (quizEditorAppElement && userData && userData.role === 'teacher') {
    const root = createRoot(quizEditorAppElement);
    root.render(<QuizCreator userData={userData} />);
  }


  const quizTakerAppElement = document.getElementById('quiz-taker-app');
  if (quizTakerAppElement && userData && userData.role === 'student') {
    const root = createRoot(quizTakerAppElement);
    root.render(<QuizTaker userData={userData} />);
  }


  const dashboardElement = document.getElementById('dashboard-app');
  if (dashboardElement && userData) {
    const root = createRoot(dashboardElement);
    root.render(<Dashboard userData={userData} />);
  }
});
