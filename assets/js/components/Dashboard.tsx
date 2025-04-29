import React, { useState, useEffect } from 'react';
import { getUserData } from '../services/api';
import { Card, Loader } from '@ui';

interface User {
  id: number;
  username: string;
  role: string;
}

interface DashboardProps {
  userData: User;
}

const Dashboard: React.FC<DashboardProps> = ({ userData }) => {
  const [stats, setStats] = useState({
    quizzes: 0,
    attempts: 0,
    completedQuizzes: 0,
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadDashboardData = async () => {
      try {
        setLoading(true);


        


        
        setTimeout(() => {
          setStats({
            quizzes: Math.floor(Math.random() * 20),
            attempts: Math.floor(Math.random() * 50),
            completedQuizzes: Math.floor(Math.random() * 15),
          });
          setLoading(false);
        }, 800);
      } catch (err) {
        setError('Failed to load dashboard data');
        setLoading(false);
      }
    };

    loadDashboardData();
  }, [userData]);

  if (loading) {
    return <Loader fullScreen text="Loading dashboard data..." />;
  }

  if (error) {
    return (
      <div className="error-container">
        <Card title="Error" variant="danger">
          {error}
        </Card>
      </div>
    );
  }

  return (
    <div className="dashboard-container">
      <Card title={`Welcome, ${userData.username}!`} subtitle="Here's your overview" className="mb-5">
        <div className="stats-overview" style={{ display: 'flex', gap: '1rem', marginBottom: '1rem' }}>
          <Card 
            variant="primary" 
            bordered 
            className="stat-card"
            style={{ flex: 1, textAlign: 'center', padding: '1rem' }}
          >
            <div className="stat-value" style={{ fontSize: '2rem', fontWeight: 'bold' }}>{stats.quizzes}</div>
            <div className="stat-label">Quizzes</div>
          </Card>
          
          <Card 
            variant="success" 
            bordered 
            className="stat-card"
            style={{ flex: 1, textAlign: 'center', padding: '1rem' }}
          >
            <div className="stat-value" style={{ fontSize: '2rem', fontWeight: 'bold' }}>{stats.attempts}</div>
            <div className="stat-label">Quiz Attempts</div>
          </Card>
          
          <Card 
            variant="info" 
            bordered 
            className="stat-card"
            style={{ flex: 1, textAlign: 'center', padding: '1rem' }}
          >
            <div className="stat-value" style={{ fontSize: '2rem', fontWeight: 'bold' }}>{stats.completedQuizzes}</div>
            <div className="stat-label">Completed Quizzes</div>
          </Card>
        </div>
      </Card>
      
      <Card title="Recent Activity" subtitle="Your latest interactions" className="mt-5">
        <p>Your recent activity will appear here.</p>
      </Card>
    </div>
  );
};

export default Dashboard;
