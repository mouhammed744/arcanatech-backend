import { useState, useCallback, useEffect } from 'react';
import { api } from '~/lib/api';

export interface User {
  id: number;
  name?: string;          // legacy — not returned by /auth/me (use firstName/lastName)
  firstName?: string;
  lastName?: string;
  email: string;
  role: 'admin' | 'teacher' | 'student';
  universityId?: number;
  university_id?: number; // fallback
  phone?: string;
  address?: string;
  isActive?: boolean;
  is_active?: boolean;    // fallback
  // Student-specific (only present for student role)
  student?: {
    studentId: number;
    registrationNumber: string;
    level: string;
    filiere: { id: number; name: string; code: string } | null;
    enrollmentYear: number;
  } | null;
}

export interface AuthState {
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  error: string | null;
  fieldErrors?: Record<string, string[]> | null;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

/**
 * useAuth Hook
 * 
 * Gère l'authentification globale de l'application
 * - Login / Logout
 * - Persistance des tokens (localStorage)
 * - Auto-renouvellement du token
 * - Gestion des erreurs
 */
export function useAuth() {
  const [state, setState] = useState<AuthState>({
    user: null,
    isLoading: true,
    isAuthenticated: false,
    error: null,
    fieldErrors: null,
  });

  // Vérifier l'authentification au chargement
  useEffect(() => {
    const initAuth = async () => {
      try {
        // Vérifier si on a un token stocké
        const user = api.getUser();
        if (user && api.isAuthenticated()) {
          // Vérifier que le token est toujours valide
          const me = await api.getMe();
          // Persist universityId in localStorage so api.getUniversityId() always works
          const uniId = me.universityId ?? me.university_id;
          if (uniId != null) localStorage.setItem('university_id', String(uniId));
          setState({
            user: me,
            isLoading: false,
            isAuthenticated: true,
            error: null,
          });
        } else {
          setState({
            user: null,
            isLoading: false,
            isAuthenticated: false,
            error: null,
          });
        }
      } catch (error) {
        // Token invalide, déconnecter
        setState({
          user: null,
          isLoading: false,
          isAuthenticated: false,
          error: null,
        });
      }
    };

    initAuth();
  }, []);

  const login = useCallback(async (credentials: LoginCredentials): Promise<User> => {
    setState((prev) => ({ ...prev, isLoading: true, error: null }));

    try {
      const response = await api.login(credentials.email, credentials.password);

      // Fetch full user profile (includes student.studentId, filiere, etc.)
      // so that the student dashboard works immediately without a page reload.
      let user: any;
      try {
        user = await api.getMe();
        const uniId = user.universityId ?? user.university_id;
        if (uniId != null) localStorage.setItem('university_id', String(uniId));
      } catch {
        // Fallback to the user returned by the login endpoint
        user = response.user || api.getUser();
      }

      setState({
        user,
        isLoading: false,
        isAuthenticated: true,
        error: null,
        fieldErrors: null,
      });

      return user;
    } catch (error: any) {
      let errorMessage = 'Login failed';

      if (error) {
        if (typeof error === 'string') {
          errorMessage = error;
        } else if (error.message) {
          errorMessage = String(error.message);
        }

        // If validation errors are provided (Laravel style), join them into a readable string
        if (error.errors && typeof error.errors === 'object') {
          try {
            const parts = Object.values(error.errors).flat().map((p: any) => String(p));
            if (parts.length) {
              errorMessage = parts.join(' ');
            }
          } catch (e) {
            // ignore and keep previous message
          }
        } else if ((error as any).error) {
          errorMessage = String((error as any).error);
        }
      }

      setState({
        user: null,
        isLoading: false,
        isAuthenticated: false,
        error: errorMessage,
        fieldErrors: error && (error.errors || null),
      });

      throw error;
    }
  }, []);

  const logout = useCallback(async () => {
    setState((prev) => ({ ...prev, isLoading: true }));

    try {
      await api.logout();
      setState({
        user: null,
        isLoading: false,
        isAuthenticated: false,
        error: null,
      });
    } catch (error: any) {
      let errorMessage = 'Logout failed';
      if (error) {
        if (typeof error === 'string') errorMessage = error;
        else if (error.message) errorMessage = String(error.message);
        else if ((error as any).error) errorMessage = String((error as any).error);
      }

      setState({
        user: null,
        isLoading: false,
        isAuthenticated: false,
        error: errorMessage,
      });

      throw error;
    }
  }, []);

  return {
    ...state,
    login,
    logout,
  };
}
