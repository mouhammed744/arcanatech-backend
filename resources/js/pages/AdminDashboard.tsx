import { useEffect, useState } from 'react';
import { useAuthContext } from '~/contexts/AuthContext';
import { useNavigate } from 'react-router-dom';
import { api } from '~/lib/api';
import StudentsPage from '~/pages/StudentsPage';
import FilieresPage from '~/pages/FilieresPage';

interface AttendanceStats {
  total_count: number;
  present_count: number;
  late_count: number;
  absent_count: number;
  presence_rate: number;
  filtered_count?: number;
}

// ── Sub-components ─────────────────────────────────────────────────────────────

/** Grande carte de stat utilisée dans l'onglet Présences */
function StatCard({
  label, value, color, percent,
}: {
  label: string; value: number | string; color: string; percent?: string;
}) {
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
        fontSize: 11.5, color: 'oklch(0.55 0.020 225)',
        fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.07em',
        marginBottom: 10,
      }}>{label}</p>
      <p style={{
        fontSize: 30, fontWeight: 800, color, letterSpacing: '-1.5px', lineHeight: 1,
      }}>{value}</p>
      {percent !== undefined && (
        <p style={{ fontSize: 11, color: 'oklch(0.50 0.018 225)', marginTop: 6 }}>
          {percent}% du total
        </p>
      )}
    </div>
  );
}

