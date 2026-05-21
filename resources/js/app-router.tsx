import { ReactNode } from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuthContext } from '~/contexts/AuthContext';
import LoginPage from '~/pages/LoginPage';
import RegisterPage from '~/pages/RegisterPage';
import StudentDashboard from '~/pages/StudentDashboard';
import TeacherDashboard from '~/pages/TeacherDashboard';
import AdminDashboard from '~/pages/AdminDashboard';

interface ProtectedRouteProps {
  element: ReactNode;
  requiredRole?: 'admin' | 'teacher' | 'student';
}

function ProtectedRoute({ element, requiredRole }: ProtectedRouteProps) {
  const { isAuthenticated, user, isLoading } = useAuthContext();

  if (isLoading) {
    return <div className="flex items-center justify-center min-h-screen">Loading...</div>;
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  if (requiredRole && user?.role !== requiredRole) {
    return <Navigate to="/login" replace />;
  }

  return element;
}

export default function AppRouter() {
  return (
    <Router>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />
          <Route path="/student/dashboard" element={<ProtectedRoute element={<StudentDashboard />} requiredRole="student" />} />
          <Route path="/teacher/dashboard" element={<ProtectedRoute element={<TeacherDashboard />} requiredRole="teacher" />} />
          <Route path="/admin/dashboard" element={<ProtectedRoute element={<AdminDashboard />} requiredRole="admin" />} />
          <Route path="/" element={<Navigate to="/login" replace />} />
          <Route path="*" element={<Navigate to="/login" replace />} />
        </Routes>
      </AuthProvider>
    </Router>
  );
}
