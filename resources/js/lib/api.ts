/**
 * API Client Service
 * 
 * Gère tous les appels API au backend Laravel
 * - Authentication (JWT)
 * - Gestion des tokens (localStorage)
 * - Intercepteurs pour Bearer token
 * - Gestion des erreurs
 */

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';

export interface ApiErrorResponse {
  error: string;
  message: string;
  errors?: Record<string, string[]>;
}

export interface ApiResponse<T = any> {
  data?: T;
  message?: string;
  pagination?: {
    total: number;
    limit: number;
    offset: number;
    pages: number;
  };
  [key: string]: any;
}

/**
 * Classe API - Gère tous les appels au backend
 * 
 * Stockage des tokens :
 * - localStorage['access_token'] - JWT access token (15 min)
 * - localStorage['refresh_token'] - JWT refresh token (7 jours)
 * - localStorage['user_id'] - ID de l'utilisateur connecté
 * - localStorage['university_id'] - ID de l'université
 */
class ApiClient {
  private baseUrl: string;
  private accessToken: string | null;
  private refreshToken: string | null;

  constructor(baseUrl: string = API_BASE_URL) {
    this.baseUrl = baseUrl;
    this.accessToken = this.getStoredToken('access_token');
    this.refreshToken = this.getStoredToken('refresh_token');
  }

  /**
   * Récupérer les headers pour les requêtes API
   */
  private getHeaders(contentType: string = 'application/json'): HeadersInit {
    const headers: HeadersInit = {
      'Content-Type': contentType,
      'Accept': 'application/json',
    };

    // Ajouter le Bearer token si connecté
    if (this.accessToken) {
      headers['Authorization'] = `Bearer ${this.accessToken}`;
    }

    return headers;
  }

  /**
   * Stocker un token en localStorage
   */
  private setToken(key: string, value: string): void {
    localStorage.setItem(key, value);
    if (key === 'access_token') {
      this.accessToken = value;
    }
    if (key === 'refresh_token') {
      this.refreshToken = value;
    }
  }

  /**
   * Récupérer un token depuis localStorage
   */
  private getStoredToken(key: string): string | null {
    const value = localStorage.getItem(key);
    // Reject invalid values that could have been stored by a previous buggy version
    if (!value || value === 'undefined' || value === 'null') return null;
    return value;
  }

  /**
   * Supprimer un token
   */
  private removeToken(key: string): void {
    localStorage.removeItem(key);
    if (key === 'access_token') {
      this.accessToken = null;
    }
    if (key === 'refresh_token') {
      this.refreshToken = null;
    }
  }

  /**
   * Gestion des erreurs API
   */
  private async handleResponse<T>(response: Response): Promise<T> {
    const data = await response.json();

    if (!response.ok) {
      // Si token expiré et on a un refresh token, renouveller
      if (response.status === 401 && this.refreshToken) {
        const refreshed = await this.refreshAccessToken();
        if (refreshed) {
          // Relancer la requête avec le nouveau token
          return this.get<T>(response.url?.split('/api')[1] || '');
        }
      }

      throw {
        status: response.status,
        statusText: response.statusText,
        error: data.error || 'Unknown error',
        message: data.message || response.statusText,
        errors: data.errors,
      } as ApiErrorResponse;
    }

    return data;
  }

  /**
   * ========== AUTHENTIFICATION ==========
   */

  /**
   * POST /api/auth/login
   * 
   * Connecter un utilisateur avec email/password
   */
  async login(
    email: string,
    password: string
  ): Promise<any> {
    const response = await fetch(`${this.baseUrl}/auth/login`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({ email, password }),
    });

    const data = await this.handleResponse<any>(response);

    // Backend returns tokens in nested 'tokens' object with camelCase keys.
    // Support both shapes for robustness.
    const accessToken  = data.tokens?.accessToken  ?? data.access_token;
    const refreshToken = data.tokens?.refreshToken ?? data.refresh_token;

    if (accessToken)  this.setToken('access_token',  accessToken);
    if (refreshToken) this.setToken('refresh_token', refreshToken);

    // Stocker les infos user
    if (data.user) {
      localStorage.setItem('user', JSON.stringify(data.user));
      localStorage.setItem('user_id', String(data.user.id));
      // Backend uses camelCase 'universityId'; fallback to snake_case for older payloads.
      const uniId = data.user.universityId ?? data.user.university_id;
      if (uniId != null) localStorage.setItem('university_id', String(uniId));
    }

