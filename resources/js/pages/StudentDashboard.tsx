import { useEffect, useState } from 'react';
import { useAuthContext } from '~/contexts/AuthContext';
import { useNavigate } from 'react-router-dom';
import { api } from '~/lib/api';

interface AttendanceRecord {
  id: number;
  course: {
    id: number;
    name: string;
    code: string;
  };
  status: 'present' | 'late' | 'absent';
  scanned_at: string;
  timetable?: {
    day: string;
    start_time: string;
    end_time: string;
  };
}

interface CourseStats {
  course_id: number;
  course_name: string;
  course_code: string;
  total_sessions: number;
  attended_sessions: number;
  attendance_rate: number;
}

// ── Sub-components ─────────────────────────────────────────────────────────────

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

function ProfileField({ label, value }: { label: string; value?: string | null }) {
  const displayValue = value != null && typeof value !== 'object' ? String(value) : null;
  return (
    <div style={{
      background: 'oklch(0.14 0.016 240)', borderRadius: 10,
      padding: '14px 18px',
    }}>
      <p style={{
        fontSize: 10.5, fontWeight: 600, color: 'oklch(0.50 0.018 225)',
        textTransform: 'uppercase', letterSpacing: '0.08em', marginBottom: 5,
      }}>{label}</p>
      <p style={{ fontSize: 14.5, fontWeight: 600, color: 'oklch(0.90 0.015 220)' }}>
        {displayValue || '—'}
      </p>
    </div>
  );
}

// ── Main component ─────────────────────────────────────────────────────────────

