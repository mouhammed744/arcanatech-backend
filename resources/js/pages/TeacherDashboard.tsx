import { useEffect, useState } from 'react';
import { useAuthContext } from '~/contexts/AuthContext';
import { useNavigate } from 'react-router-dom';
import { api } from '~/lib/api';

interface Course {
  id: number;
  name: string;
  code: string;
  level: string;
  credits: number;
  enrolled_students: number;
  sessions: number;
}

interface StudentAttendance {
  id: number;
  name?: string;
  student_name: string;
  registration_number?: string;
  status: 'present' | 'late' | 'absent';
  scanned_at: string;
}

// ── Sub-components ─────────────────────────────────────────────────────────────

function StatCard({ label, value, color }: { label: string; value: number | string; color: string }) {
  return (
    <div style={{
      background: 'oklch(0.11 0.014 240)',
      border: '1px solid oklch(0.19 0.018 240)',
      borderRadius: 12, padding: '20px 22px',
      position: 'relative', overflow: 'hidden',
    }}>
      <div style={{
        position: 'absolute', top: 0, left: 0, right: 0, height: 2,
        background: color, opacity: 0.8,
      }} />
      <p style={{
        fontSize: 11, color: 'oklch(0.55 0.020 225)', fontWeight: 600,
        textTransform: 'uppercase', letterSpacing: '0.07em', marginBottom: 10,
      }}>{label}</p>
      <p style={{ fontSize: 30, fontWeight: 800, color, letterSpacing: '-1.5px', lineHeight: 1 }}>
        {value}
      </p>
    </div>
  );
}

function StatusBadge({ status }: { status: string }) {
  const cfg: Record<string, { bg: string; color: string; border: string; label: string }> = {
    present: { bg: 'rgba(45,184,122,0.12)',  color: '#2DB87A', border: 'rgba(45,184,122,0.25)',  label: 'Présent'   },
    late:    { bg: 'rgba(212,160,23,0.12)',  color: '#D4A017', border: 'rgba(212,160,23,0.25)',  label: 'En retard' },
    absent:  { bg: 'rgba(229,92,92,0.12)',   color: '#E55C5C', border: 'rgba(229,92,92,0.25)',   label: 'Absent'    },
  };
  const c = cfg[status] ?? cfg.absent;
  return (
    <span style={{
      padding: '3px 10px', borderRadius: 20, fontSize: 11.5, fontWeight: 600,
      background: c.bg, color: c.color, border: `1px solid ${c.border}`,
    }}>{c.label}</span>
  );
}

// ── Main component ─────────────────────────────────────────────────────────────