    return data;
  }

  /**
   * POST /api/auth/refresh
   * 
   * Renouveler le access token via le refresh token
   */
  async refreshAccessToken(): Promise<boolean> {
    if (!this.refreshToken) {
      return false;
    }

    try {
      const response = await fetch(`${this.baseUrl}/auth/refresh`, {
        method: 'POST',
        headers: this.getHeaders(),
        // Backend expects camelCase 'refreshToken'; also send snake_case for compatibility
        body: JSON.stringify({ refreshToken: this.refreshToken, refresh_token: this.refreshToken }),
      });

      const data = await this.handleResponse<any>(response);
      const newAccessToken = data.tokens?.accessToken ?? data.access_token;
      if (newAccessToken) this.setToken('access_token', newAccessToken);
      return true;
    } catch (error) {
      // Refresh failed, need to re-login
      this.logout();
      return false;
    }
  }

  /**
   * POST /api/auth/logout
   * 
   * Déconnecter l'utilisateur
   */
  async logout(): Promise<void> {
    try {
      await fetch(`${this.baseUrl}/auth/logout`, {
        method: 'POST',
        headers: this.getHeaders(),
      });
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      // Supprimer les tokens même si la requête échoue
      this.removeToken('access_token');
      this.removeToken('refresh_token');
      localStorage.removeItem('user');
      localStorage.removeItem('user_id');
      localStorage.removeItem('university_id');
    }
  }

  /**
   * GET /api/auth/me
   * 
   * Récupérer le profil de l'utilisateur connecté
   */
  async getMe(): Promise<any> {
    return this.get('/auth/me');
  }

  /**
   * POST /api/auth/register
   *
   * Inscription d'un nouvel administrateur.
   * Le code université (LCS) est envoyé automatiquement.
   */
  async register(data: {
    first_name: string;
    last_name: string;
    email: string;
    password: string;
    password_confirmation: string;
  }): Promise<any> {
    const response = await fetch(`${this.baseUrl}/auth/register`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(data),
    });
    return this.handleResponse<any>(response);
  }

  /**
   * ========== COURSES ==========
   */

  async getCourses(
    universityId: number,
    filters?: {
      level?: string;
      semester?: number;
      search?: string;
      limit?: number;
      offset?: number;
    }
  ): Promise<ApiResponse> {
    const params = new URLSearchParams();
    if (filters?.level) params.append('level', filters.level);
    if (filters?.semester) params.append('semester', filters.semester.toString());
    if (filters?.search) params.append('search', filters.search);
    if (filters?.limit) params.append('limit', filters.limit.toString());
    if (filters?.offset) params.append('offset', filters.offset.toString());

    const queryString = params.toString();
    const url = `/universities/${universityId}/courses${queryString ? '?' + queryString : ''}`;
    return this.get<ApiResponse>(url);
  }

  async getCourse(universityId: number, courseId: number): Promise<any> {
    return this.get(`/universities/${universityId}/courses/${courseId}`);
  }

  /**
   * ========== STUDENTS ==========
   */

  async getStudents(universityId: number, filters?: any): Promise<ApiResponse> {
    const params = new URLSearchParams();
    if (filters?.search) params.append('search', filters.search);
    if (filters?.level) params.append('level', filters.level);
    if (filters?.limit) params.append('limit', filters.limit.toString());
    if (filters?.offset) params.append('offset', (filters.offset ?? 0).toString());

    const queryString = params.toString();
    const url = `/universities/${universityId}/students${queryString ? '?' + queryString : ''}`;
    return this.get<ApiResponse>(url);
  }

  async getStudent(universityId: number, studentId: number): Promise<any> {
    return this.get(`/universities/${universityId}/students/${studentId}`);
  }

  async getStudentAttendances(
    universityId: number,
    studentId: number,
    filters?: {
      status?: string;
      course_id?: number;
      date_from?: string;
      date_to?: string;
      limit?: number;
    }
  ): Promise<ApiResponse> {
    const params = new URLSearchParams();
    if (filters?.status) params.append('status', filters.status);
    if (filters?.course_id) params.append('course_id', filters.course_id.toString());
    if (filters?.date_from) params.append('date_from', filters.date_from);
    if (filters?.date_to) params.append('date_to', filters.date_to);
    if (filters?.limit) params.append('limit', filters.limit.toString());

    const queryString = params.toString();
    const url = `/universities/${universityId}/students/${studentId}/attendances${
      queryString ? '?' + queryString : ''
    }`;
    return this.get<ApiResponse>(url);
  }

  /**
   * ========== TEACHERS ==========
   */

  /**
   * ========== FILIERES ==========
   */

  async getFilieres(universityId: number): Promise<ApiResponse> {
    return this.get(`/universities/${universityId}/filieres`);
  }

  async getFiliere(universityId: number, filiereId: number): Promise<any> {
    return this.get(`/universities/${universityId}/filieres/${filiereId}`);
  }

  /**
   * ========== TEACHERS ==========
   */

  async getTeachers(universityId: number): Promise<ApiResponse> {
    return this.get(`/universities/${universityId}/teachers`);
  }

  async getTeacher(universityId: number, teacherId: number): Promise<any> {
    return this.get(`/universities/${universityId}/teachers/${teacherId}`);
  }

  /**
   * ========== ATTENDANCES ==========
   */

  async getAttendances(
    universityId: number,
    filters?: {
      status?: string;
      student_id?: number;
      course_id?: number;
      date_from?: string;
      date_to?: string;
      limit?: number;
      offset?: number;
    }
  ): Promise<ApiResponse> {
    const params = new URLSearchParams();
    if (filters?.status) params.append('status', filters.status);
    if (filters?.student_id) params.append('student_id', filters.student_id.toString());
    if (filters?.course_id) params.append('course_id', filters.course_id.toString());
    if (filters?.date_from) params.append('date_from', filters.date_from);
    if (filters?.date_to) params.append('date_to', filters.date_to);
    if (filters?.limit) params.append('limit', (filters?.limit || 50).toString());
    if (filters?.offset) params.append('offset', (filters?.offset || 0).toString());

    const queryString = params.toString();
    const url = `/universities/${universityId}/attendances${queryString ? '?' + queryString : ''}`;
    return this.get<ApiResponse>(url);
  }

  async getAttendance(universityId: number, attendanceId: number): Promise<any> {
    return this.get(`/universities/${universityId}/attendances/${attendanceId}`);
  }

  /**
   * ========== UNIVERSITÉ ==========
   */

  async getUniversity(universityId: number): Promise<any> {
    return this.get(`/universities/${universityId}`);
  }

  /**
   * ========== RFID ==========
   */

  async scanRfidCard(
    universityId: number,
    cardNumber: string,
    classroomId?: number,
    timestamp?: string
  ): Promise<any> {
    const response = await fetch(`${this.baseUrl}/universities/${universityId}/rfid/scan`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify({
        card_number: cardNumber,
        classroom_id: classroomId,
        timestamp,
      }),
    });

    return this.handleResponse<any>(response);
  }

  // ─── Attribution RFID ────────────────────────────────────────────────────

  async assignRfidCard(universityId: number, studentId: number, cardNumber: string): Promise<any> {
    return this.post(`/universities/${universityId}/rfid/assign`, {
      student_id:  studentId,
      card_number: cardNumber,
    });
  }

  async toggleRfidCard(universityId: number, cardId: string, reason?: string): Promise<any> {
    return this.patch(`/universities/${universityId}/rfid/${cardId}/toggle`, { reason });
  }

  async deleteRfidCard(universityId: number, cardId: string): Promise<any> {
    return this.delete(`/universities/${universityId}/rfid/${cardId}`);
  }

  async getRfidLogs(universityId: number, filters?: any): Promise<ApiResponse> {
    const params = new URLSearchParams();
    if (filters?.status) params.append('status', filters.status);
    if (filters?.limit) params.append('limit', (filters?.limit || 100).toString());

    const queryString = params.toString();
    const url = `/universities/${universityId}/rfid/logs${queryString ? '?' + queryString : ''}`;
    return this.get<ApiResponse>(url);
  }

  /**
   * ========== REQUÊTES HTTP GÉNÉRIQUES ==========
   */

  /**
   * GET request
   */
  private async get<T = any>(path: string): Promise<T> {
    const response = await fetch(`${this.baseUrl}${path}`, {
      method: 'GET',
      headers: this.getHeaders(),
    });

    return this.handleResponse<T>(response);
  }

  /**
   * POST request
   */
  private async post<T = any>(path: string, body: any): Promise<T> {
    const response = await fetch(`${this.baseUrl}${path}`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(body),
    });

    return this.handleResponse<T>(response);
  }

  /**
   * PUT request
   */
  private async put<T = any>(path: string, body: any): Promise<T> {
    const response = await fetch(`${this.baseUrl}${path}`, {
      method: 'PUT',
      headers: this.getHeaders(),
      body: JSON.stringify(body),
    });

    return this.handleResponse<T>(response);
  }

  /**
   * PATCH request
   */
  private async patch<T = any>(path: string, body: any): Promise<T> {
    const response = await fetch(`${this.baseUrl}${path}`, {
      method: 'PATCH',
      headers: this.getHeaders(),
      body: JSON.stringify(body),
    });

    return this.handleResponse<T>(response);
  }

  /**
   * DELETE request
   */
  private async delete<T = any>(path: string): Promise<T> {
    const response = await fetch(`${this.baseUrl}${path}`, {
      method: 'DELETE',
      headers: this.getHeaders(),
    });

    return this.handleResponse<T>(response);
  }

  /**
   * Vérifier si l'utilisateur est connecté
   */
  isAuthenticated(): boolean {
    return !!this.accessToken && this.accessToken !== 'undefined' && this.accessToken !== 'null';
  }

  /**
   * Récupérer l'utilisateur stocké
   */
  getUser(): any {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  }

  /**
   * Récupérer l'ID de l'université
   */
  getUniversityId(): number | null {
    const id = localStorage.getItem('university_id');
    if (!id || id === 'undefined' || id === 'null') return null;
    const parsed = parseInt(id, 10);
    return isNaN(parsed) ? null : parsed;
  }
}

// Exporter une instance unique
export const api = new ApiClient();