export default function StudentDashboard() {
  const navigate = useNavigate();
  const { user, isAuthenticated, logout } = useAuthContext();
  const [studentData, setStudentData] = useState<any>(null);
  const [attendances, setAttendances] = useState<AttendanceRecord[]>([]);
  const [courseStats, setCourseStats] = useState<CourseStats[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isAuthenticated) {
      navigate('/login');
      return;
    }

    const loadStudentData = async () => {
      try {
        setIsLoading(true);
        // University ID: from localStorage (set during login) or from user object (me() response)
        const universityId = api.getUniversityId()
          ?? (user as any)?.universityId
          ?? (user as any)?.university_id;
        if (!universityId) throw new Error('No university ID');

        // The me() endpoint returns student.studentId for student users.
        // user.id is the User model ID — we need the Student record ID.
        const studentId: number = (user as any)?.student?.studentId ?? user!.id;
        const student = await api.getStudent(universityId, studentId);
        setStudentData(student);
        setCourseStats(student.course_statistics || []);

        const response = await api.getStudentAttendances(universityId, studentId, { limit: 20 });
        setAttendances(response.data || []);
      } catch (err: any) {
        setError(err.message || 'Failed to load data');
      } finally {
        setIsLoading(false);
      }
    };

    loadStudentData();
  }, [isAuthenticated, navigate, user]);

  const handleLogout = async () => {
    try { await logout(); navigate('/login'); }
    catch (err) { console.error('Logout error:', err); }
  };

  if (!isAuthenticated) return null;

  // Priorité : taux global calculé par le backend (overall_statistics).
  // Fallback : moyenne des taux par cours si overall_statistics absent.
  const overallRate: number | null =
    studentData?.overall_statistics != null
      ? Math.round(studentData.overall_statistics.overall_attendance_rate)
      : courseStats.length > 0
        ? Math.round(courseStats.reduce((sum: number, s: CourseStats) => sum + s.attendance_rate, 0) / courseStats.length)
        : null;

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
                Portail Étudiant
              </p>
            </div>
          </div>

          <div style={{ display: 'flex', alignItems: 'center', gap: 20 }}>
            <div style={{ textAlign: 'right' }}>
              <p style={{ fontSize: 13.5, fontWeight: 600, color: 'oklch(0.90 0.015 220)' }}>
                {(user as any)?.firstName || (user as any)?.lastName || user?.email}
              </p>
              <p style={{ fontSize: 11, color: '#2DB87A', marginTop: -1 }}>Étudiant</p>
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
            <p style={{ fontSize: 13, color: 'oklch(0.55 0.020 225)' }}>Chargement de votre espace…</p>
          </div>
        ) : (
          <div className="slide-up" style={{ display: 'flex', flexDirection: 'column', gap: 24 }}>

            {/* ── Profile + overall rate ── */}
            <div style={{ display: 'grid', gridTemplateColumns: '1fr auto', gap: 20, alignItems: 'stretch' }}>

              {/* Profile card */}
              <div style={{
                background: 'oklch(0.11 0.014 240)',
                border: '1px solid oklch(0.19 0.018 240)',
                borderRadius: 14, padding: '24px 28px',
                position: 'relative', overflow: 'hidden',
              }}>
                <div style={{
                  position: 'absolute', top: 0, left: 0, right: 0, height: 2,
                  background: 'linear-gradient(90deg, #2DB87A, #5BA3E8)', opacity: 0.7,
                }} />
                <p style={{
                  fontSize: 11, fontWeight: 700, color: 'oklch(0.50 0.018 225)',
                  textTransform: 'uppercase', letterSpacing: '0.08em', marginBottom: 18,
                }}>Informations personnelles</p>
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
                  <ProfileField label="Nom complet"    value={studentData?.user?.name} />
                  <ProfileField label="N° Matricule"   value={studentData?.registration_number} />
                  <ProfileField label="Adresse email"  value={user?.email} />
                  <ProfileField label="Niveau"         value={studentData?.level} />
                  <ProfileField
                    label="Filière"
                    value={(user as any)?.student?.filiere?.name ?? null}
                  />
                  <ProfileField
                    label="Année d'inscription"
                    value={(user as any)?.student?.enrollmentYear != null
                      ? String((user as any).student.enrollmentYear)
                      : null}
                  />
                </div>
              </div>

              {/* Overall attendance rate */}
              {overallRate !== null && (
                <div style={{
                  background: 'oklch(0.11 0.014 240)',
                  border: '1px solid oklch(0.19 0.018 240)',
                  borderRadius: 14, padding: '24px 32px',
                  display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center',
                  position: 'relative', overflow: 'hidden', minWidth: 180,
                }}>
                  <div style={{
                    position: 'absolute', top: 0, left: 0, right: 0, height: 2,
                    background: overallRate >= 80 ? '#2DB87A' : overallRate >= 60 ? '#D4A017' : '#E55C5C',
                    opacity: 0.8,
                  }} />
                  <p style={{
                    fontSize: 10.5, fontWeight: 700, color: 'oklch(0.50 0.018 225)',
                    textTransform: 'uppercase', letterSpacing: '0.08em', marginBottom: 10,
                    textAlign: 'center',
                  }}>Taux global</p>
                  <p style={{
                    fontSize: 52, fontWeight: 900, letterSpacing: '-3px', lineHeight: 1,
                    color: overallRate >= 80 ? '#2DB87A' : overallRate >= 60 ? '#D4A017' : '#E55C5C',
                  }}>{overallRate}<span style={{ fontSize: 24, letterSpacing: '-1px' }}>%</span></p>
                  <p style={{ fontSize: 11, color: 'oklch(0.50 0.018 225)', marginTop: 8 }}>
                    {courseStats.length} cours
                  </p>
                </div>
              )}
            </div>

            {/* ── Overall attendance summary ── */}
            {studentData?.overall_statistics && (
              <div style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fit, minmax(140px, 1fr))',
                gap: 14,
              }}>
                {[
                  { label: 'Présences', value: studentData.overall_statistics.present_count,           color: '#2DB87A' },
                  { label: 'Retards',   value: studentData.overall_statistics.late_count,              color: '#D4A017' },
                  { label: 'Absences',  value: studentData.overall_statistics.absent_count,            color: '#E55C5C' },
                  { label: 'Total séances', value: studentData.overall_statistics.total_sessions_attended, color: '#5BA3E8' },
                ].map(({ label, value, color }) => (
                  <div key={label} style={{
                    background: 'oklch(0.11 0.014 240)',
                    border: '1px solid oklch(0.19 0.018 240)',
                    borderRadius: 12, padding: '18px 20px',
                    position: 'relative', overflow: 'hidden',
                  }}>
                    <div style={{
                      position: 'absolute', top: 0, left: 0, right: 0, height: 2,
                      background: color, opacity: 0.8,
                    }} />
                    <p style={{
                      fontSize: 10.5, fontWeight: 600,
                      color: 'oklch(0.50 0.018 225)',
                      textTransform: 'uppercase', letterSpacing: '0.07em', marginBottom: 8,
                    }}>{label}</p>
                    <p style={{
                      fontSize: 28, fontWeight: 800, color,
                      letterSpacing: '-1px', lineHeight: 1,
                    }}>{value}</p>
                  </div>
                ))}
              </div>
            )}

            {/* ── Course attendance stats ── */}
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
                  Présence par cours
                </p>
                <span style={{
                  fontSize: 11, padding: '3px 10px', borderRadius: 20,
                  background: 'rgba(91,163,232,0.10)', color: '#5BA3E8',
                  border: '1px solid rgba(91,163,232,0.20)', fontWeight: 600,
                }}>{courseStats.length} cours</span>
              </div>

              {courseStats.length === 0 ? (
                <p style={{ color: 'oklch(0.50 0.018 225)', fontSize: 13, padding: '24px 20px' }}>
                  Aucun cours inscrit
                </p>
              ) : (
                <div style={{ padding: '16px 20px', display: 'flex', flexDirection: 'column', gap: 14 }}>
                  {courseStats.map(stats => {
                    const rate = stats.attendance_rate;
                    const barColor = rate >= 80 ? '#2DB87A' : rate >= 60 ? '#D4A017' : '#E55C5C';
                    return (
                      <div key={stats.course_id} style={{
                        background: 'oklch(0.14 0.016 240)',
                        borderRadius: 10, padding: '14px 18px',
                        borderLeft: `3px solid ${barColor}`,
                      }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 10 }}>
                          <div>
                            <p style={{ fontWeight: 700, fontSize: 14, color: 'oklch(0.90 0.015 220)' }}>
                              {stats.course_name}
                            </p>
                            <p style={{
                              fontSize: 11.5, color: 'oklch(0.50 0.018 225)', marginTop: 2,
                              fontFamily: "'IBM Plex Mono', monospace",
                            }}>{stats.course_code}</p>
                          </div>
                          <div style={{ textAlign: 'right' }}>
                            <p style={{ fontSize: 26, fontWeight: 900, color: barColor, letterSpacing: '-1px', lineHeight: 1 }}>
                              {rate}<span style={{ fontSize: 14 }}>%</span>
                            </p>
                            <p style={{ fontSize: 11, color: 'oklch(0.50 0.018 225)', marginTop: 2 }}>
                              {stats.attended_sessions}/{stats.total_sessions} séances
                            </p>
                          </div>
                        </div>

                        {/* Progress bar */}
                        <div style={{
                          height: 4, borderRadius: 99,
                          background: 'oklch(0.19 0.018 240)', overflow: 'hidden',
                        }}>
                          <div style={{
                            height: '100%', borderRadius: 99,
                            width: `${rate}%`,
                            background: barColor,
                            boxShadow: `0 0 8px ${barColor}66`,
                            transition: 'width 0.6s cubic-bezier(0.16,1,0.3,1)',
                          }} />
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>

            {/* ── Recent attendance table ── */}
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

              {attendances.length === 0 ? (
                <p style={{ color: 'oklch(0.50 0.018 225)', fontSize: 13, padding: '24px 20px' }}>
                  Aucun enregistrement de présence
                </p>
              ) : (
                <div style={{ overflowX: 'auto' }}>
                  <table style={{ width: '100%', fontSize: 13, borderCollapse: 'collapse' }}>
                    <thead>
                      <tr style={{ borderBottom: '1px solid oklch(0.19 0.018 240)' }}>
                        {['Cours', 'Date', 'Horaire', 'Statut'].map(h => (
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
                      {attendances.map(record => (
                        <tr
                          key={record.id}
                          style={{ borderBottom: '1px solid oklch(0.14 0.015 240)', transition: 'background 0.15s' }}
                          onMouseEnter={e => (e.currentTarget.style.background = 'oklch(0.13 0.015 240)')}
                          onMouseLeave={e => (e.currentTarget.style.background = 'transparent')}
                        >
                          <td style={{ padding: '11px 16px' }}>
                            <p style={{ fontWeight: 600, color: 'oklch(0.90 0.015 220)' }}>
                              {typeof record.course?.name === 'string' ? record.course.name : 'Cours inconnu'}
                            </p>
                            <p style={{
                              fontSize: 11, color: 'oklch(0.50 0.018 225)',
                              fontFamily: "'IBM Plex Mono', monospace",
                            }}>{typeof record.course?.code === 'string' ? record.course.code : '—'}</p>
                          </td>
                          <td style={{
                            padding: '11px 16px', color: 'oklch(0.55 0.020 225)',
                            fontFamily: "'IBM Plex Mono', monospace", fontSize: 12,
                          }}>
                            {new Date(record.scanned_at).toLocaleDateString('fr-FR')}
                          </td>
                          <td style={{
                            padding: '11px 16px', color: 'oklch(0.55 0.020 225)',
                            fontFamily: "'IBM Plex Mono', monospace", fontSize: 12,
                          }}>
                            {record.timetable
                              ? `${record.timetable.start_time} – ${record.timetable.end_time}`
                              : '—'}
                          </td>
                          <td style={{ padding: '11px 16px' }}>
                            <StatusBadge status={record.status} />
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </div>
        )}
      </main>
    </div>
  );
}