export default function TeacherDashboard() {
  const navigate = useNavigate();
  const { user, isAuthenticated, logout } = useAuthContext();
  const [courses, setCourses] = useState<Course[]>([]);
  const [selectedCourse, setSelectedCourse] = useState<number | null>(null);
  const [courseDetails, setCourseDetails] = useState<any>(null);
  const [attendances, setAttendances] = useState<StudentAttendance[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [detailsLoading, setDetailsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isAuthenticated || user?.role !== 'teacher') {
      navigate('/login');
      return;
    }

    const loadCourses = async () => {
      try {
        setIsLoading(true);
        const universityId = api.getUniversityId()
          ?? (user as any)?.universityId
          ?? (user as any)?.university_id;
        if (!universityId) throw new Error('No university ID');

        const response = await api.getCourses(universityId);
        setCourses(response.data || []);

        if (response.data && response.data.length > 0) {
          setSelectedCourse(response.data[0].id);
        }
      } catch (err: any) {
        setError(err.message || 'Failed to load courses');
      } finally {
        setIsLoading(false);
      }
    };

    loadCourses();
  }, [isAuthenticated, navigate, user]);

  useEffect(() => {
    if (!selectedCourse) return;

    const loadCourseDetails = async () => {
      try {
        setDetailsLoading(true);
        const universityId = api.getUniversityId()
          ?? (user as any)?.universityId
          ?? (user as any)?.university_id;
        if (!universityId) return;

        const details = await api.getCourse(universityId, selectedCourse);
        setCourseDetails(details);

        const response = await api.getAttendances(universityId, {
          course_id: selectedCourse,
          limit: 100,
        });
        setAttendances(response.data || []);
      } catch (err: any) {
        setError(err.message || 'Failed to load course details');
      } finally {
        setDetailsLoading(false);
      }
    };

    loadCourseDetails();
  }, [selectedCourse, user]);

  const handleLogout = async () => {
    try { await logout(); navigate('/login'); }
    catch (err) { console.error('Logout error:', err); }
  };

  if (!isAuthenticated || user?.role !== 'teacher') return null;

  const selectedCourseData = courses.find(c => c.id === selectedCourse);

  return (
    <div style={{
      minHeight: '100vh',
      background: 'oklch(0.085 0.012 240)',
      fontFamily: "'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif",
      colorScheme: 'dark',
    }}>
      <style>{`
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes slideUp {
          from { opacity: 0; transform: translateY(14px); }
          to   { opacity: 1; transform: translateY(0); }
        }
        .slide-up { animation: slideUp 0.35s cubic-bezier(0.16,1,0.3,1) both; }
        .course-btn:hover { background: oklch(0.16 0.017 240) !important; }
      `}</style>

      {/* ── HEADER ── */}
      <header style={{
        background: 'oklch(0.11 0.014 240)',
        borderBottom: '1px solid oklch(0.19 0.018 240)',
        position: 'sticky', top: 0, zIndex: 40,
        backdropFilter: 'blur(12px)',
      }}>
        <div style={{
          maxWidth: 1280, margin: '0 auto', padding: '0 28px',
          display: 'flex', alignItems: 'center', justifyContent: 'space-between', height: 64,
        }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
            <div style={{
              width: 38, height: 38, borderRadius: 10,
              background: 'linear-gradient(135deg, #E8A020 0%, #C88A10 100%)',
              display: 'flex', alignItems: 'center', justifyContent: 'center',
              fontSize: 13, fontWeight: 900, color: 'oklch(0.08 0.010 240)',
              letterSpacing: '-0.5px',
              boxShadow: '0 0 18px rgba(232,160,32,0.40), 0 2px 8px rgba(0,0,0,0.4)',
            }}>LCS</div>
            <div>
              <p style={{ fontSize: 15, fontWeight: 700, color: 'oklch(0.90 0.015 220)', letterSpacing: '-0.3px' }}>
                Les Cours Sonou
              </p>
              <p style={{ fontSize: 11, color: 'oklch(0.50 0.018 225)', marginTop: -1 }}>
                Portail Enseignant
              </p>
            </div>
          </div>

          <div style={{ display: 'flex', alignItems: 'center', gap: 20 }}>
            <div style={{ textAlign: 'right' }}>
              <p style={{ fontSize: 13.5, fontWeight: 600, color: 'oklch(0.90 0.015 220)' }}>
                {(user as any)?.firstName || (user as any)?.lastName || user?.email}
              </p>
              <p style={{ fontSize: 11, color: '#5BA3E8', marginTop: -1 }}>Enseignant</p>
            </div>
            <button
              onClick={handleLogout}
              style={{
                padding: '7px 16px', borderRadius: 8, fontSize: 13, fontWeight: 600,
                cursor: 'pointer', background: 'rgba(229,92,92,0.10)',
                color: '#E55C5C', border: '1px solid rgba(229,92,92,0.22)',
                transition: 'all 0.2s', fontFamily: 'inherit',
              }}
              onMouseEnter={e => { (e.target as HTMLElement).style.background = 'rgba(229,92,92,0.20)'; }}
              onMouseLeave={e => { (e.target as HTMLElement).style.background = 'rgba(229,92,92,0.10)'; }}
            >
              Déconnexion
            </button>
          </div>
        </div>
      </header>

      {/* ── MAIN ── */}
      <main style={{ maxWidth: 1280, margin: '0 auto', padding: '32px 28px' }}>

        {error && (
          <div style={{
            background: 'rgba(229,92,92,0.10)', border: '1px solid rgba(229,92,92,0.25)',
            color: '#E55C5C', padding: '12px 16px', borderRadius: 10, fontSize: 13, marginBottom: 24,
          }}>{error}</div>
        )}

        {isLoading ? (
          <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: 300, flexDirection: 'column', gap: 14 }}>
            <div style={{
              width: 36, height: 36, borderRadius: '50%',
              border: '3px solid rgba(232,160,32,0.15)',
              borderTop: '3px solid #E8A020',
              animation: 'spin 0.8s linear infinite',
            }} />
            <p style={{ fontSize: 13, color: 'oklch(0.55 0.020 225)' }}>Chargement des cours…</p>
          </div>
        ) : (
          <div style={{ display: 'grid', gridTemplateColumns: '260px 1fr', gap: 24 }}>

            {/* ── Sidebar: Course list ── */}
            <aside>
              <div style={{
                background: 'oklch(0.11 0.014 240)',
                border: '1px solid oklch(0.19 0.018 240)',
                borderRadius: 14, overflow: 'hidden',
                position: 'sticky', top: 88,
              }}>
                <div style={{
                  padding: '14px 18px',
                  borderBottom: '1px solid oklch(0.19 0.018 240)',
                }}>
                  <p style={{ fontSize: 11, fontWeight: 700, color: 'oklch(0.50 0.018 225)', textTransform: 'uppercase', letterSpacing: '0.08em' }}>
                    Mes cours
                  </p>
                  <p style={{ fontSize: 20, fontWeight: 800, color: 'oklch(0.90 0.015 220)', marginTop: 2 }}>
                    {courses.length}
                  </p>
                </div>

                <div style={{ padding: 8 }}>
                  {courses.length === 0 ? (
                    <p style={{ color: 'oklch(0.50 0.018 225)', fontSize: 13, padding: '12px 10px' }}>
                      Aucun cours assigné
                    </p>
                  ) : (
                    courses.map(course => {
                      const active = selectedCourse === course.id;
                      return (
                        <button
                          key={course.id}
                          className="course-btn"
                          onClick={() => setSelectedCourse(course.id)}
                          style={{
                            width: '100%', textAlign: 'left', padding: '10px 12px',
                            borderRadius: 8, cursor: 'pointer', transition: 'all 0.15s',
                            border: active ? '1px solid rgba(232,160,32,0.30)' : '1px solid transparent',
                            background: active ? 'rgba(232,160,32,0.10)' : 'transparent',
                            marginBottom: 2, fontFamily: 'inherit',
                          }}
                        >
                          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                            <p style={{
                              fontWeight: 700, fontSize: 13,
                              color: active ? '#E8A020' : 'oklch(0.85 0.015 220)',
                              fontFamily: "'IBM Plex Mono', monospace",
                            }}>{course.code}</p>
                            <span style={{
                              fontSize: 10, padding: '2px 7px', borderRadius: 20,
                              background: active ? 'rgba(232,160,32,0.15)' : 'oklch(0.14 0.016 240)',
                              color: active ? '#E8A020' : 'oklch(0.50 0.018 225)',
                              fontWeight: 600,
                            }}>{course.enrolled_students}</span>
                          </div>
                          <p style={{
                            fontSize: 11.5, color: 'oklch(0.55 0.020 225)', marginTop: 2,
                            whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis',
                          }}>{course.name}</p>
                        </button>
                      );
                    })
                  )}
                </div>
              </div>
            </aside>

            {/* ── Main content ── */}
            <div key={selectedCourse} className="slide-up" style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>

              {detailsLoading ? (
                <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: 240, flexDirection: 'column', gap: 14 }}>
                  <div style={{
                    width: 30, height: 30, borderRadius: '50%',
                    border: '3px solid rgba(232,160,32,0.15)',
                    borderTop: '3px solid #E8A020',
                    animation: 'spin 0.8s linear infinite',
                  }} />
                  <p style={{ fontSize: 13, color: 'oklch(0.55 0.020 225)' }}>Chargement…</p>
                </div>
              ) : selectedCourse && courseDetails ? (
                <>
                  {/* Course header card */}
                  <div style={{
                    background: 'oklch(0.11 0.014 240)',
                    border: '1px solid oklch(0.19 0.018 240)',
                    borderRadius: 14, padding: '24px 28px',
                    position: 'relative', overflow: 'hidden',
                  }}>
                    <div style={{
                      position: 'absolute', top: 0, left: 0, right: 0, height: 2,
                      background: 'linear-gradient(90deg, #E8A020, #5BA3E8)', opacity: 0.7,
                    }} />
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                      <div>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 6 }}>
                          <span style={{
                            fontFamily: "'IBM Plex Mono', monospace",
                            fontSize: 12, padding: '3px 10px', borderRadius: 20,
                            background: 'rgba(91,163,232,0.10)', color: '#5BA3E8',
                            border: '1px solid rgba(91,163,232,0.20)', fontWeight: 600,
                          }}>{courseDetails.code}</span>
                        </div>
                        <h2 style={{ fontSize: 22, fontWeight: 800, color: 'oklch(0.90 0.015 220)', letterSpacing: '-0.5px' }}>
                          {courseDetails.name}
                        </h2>
                        {courseDetails.description && (
                          <p style={{ fontSize: 13, color: 'oklch(0.55 0.020 225)', marginTop: 6 }}>
                            {courseDetails.description}
                          </p>
                        )}
                      </div>
                      <span style={{
                        fontSize: 12, padding: '5px 14px', borderRadius: 20,
                        background: 'rgba(232,160,32,0.10)', color: '#E8A020',
                        border: '1px solid rgba(232,160,32,0.25)', fontWeight: 700,
                        whiteSpace: 'nowrap',
                      }}>{courseDetails.level}</span>
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 20, marginTop: 22 }}>
                      {[
                        { label: 'Crédits', value: courseDetails.credits, color: '#E8A020' },
                        { label: 'Étudiants inscrits', value: courseDetails.students?.length ?? 0, color: '#5BA3E8' },
                        { label: 'Séances', value: courseDetails.timetable?.length ?? 0, color: '#2DB87A' },
                      ].map(({ label, value, color }) => (
                        <div key={label} style={{
                          background: 'oklch(0.14 0.016 240)',
                          borderRadius: 10, padding: '14px 18px',
                        }}>
                          <p style={{ fontSize: 11, color: 'oklch(0.50 0.018 225)', fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.07em', marginBottom: 6 }}>
                            {label}
                          </p>
                          <p style={{ fontSize: 28, fontWeight: 800, color, letterSpacing: '-1px' }}>{value}</p>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* Attendance statistics */}
                  {courseDetails.statistics && (
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))', gap: 14 }}>
                      <StatCard label="Total présences"  value={courseDetails.statistics.total_attendances} color="#5BA3E8" />
                      <StatCard label="Présents"         value={courseDetails.statistics.present_count}     color="#2DB87A" />
                      <StatCard label="En retard"        value={courseDetails.statistics.late_count}        color="#D4A017" />
                      <StatCard label="Absents"          value={courseDetails.statistics.absent_count}      color="#E55C5C" />
                    </div>
                  )}

                  {/* Enrolled students */}
                  <div style={{
                    background: 'oklch(0.11 0.014 240)',
                    border: '1px solid oklch(0.19 0.018 240)',
                    borderRadius: 14, overflow: 'hidden',
                  }}>
                    <div style={{
                      padding: '16px 20px', borderBottom: '1px solid oklch(0.19 0.018 240)',
                      display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    }}>
                      <p style={{ fontSize: 15, fontWeight: 700, color: 'oklch(0.90 0.015 220)' }}>
                        Étudiants inscrits
                      </p>
                      <span style={{
                        fontSize: 11, padding: '3px 10px', borderRadius: 20,
                        background: 'rgba(91,163,232,0.10)', color: '#5BA3E8',
                        border: '1px solid rgba(91,163,232,0.20)', fontWeight: 600,
                      }}>{courseDetails.students?.length ?? 0}</span>
                    </div>

                    {courseDetails.students && courseDetails.students.length > 0 ? (
                      <div style={{ overflowX: 'auto' }}>
                        <table style={{ width: '100%', fontSize: 13, borderCollapse: 'collapse' }}>
                          <thead>
                            <tr style={{ borderBottom: '1px solid oklch(0.19 0.018 240)' }}>
                              {['Nom', 'N° Matricule'].map(h => (
                                <th key={h} style={{
                                  textAlign: 'left', padding: '11px 16px',
                                  fontSize: 11, fontWeight: 600, textTransform: 'uppercase',
                                  letterSpacing: '0.08em', color: 'oklch(0.50 0.018 225)',
                                  background: 'oklch(0.10 0.013 240)',
                                }}>{h}</th>
                              ))}
                            </tr>
                          </thead>
                          <tbody>
                            {courseDetails.students.map((student: any) => (
                              <tr
                                key={student.id}
                                style={{ borderBottom: '1px solid oklch(0.14 0.015 240)', transition: 'background 0.15s' }}
                                onMouseEnter={e => (e.currentTarget.style.background = 'oklch(0.13 0.015 240)')}
                                onMouseLeave={e => (e.currentTarget.style.background = 'transparent')}
                              >
                                <td style={{ padding: '11px 16px', fontWeight: 500, color: 'oklch(0.88 0.015 220)' }}>
                                  {student.name}
                                </td>
                                <td style={{
                                  padding: '11px 16px', color: 'oklch(0.55 0.020 225)',
                                  fontFamily: "'IBM Plex Mono', monospace", fontSize: 12,
                                }}>
                                  {student.registration_number}
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    ) : (
                      <p style={{ color: 'oklch(0.50 0.018 225)', fontSize: 13, padding: '24px 20px' }}>
                        Aucun étudiant inscrit
                      </p>
                    )}
                  </div>

                  {/* Recent attendance */}
                  <div style={{
                    background: 'oklch(0.11 0.014 240)',
                    border: '1px solid oklch(0.19 0.018 240)',
                    borderRadius: 14, overflow: 'hidden',
                  }}>
                    <div style={{
                      padding: '16px 20px', borderBottom: '1px solid oklch(0.19 0.018 240)',
                      display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    }}>
                      <p style={{ fontSize: 15, fontWeight: 700, color: 'oklch(0.90 0.015 220)' }}>
                        Présences récentes
                      </p>
                      <span style={{
                        fontSize: 11, padding: '3px 10px', borderRadius: 20,
                        background: 'rgba(232,160,32,0.10)', color: '#E8A020',
                        border: '1px solid rgba(232,160,32,0.20)', fontWeight: 600,
                      }}>{attendances.length} enregistrements</span>
                    </div>

                    {attendances.length > 0 ? (
                      <div style={{ overflowX: 'auto' }}>
                        <table style={{ width: '100%', fontSize: 13, borderCollapse: 'collapse' }}>
                          <thead>
                            <tr style={{ borderBottom: '1px solid oklch(0.19 0.018 240)' }}>
                              {['Étudiant', 'Date', 'Statut'].map(h => (
                                <th key={h} style={{
                                  textAlign: 'left', padding: '11px 16px',
                                  fontSize: 11, fontWeight: 600, textTransform: 'uppercase',
                                  letterSpacing: '0.08em', color: 'oklch(0.50 0.018 225)',
                                  background: 'oklch(0.10 0.013 240)',
                                }}>{h}</th>
                              ))}
                            </tr>
                          </thead>
                          <tbody>
                            {attendances.slice(0, 20).map(record => (
                              <tr
                                key={record.id}
                                style={{ borderBottom: '1px solid oklch(0.14 0.015 240)', transition: 'background 0.15s' }}
                                onMouseEnter={e => (e.currentTarget.style.background = 'oklch(0.13 0.015 240)')}
                                onMouseLeave={e => (e.currentTarget.style.background = 'transparent')}
                              >
                                <td style={{ padding: '11px 16px' }}>
                                  <p style={{ fontWeight: 600, color: 'oklch(0.90 0.015 220)' }}>
                                    {record.student_name}
                                  </p>
                                  {record.registration_number && (
                                    <p style={{
                                      fontSize: 11, color: 'oklch(0.50 0.018 225)',
                                      fontFamily: "'IBM Plex Mono', monospace",
                                    }}>{record.registration_number}</p>
                                  )}
                                </td>
                                <td style={{
                                  padding: '11px 16px', color: 'oklch(0.55 0.020 225)',
                                  fontFamily: "'IBM Plex Mono', monospace", fontSize: 12,
                                }}>
                                  {new Date(record.scanned_at).toLocaleDateString('fr-FR')}
                                  {' '}
                                  {new Date(record.scanned_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}
                                </td>
                                <td style={{ padding: '11px 16px' }}>
                                  <StatusBadge status={record.status} />
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    ) : (
                      <p style={{ color: 'oklch(0.50 0.018 225)', fontSize: 13, padding: '24px 20px' }}>
                        Aucun enregistrement de présence
                      </p>
                    )}
                  </div>
                </>
              ) : (
                <div style={{
                  background: 'oklch(0.11 0.014 240)',
                  border: '1px solid oklch(0.19 0.018 240)',
                  borderRadius: 14, padding: '60px 24px',
                  display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 12,
                }}>
                  <div style={{ fontSize: 40, opacity: 0.3 }}>📚</div>
                  <p style={{ color: 'oklch(0.55 0.020 225)', fontSize: 14 }}>
                    Sélectionnez un cours pour voir les détails
                  </p>
                </div>
              )}
            </div>
          </div>
        )}
      </main>
    </div>
  );
}