/** Carte aperçu général — visible sur tous les onglets */
function OverviewCard({ icon, label, value, color }: {
  icon: string; label: string; value: number | string; color: string;
}) {
  return (
    <div style={{
      background: 'oklch(0.11 0.014 240)',
      border: '1px solid oklch(0.19 0.018 240)',
      borderRadius: 12, padding: '18px 20px',
      display: 'flex', alignItems: 'center', gap: 16,
      position: 'relative', overflow: 'hidden',
    }}>
      {/* Barre accent gauche */}
      <div style={{
        position: 'absolute', top: 0, left: 0, bottom: 0, width: 3,
        background: color,
      }} />
      <div style={{
        width: 46, height: 46, borderRadius: 12,
        background: `${color}1A`,
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        fontSize: 22, flexShrink: 0,
      }}>{icon}</div>
      <div>
        <p style={{
          fontSize: 26, fontWeight: 800, color,
          letterSpacing: '-1px', lineHeight: 1,
        }}>
          {typeof value === 'number' ? value.toLocaleString('fr-FR') : value}
        </p>
        <p style={{
          fontSize: 11.5, color: 'oklch(0.55 0.020 225)',
          fontWeight: 500, marginTop: 4,
        }}>{label}</p>
      </div>
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

export default function AdminDashboard() {
  const navigate = useNavigate();
  const { user, isAuthenticated, logout } = useAuthContext();

  const [stats, setStats]               = useState<AttendanceStats | null>(null);
  const [attendances, setAttendances]   = useState<any[]>([]);
  const [isLoading, setIsLoading]       = useState(true);
  const [error, setError]               = useState<string | null>(null);
  const [filter, setFilter]             = useState<'all' | 'present' | 'late' | 'absent'>('all');
  const [activeTab, setActiveTab]       = useState<'students' | 'filieres' | 'attendance'>('students');

  // Compteurs pour le bloc d'aperçu général
  const [studentsTotal, setStudentsTotal] = useState<number>(0);
  const [filieresTotal, setFilieresTotal] = useState<number>(0);

  // ── Charge les présences + stats (se relance quand le filtre change) ──
  useEffect(() => {
    if (!isAuthenticated || user?.role !== 'admin') {
      navigate('/login');
      return;
    }
    const universityId = api.getUniversityId()
      ?? (user as any)?.universityId
      ?? (user as any)?.university_id;
    if (!universityId) return;

    const loadAttendances = async () => {
      try {
        setIsLoading(true);
        const response = await api.getAttendances(universityId, {
          status: filter === 'all' ? undefined : filter,
          limit: 100,
        });
        setAttendances(response.data ?? []);
        setStats(response.statistics ?? null);
      } catch (err: any) {
        setError(err.message || 'Impossible de charger les présences');
      } finally {
        setIsLoading(false);
      }
    };

    loadAttendances();
  }, [isAuthenticated, navigate, user, filter]);

  // ── Charge les compteurs d'aperçu une seule fois (pas à chaque filtre) ──
  useEffect(() => {
    if (!isAuthenticated || user?.role !== 'admin') return;
    const universityId = api.getUniversityId()
      ?? (user as any)?.universityId
      ?? (user as any)?.university_id;
    if (!universityId) return;

    (async () => {
      try {
        const [studentsRes, filieresRes] = await Promise.all([
          api.getStudents(universityId),
          api.getFilieres(universityId),
        ]);

        // getStudents retourne { data: [...], pagination: { total } }
        setStudentsTotal(
          studentsRes.pagination?.total ??
          (Array.isArray(studentsRes.data) ? studentsRes.data.length : 0)
        );

        // getFilieres retourne { data: [...] }
        const fList = Array.isArray(filieresRes.data) ? filieresRes.data : [];
        setFilieresTotal(fList.length);
      } catch {
        // Non critique — l'aperçu affichera simplement 0
      }
    })();
  }, [isAuthenticated, user]);

  const handleLogout = async () => {
    try { await logout(); navigate('/login'); }
    catch (err) { console.error('Logout error:', err); }
  };

  if (!isAuthenticated || user?.role !== 'admin') return null;

  const universityId = api.getUniversityId()
    ?? (user as any)?.universityId
    ?? (user as any)?.university_id;

  const tabs = [
    { id: 'students'   as const, label: 'Étudiants', icon: '👨‍🎓' },
    { id: 'filieres'   as const, label: 'Filières',  icon: '🎓'  },
    { id: 'attendance' as const, label: 'Présences', icon: '📋'  },
  ];

  const filterColors: Record<string, string> = {
    all: '#5BA3E8', present: '#2DB87A', late: '#D4A017', absent: '#E55C5C',
  };
  const filterLabels: Record<string, string> = {
    all: 'Tous', present: 'Présents', late: 'En retard', absent: 'Absents',
  };

  const presenceRate = stats?.presence_rate ?? 0;

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
        .tab-content { animation: slideUp 0.35s cubic-bezier(0.16,1,0.3,1) both; }
        select option { background: #0E1117; color: #DDE6F0; }
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
          {/* Brand */}
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
              <p style={{
                fontSize: 15, fontWeight: 700,
                color: 'oklch(0.90 0.015 220)', letterSpacing: '-0.3px',
              }}>
                Les Cours Sonou
              </p>
              <p style={{ fontSize: 11, color: 'oklch(0.50 0.018 225)', marginTop: -1 }}>
                Portail Administrateur
              </p>
            </div>
          </div>

          {/* User + Logout */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 20 }}>
            <div style={{ textAlign: 'right' }}>
              <p style={{ fontSize: 13.5, fontWeight: 600, color: 'oklch(0.90 0.015 220)' }}>
                {(user as any)?.firstName || (user as any)?.lastName || user?.email}
              </p>
              <p style={{ fontSize: 11, color: '#E8A020', marginTop: -1 }}>Administrateur</p>
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

        {/* ── APERÇU GÉNÉRAL (toujours visible) ── */}
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))',
          gap: 14,
          marginBottom: 28,
        }}>
          <OverviewCard icon="👨‍🎓" label="Étudiants inscrits" value={studentsTotal} color="#5BA3E8" />
          <OverviewCard icon="🎓"  label="Filières actives"   value={filieresTotal} color="#E8A020" />
          <OverviewCard icon="📋"  label="Entrées présences"  value={stats?.total_count ?? 0} color="#9B72E8" />
          <OverviewCard
            icon="✅"
            label="Taux de présence"
            value={`${presenceRate.toFixed(1)}%`}
            color="#2DB87A"
          />
        </div>

        {/* Tab navigation */}
        <nav style={{
          display: 'flex', gap: 4, padding: 4,
          background: 'oklch(0.11 0.014 240)',
          border: '1px solid oklch(0.19 0.018 240)',
          borderRadius: 14, width: 'fit-content', marginBottom: 32,
        }}>
          {tabs.map(tab => {
            const active = activeTab === tab.id;
            return (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                style={{
                  padding: '9px 22px', borderRadius: 10,
                  fontSize: 13.5, fontWeight: 600,
                  cursor: 'pointer', transition: 'all 0.2s', border: 'none',
                  background: active ? '#E8A020' : 'transparent',
                  color: active ? 'oklch(0.10 0.010 240)' : 'oklch(0.55 0.020 225)',
                  boxShadow: active ? '0 0 16px rgba(232,160,32,0.35)' : 'none',
                  display: 'flex', alignItems: 'center', gap: 6,
                  fontFamily: 'inherit',
                }}
              >
                <span style={{ fontSize: 15 }}>{tab.icon}</span>
                {tab.label}
              </button>
            );
          })}
        </nav>

        {/* Tab content */}
        <div key={activeTab} className="tab-content">

          {/* ── Students ── */}
          {activeTab === 'students' && (
            universityId
              ? <StudentsPage universityId={universityId} />
              : <p style={{ color: 'oklch(0.55 0.020 225)', fontSize: 13 }}>Université non configurée.</p>
          )}

          {/* ── Filières ── */}
          {activeTab === 'filieres' && (
            universityId
              ? <FilieresPage universityId={universityId} />
              : <p style={{ color: 'oklch(0.55 0.020 225)', fontSize: 13 }}>Université non configurée.</p>
          )}

          {/* ── Attendance ── */}
          {activeTab === 'attendance' && (
            <div style={{ display: 'flex', flexDirection: 'column', gap: 24 }}>

              {error && (
                <div style={{
                  background: 'rgba(229,92,92,0.10)', border: '1px solid rgba(229,92,92,0.25)',
                  color: '#E55C5C', padding: '12px 16px', borderRadius: 10, fontSize: 13,
                }}>{error}</div>
              )}

              {isLoading ? (
                <div style={{
                  display: 'flex', justifyContent: 'center', alignItems: 'center',
                  height: 240, flexDirection: 'column', gap: 14,
                }}>
                  <div style={{
                    width: 36, height: 36, borderRadius: '50%',
                    border: '3px solid rgba(232,160,32,0.15)',
                    borderTop: '3px solid #E8A020',
                    animation: 'spin 0.8s linear infinite',
                  }} />
                  <p style={{ fontSize: 13, color: 'oklch(0.55 0.020 225)' }}>Chargement…</p>
                </div>
              ) : (
                <>
                  {/* Stats grid */}
                  {stats && (
                    <div style={{
                      display: 'grid',
                      gridTemplateColumns: 'repeat(auto-fit, minmax(170px, 1fr))',
                      gap: 16,
                    }}>
                      <StatCard
                        label="Total (université)"
                        value={stats.total_count}
                        color="#5BA3E8"
                      />
                      <StatCard
                        label="Présents"
                        value={stats.present_count}
                        color="#2DB87A"
                        percent={
                          stats.total_count > 0
                            ? ((stats.present_count / stats.total_count) * 100).toFixed(1)
                            : '0'
                        }
                      />
                      <StatCard
                        label="En retard"
                        value={stats.late_count}
                        color="#D4A017"
                        percent={
                          stats.total_count > 0
                            ? ((stats.late_count / stats.total_count) * 100).toFixed(1)
                            : '0'
                        }
                      />
                      <StatCard
                        label="Absents"
                        value={stats.absent_count}
                        color="#E55C5C"
                        percent={
                          stats.total_count > 0
                            ? ((stats.absent_count / stats.total_count) * 100).toFixed(1)
                            : '0'
                        }
                      />
                      <StatCard
                        label="Taux de présence"
                        value={`${presenceRate.toFixed(1)}%`}
                        color="#E8A020"
                      />
                    </div>
                  )}

                  {/* Filter bar */}
                  <div style={{
                    background: 'oklch(0.11 0.014 240)',
                    border: '1px solid oklch(0.19 0.018 240)',
                    borderRadius: 12, padding: '16px 20px',
                  }}>
                    <p style={{
                      fontSize: 11, fontWeight: 600, color: 'oklch(0.50 0.018 225)',
                      textTransform: 'uppercase', letterSpacing: '0.08em', marginBottom: 12,
                    }}>Filtrer par statut</p>
                    <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                      {(['all', 'present', 'late', 'absent'] as const).map(s => {
                        const active = filter === s;
                        const c = filterColors[s];
                        return (
                          <button
                            key={s} onClick={() => setFilter(s)}
                            style={{
                              padding: '7px 18px', borderRadius: 8,
                              fontSize: 12.5, fontWeight: 600, cursor: 'pointer',
                              transition: 'all 0.2s', border: 'none', fontFamily: 'inherit',
                              background: active ? `${c}22` : 'oklch(0.14 0.016 240)',
                              color: active ? c : 'oklch(0.55 0.020 225)',
                              outline: active ? `1px solid ${c}44` : '1px solid transparent',
                            }}
                          >{filterLabels[s]}</button>
                        );
                      })}
                    </div>
                  </div>

                  {/* Table */}
                  <div style={{
                    background: 'oklch(0.11 0.014 240)',
                    border: '1px solid oklch(0.19 0.018 240)',
                    borderRadius: 12, overflow: 'hidden',
                  }}>
                    <div style={{
                      padding: '16px 20px',
                      borderBottom: '1px solid oklch(0.19 0.018 240)',
                      display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    }}>
                      <p style={{ fontSize: 15, fontWeight: 700, color: 'oklch(0.90 0.015 220)' }}>
                        Registre de présences
                      </p>
                      <span style={{
                        fontSize: 11, padding: '3px 10px', borderRadius: 20,
                        background: 'rgba(91,163,232,0.10)', color: '#5BA3E8',
                        border: '1px solid rgba(91,163,232,0.20)', fontWeight: 600,
                      }}>{attendances.length} enregistrements</span>
                    </div>

                    {attendances.length === 0 ? (
                      <div style={{
                        textAlign: 'center', padding: '48px 24px',
                        color: 'oklch(0.55 0.020 225)', fontSize: 13,
                      }}>
                        Aucun enregistrement trouvé
                      </div>
                    ) : (
                      <div style={{ overflowX: 'auto' }}>
                        <table style={{ width: '100%', fontSize: 13, borderCollapse: 'collapse' }}>
                          <thead>
                            <tr style={{ borderBottom: '1px solid oklch(0.19 0.018 240)' }}>
                              {['Étudiant', 'Cours', 'Date & heure', 'Statut', 'Vérification'].map(h => (
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
                                style={{
                                  borderBottom: '1px solid oklch(0.14 0.015 240)',
                                  transition: 'background 0.15s',
                                }}
                                onMouseEnter={e => (e.currentTarget.style.background = 'oklch(0.13 0.015 240)')}
                                onMouseLeave={e => (e.currentTarget.style.background = 'transparent')}
                              >
                                <td style={{ padding: '12px 16px' }}>
                                  <p style={{ fontWeight: 600, color: 'oklch(0.90 0.015 220)' }}>
                                    {record.student_name}
                                  </p>
                                  <p style={{
                                    fontSize: 11, color: 'oklch(0.50 0.018 225)',
                                    fontFamily: "'IBM Plex Mono', monospace",
                                  }}>{record.student_registration}</p>
                                </td>
                                <td style={{ padding: '12px 16px' }}>
                                  <p style={{ fontWeight: 500, color: 'oklch(0.85 0.015 220)' }}>
                                    {record.course_name}
                                  </p>
                                  <p style={{ fontSize: 11, color: 'oklch(0.50 0.018 225)' }}>
                                    {record.course_code}
                                  </p>
                                </td>
                                <td style={{
                                  padding: '12px 16px', color: 'oklch(0.55 0.020 225)',
                                  fontFamily: "'IBM Plex Mono', monospace", fontSize: 12,
                                }}>
                                  {record.scanned_at
                                    ? <>
                                        {new Date(record.scanned_at).toLocaleDateString('fr-FR')}
                                        {' '}
                                        {new Date(record.scanned_at).toLocaleTimeString('fr-FR')}
                                      </>
                                    : '—'
                                  }
                                </td>
                                <td style={{ padding: '12px 16px' }}>
                                  <StatusBadge status={record.status} />
                                </td>
                                <td style={{ padding: '12px 16px' }}>
                                  <span style={{
                                    fontSize: 11, padding: '3px 8px', borderRadius: 6,
                                    background: 'rgba(91,163,232,0.10)', color: '#5BA3E8',
                                    border: '1px solid rgba(91,163,232,0.20)',
                                    fontFamily: "'IBM Plex Mono', monospace",
                                  }}>{record.verification_method ?? '—'}</span>
                                </td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    )}
                  </div>
                </>
              )}
            </div>
          )}
        </div>
      </main>
    </div>
  );
}
