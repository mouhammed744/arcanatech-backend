import { useEffect, useState, useCallback } from 'react';
import { api } from '~/lib/api';

// ── Types ──────────────────────────────────────────────────────────────────────

interface Filiere {
  id: number;
  code: string;
  name: string;
  description: string | null;
  level: string | null;
  department: string | null;
  isActive: boolean;
  studentsCount: number;
}

interface StudentInFiliere {
  id: number;
  registration_number: string;
  name: string;
  email: string;
  level: string;
  has_rfid: boolean;
  rfid_active: boolean;
}

interface FiliereDetail extends Filiere {
  students: StudentInFiliere[];
}

const LEVEL_LABELS: Record<string, string> = {
  L1: 'Licence 1', L2: 'Licence 2', L3: 'Licence 3',
  M1: 'Master 1',  M2: 'Master 2',  D:  'Doctorat',
};

const AVATAR_GRADIENTS = [
  'linear-gradient(135deg, #E8A020, #C88A10)',
  'linear-gradient(135deg, #5BA3E8, #3A7EC8)',
  'linear-gradient(135deg, #2DB87A, #1E9A62)',
  'linear-gradient(135deg, #9B78EB, #7850C8)',
  'linear-gradient(135deg, #E55C5C, #C84040)',
];

interface FilieresPageProps {
  universityId: number;
}

// ── Main Component ─────────────────────────────────────────────────────────────

export default function FilieresPage({ universityId }: FilieresPageProps) {
  const [filieres, setFilieres]           = useState<Filiere[]>([]);
  const [selected, setSelected]           = useState<FiliereDetail | null>(null);
  const [loadingList, setLoadingList]     = useState(true);
  const [loadingDetail, setLoadingDetail] = useState(false);
  const [error, setError]                 = useState<string | null>(null);
  const [search, setSearch]               = useState('');

  const loadFilieres = useCallback(async () => {
    try {
      setLoadingList(true);
      setError(null);
      const resp = await api.getFilieres(universityId);
      setFilieres(resp.data || []);
    } catch (e: any) {
      setError(e.message || 'Erreur chargement');
    } finally {
      setLoadingList(false);
    }
  }, [universityId]);

  useEffect(() => { loadFilieres(); }, [loadFilieres]);

  const openFiliere = async (f: Filiere) => {
    setLoadingDetail(true);
    setSelected(null);
    try {
      const resp = await api.getFiliere(universityId, f.id);
      setSelected({ ...f, students: resp.students ?? [] });
    } catch {
      setSelected({ ...f, students: [] });
    } finally {
      setLoadingDetail(false);
    }
  };

  const filtered = filieres.filter(f =>
    f.name.toLowerCase().includes(search.toLowerCase()) ||
    f.code.toLowerCase().includes(search.toLowerCase())
  );

  // ── Detail View ──────────────────────────────────────────────────────────────

  if (selected || loadingDetail) {
    return (
      <div style={{ display: 'flex', flexDirection: 'column', gap: 24, fontFamily: "'Plus Jakarta Sans', sans-serif" }}>
        <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>

        {/* Breadcrumb */}
        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
          <button
            onClick={() => setSelected(null)}
            style={{
              display: 'flex', alignItems: 'center', gap: 6,
              fontSize: 13, fontWeight: 600, color: '#E8A020',
              background: 'none', border: 'none', cursor: 'pointer',
              padding: '5px 0', fontFamily: 'inherit',
              transition: 'opacity 0.2s',
            }}
            onMouseEnter={e => (e.currentTarget.style.opacity = '0.7')}
            onMouseLeave={e => (e.currentTarget.style.opacity = '1')}
          >
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M15 19l-7-7 7-7" />
            </svg>
            Toutes les filières
          </button>
          {selected && (
            <>
              <span style={{ color: 'oklch(0.35 0.015 240)', fontSize: 14 }}>/</span>
              <span style={{ fontSize: 13, fontWeight: 600, color: 'oklch(0.80 0.015 220)' }}>
                {selected.name}
              </span>
            </>
          )}
        </div>

        {loadingDetail ? (
          <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: 240, flexDirection: 'column', gap: 14 }}>
            <div style={{
              width: 36, height: 36, borderRadius: '50%',
              border: '3px solid rgba(232,160,32,0.15)', borderTop: '3px solid #E8A020',
              animation: 'spin 0.8s linear infinite',
            }} />
            <p style={{ fontSize: 13, color: 'oklch(0.55 0.020 225)' }}>Chargement de la filière…</p>
          </div>
        ) : selected && (
          <>
            {/* Filière hero card */}
            <div style={{
              position: 'relative', borderRadius: 14, padding: '28px 28px',
              background: 'linear-gradient(135deg, oklch(0.14 0.016 240) 0%, oklch(0.12 0.020 255) 100%)',
              border: '1px solid oklch(0.22 0.022 240)',
              overflow: 'hidden',
            }}>
              {/* Gold top edge */}
              <div style={{
                position: 'absolute', top: 0, left: 0, right: 0, height: 3,
                background: 'linear-gradient(90deg, #E8A020 0%, rgba(232,160,32,0.2) 60%, transparent 100%)',
              }} />
              {/* Decorative glow */}
              <div style={{
                position: 'absolute', top: -40, right: -40, width: 180, height: 180,
                borderRadius: '50%',
                background: 'radial-gradient(circle, rgba(232,160,32,0.08) 0%, transparent 70%)',
                pointerEvents: 'none',
              }} />

              <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between' }}>
                <div style={{ flex: 1 }}>
                  <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 10 }}>
                    <span style={{
                      background: 'rgba(232,160,32,0.15)', color: '#E8A020',
                      border: '1px solid rgba(232,160,32,0.30)',
                      fontSize: 12, fontWeight: 700, padding: '4px 10px', borderRadius: 20,
                      fontFamily: "'IBM Plex Mono', monospace",
                    }}>{selected.code}</span>
                    {selected.level && (
                      <span style={{
                        background: 'rgba(91,163,232,0.12)', color: '#5BA3E8',
                        border: '1px solid rgba(91,163,232,0.22)',
                        fontSize: 12, fontWeight: 600, padding: '4px 10px', borderRadius: 20,
                      }}>{LEVEL_LABELS[selected.level] ?? selected.level}</span>
                    )}
                    {!selected.isActive && (
                      <span style={{
                        background: 'oklch(0.17 0.018 240)', color: 'oklch(0.50 0.018 225)',
                        border: '1px solid oklch(0.22 0.020 240)',
                        fontSize: 12, fontWeight: 600, padding: '4px 10px', borderRadius: 20,
                      }}>Inactive</span>
                    )}
                  </div>
                  <h2 style={{
                    fontSize: 22, fontWeight: 800, color: 'oklch(0.92 0.015 220)',
                    letterSpacing: '-0.5px', marginBottom: 4,
                  }}>{selected.name}</h2>
                  {selected.department && (
                    <p style={{ fontSize: 13, color: 'oklch(0.55 0.020 225)' }}>{selected.department}</p>
                  )}
                  {selected.description && (
                    <p style={{ fontSize: 12.5, color: 'oklch(0.50 0.018 225)', marginTop: 6 }}>{selected.description}</p>
                  )}
                </div>
                {/* Student count */}
                <div style={{ textAlign: 'right', flexShrink: 0, marginLeft: 24 }}>
                  <p style={{
                    fontSize: 40, fontWeight: 900, color: '#E8A020',
                    letterSpacing: '-2px', lineHeight: 1,
                    textShadow: '0 0 30px rgba(232,160,32,0.4)',
                  }}>{selected.students.length}</p>
                  <p style={{ fontSize: 12, color: 'oklch(0.55 0.020 225)', marginTop: 4 }}>
                    étudiant{selected.students.length !== 1 ? 's' : ''} inscrit{selected.students.length !== 1 ? 's' : ''}
                  </p>
                </div>
              </div>
            </div>

            {/* Students section */}
            <div>
              <h3 style={{
                fontSize: 11, fontWeight: 700, textTransform: 'uppercase' as const,
                letterSpacing: '0.08em', color: 'oklch(0.55 0.020 225)', marginBottom: 14,
              }}>
                Étudiants dans cette filière
              </h3>

              {selected.students.length === 0 ? (
                <div style={{
                  textAlign: 'center', padding: '56px 24px',
                  background: 'oklch(0.11 0.014 240)',
                  border: '1px solid oklch(0.19 0.018 240)', borderRadius: 12,
                }}>
                  <div style={{
                    width: 52, height: 52, borderRadius: '50%',
                    background: 'oklch(0.14 0.016 240)',
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    margin: '0 auto 14px',
                  }}>
                    <svg width="24" height="24" fill="none" stroke="oklch(0.50 0.018 225)" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                  </div>
                  <p style={{ color: 'oklch(0.70 0.015 220)', fontSize: 14, fontWeight: 600 }}>
                    Aucun étudiant dans cette filière
                  </p>
                  <p style={{ color: 'oklch(0.45 0.015 225)', fontSize: 12.5, marginTop: 6 }}>
                    Les étudiants inscrits via l'app mobile apparaîtront ici
                  </p>
                </div>
              ) : (
                <div style={{
                  background: 'oklch(0.11 0.014 240)',
                  border: '1px solid oklch(0.19 0.018 240)',
                  borderRadius: 12, overflow: 'hidden',
                }}>
                  <table style={{ width: '100%', fontSize: 13, borderCollapse: 'collapse' }}>
                    <thead>
                      <tr style={{ background: 'oklch(0.095 0.013 240)', borderBottom: '1px solid oklch(0.19 0.018 240)' }}>
                        {['Étudiant', 'Matricule', 'Niveau', 'Carte RFID'].map(h => (
                          <th key={h} style={{
                            textAlign: 'left', padding: '11px 16px',
                            fontSize: 10.5, fontWeight: 700, textTransform: 'uppercase',
                            letterSpacing: '0.08em', color: 'oklch(0.50 0.018 225)',
                          }}>{h}</th>
                        ))}
                      </tr>
                    </thead>
                    <tbody>
                      {selected.students.map(student => {
                        const grad = AVATAR_GRADIENTS[student.id % AVATAR_GRADIENTS.length];
                        const initials = student.name?.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase() ?? '?';
                        return (
                          <tr
                            key={student.id}
                            style={{ borderBottom: '1px solid oklch(0.14 0.015 240)', transition: 'background 0.15s' }}
                            onMouseEnter={e => (e.currentTarget.style.background = 'oklch(0.13 0.015 240)')}
                            onMouseLeave={e => (e.currentTarget.style.background = 'transparent')}
                          >
                            <td style={{ padding: '13px 16px' }}>
                              <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                                <div style={{
                                  width: 32, height: 32, borderRadius: 8, flexShrink: 0,
                                  background: grad,
                                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                                  fontSize: 11, fontWeight: 800, color: 'oklch(0.08 0.010 240)',
                                }}>{initials}</div>
                                <div>
                                  <p style={{ fontWeight: 600, color: 'oklch(0.90 0.015 220)' }}>{student.name}</p>
                                  <p style={{ fontSize: 11, color: 'oklch(0.50 0.018 225)' }}>{student.email}</p>
                                </div>
                              </div>
                            </td>
                            <td style={{ padding: '13px 16px' }}>
                              <span style={{
                                fontFamily: "'IBM Plex Mono', monospace", fontSize: 11.5,
                                background: 'rgba(232,160,32,0.10)', color: '#E8A020',
                                border: '1px solid rgba(232,160,32,0.22)',
                                padding: '3px 8px', borderRadius: 6,
                              }}>{student.registration_number || '—'}</span>
                            </td>
                            <td style={{ padding: '13px 16px' }}>
                              <span style={{
                                fontSize: 12.5, color: 'oklch(0.70 0.015 220)', fontWeight: 500,
                              }}>
                                {LEVEL_LABELS[student.level] ?? student.level ?? '—'}
                              </span>
                            </td>
                            <td style={{ padding: '13px 16px' }}>
                              {student.has_rfid ? (
                                <span style={{
                                  display: 'flex', alignItems: 'center', gap: 6,
                                  fontSize: 12, fontWeight: 500,
                                  color: student.rfid_active ? '#2DB87A' : 'oklch(0.50 0.018 225)',
                                }}>
                                  <span style={{
                                    width: 7, height: 7, borderRadius: '50%',
                                    background: student.rfid_active ? '#2DB87A' : 'oklch(0.35 0.015 225)',
                                    boxShadow: student.rfid_active ? '0 0 6px rgba(45,184,122,0.5)' : 'none',
                                  }} />
                                  {student.rfid_active ? 'Active' : 'Inactive'}
                                </span>
                              ) : (
                                <span style={{ color: 'oklch(0.40 0.015 225)', fontSize: 12, fontStyle: 'italic' }}>
                                  Aucune
                                </span>
                              )}
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </>
        )}
      </div>
    );
  }

  // ── List View ────────────────────────────────────────────────────────────────

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20, fontFamily: "'Plus Jakarta Sans', sans-serif" }}>
      <style>{`
        @keyframes spin { to { transform: rotate(360deg); } }
        .filiere-card { transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s; }
        .filiere-card:hover {
          border-color: rgba(232,160,32,0.35) !important;
          box-shadow: 0 0 24px rgba(232,160,32,0.10), 0 4px 20px rgba(0,0,0,0.3) !important;
          transform: translateY(-2px);
        }
      `}</style>

      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <div>
          <h2 style={{ fontSize: 20, fontWeight: 700, color: 'oklch(0.90 0.015 220)', letterSpacing: '-0.4px' }}>
            Filières
          </h2>
          <p style={{ fontSize: 12.5, color: 'oklch(0.55 0.020 225)', marginTop: 3 }}>
            <span style={{ color: '#E8A020', fontWeight: 700 }}>{filtered.length}</span>
            {' '}filière{filtered.length !== 1 ? 's' : ''}
          </p>
        </div>
        <button
          onClick={loadFilieres}
          style={{
            display: 'flex', alignItems: 'center', gap: 7,
            padding: '7px 14px', fontSize: 12.5, fontFamily: 'inherit', fontWeight: 500,
            background: 'oklch(0.11 0.014 240)',
            border: '1px solid oklch(0.19 0.018 240)',
            borderRadius: 8, cursor: 'pointer', color: 'oklch(0.55 0.020 225)',
            transition: 'all 0.2s',
          }}
          onMouseEnter={e => { e.currentTarget.style.borderColor = 'rgba(232,160,32,0.4)'; e.currentTarget.style.color = '#E8A020'; }}
          onMouseLeave={e => { e.currentTarget.style.borderColor = 'oklch(0.19 0.018 240)'; e.currentTarget.style.color = 'oklch(0.55 0.020 225)'; }}
        >
          <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Actualiser
        </button>
      </div>

      {/* Search */}
      <input
        type="text"
        placeholder="Rechercher une filière par nom ou code…"
        value={search}
        onChange={e => setSearch(e.target.value)}
        style={{
          background: 'oklch(0.11 0.014 240)',
          border: '1px solid oklch(0.19 0.018 240)',
          borderRadius: 8, padding: '9px 14px', fontSize: 13,
          color: 'oklch(0.90 0.015 220)', outline: 'none',
          fontFamily: "'Plus Jakarta Sans', sans-serif",
          transition: 'border-color 0.2s', width: '100%', maxWidth: 380,
          colorScheme: 'dark',
        } as React.CSSProperties}
        onFocus={e => (e.target.style.borderColor = 'rgba(232,160,32,0.5)')}
        onBlur={e  => (e.target.style.borderColor = 'oklch(0.19 0.018 240)')}
      />

      {/* Error */}
      {error && (
        <div style={{
          background: 'rgba(229,92,92,0.10)', border: '1px solid rgba(229,92,92,0.25)',
          color: '#E55C5C', padding: '12px 16px', borderRadius: 10, fontSize: 13,
        }}>
          {error}
          <button onClick={loadFilieres} style={{
            marginLeft: 12, color: '#E8A020', background: 'none',
            border: 'none', textDecoration: 'underline', cursor: 'pointer', fontSize: 13,
          }}>Réessayer</button>
        </div>
      )}

      {/* Loading */}
      {loadingList ? (
        <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: 200, flexDirection: 'column', gap: 14 }}>
          <div style={{
            width: 36, height: 36, borderRadius: '50%',
            border: '3px solid rgba(232,160,32,0.15)', borderTop: '3px solid #E8A020',
            animation: 'spin 0.8s linear infinite',
          }} />
          <p style={{ fontSize: 13, color: 'oklch(0.55 0.020 225)' }}>Chargement des filières…</p>
        </div>
      ) : filtered.length === 0 ? (
        <div style={{
          textAlign: 'center', padding: '56px 24px',
          background: 'oklch(0.11 0.014 240)',
          border: '1px solid oklch(0.19 0.018 240)', borderRadius: 12,
        }}>
          <p style={{ color: 'oklch(0.55 0.020 225)', fontSize: 14 }}>Aucune filière trouvée</p>
        </div>
      ) : (
        /* Grid */
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))', gap: 16 }}>
          {filtered.map(f => (
            <button
              key={f.id}
              onClick={() => openFiliere(f)}
              className="filiere-card"
              style={{
                textAlign: 'left', background: 'oklch(0.11 0.014 240)',
                border: '1px solid oklch(0.19 0.018 240)',
                borderRadius: 12, padding: '20px', cursor: 'pointer',
                display: 'flex', flexDirection: 'column', gap: 0,
                position: 'relative', overflow: 'hidden',
                fontFamily: 'inherit',
              }}
            >
              {/* Top accent */}
              <div style={{
                position: 'absolute', top: 0, left: 0, right: 0, height: 2,
                background: 'linear-gradient(90deg, rgba(232,160,32,0.6) 0%, transparent 60%)',
                opacity: f.isActive ? 1 : 0.3,
              }} />

              {/* Header row */}
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
                <span style={{
                  background: 'rgba(232,160,32,0.12)', color: '#E8A020',
                  border: '1px solid rgba(232,160,32,0.25)',
                  fontSize: 11.5, fontWeight: 700, padding: '3px 9px', borderRadius: 6,
                  fontFamily: "'IBM Plex Mono', monospace",
                }}>{f.code}</span>
                {!f.isActive && (
                  <span style={{
                    background: 'oklch(0.14 0.016 240)', color: 'oklch(0.50 0.018 225)',
                    fontSize: 11, fontWeight: 600, padding: '2px 8px', borderRadius: 6,
                  }}>Inactive</span>
                )}
              </div>

              {/* Name */}
              <h3 style={{
                fontSize: 14, fontWeight: 700, color: 'oklch(0.88 0.015 220)',
                letterSpacing: '-0.2px', marginBottom: 4, lineHeight: 1.35,
              }}>{f.name}</h3>

              {f.department && (
                <p style={{ fontSize: 12, color: 'oklch(0.50 0.018 225)', marginBottom: 4 }}>{f.department}</p>
              )}
              {f.level && (
                <p style={{
                  fontSize: 11.5, color: '#5BA3E8',
                  marginBottom: 0,
                }}>{LEVEL_LABELS[f.level] ?? f.level}</p>
              )}

              {/* Footer */}
              <div style={{
                display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                marginTop: 16, paddingTop: 14,
                borderTop: '1px solid oklch(0.17 0.016 240)',
              }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                  <svg width="14" height="14" fill="none" stroke="oklch(0.50 0.018 225)" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span style={{ fontSize: 13, fontWeight: 700, color: 'oklch(0.80 0.015 220)' }}>
                    {f.studentsCount}
                  </span>
                  <span style={{ fontSize: 11.5, color: 'oklch(0.50 0.018 225)' }}>
                    étudiant{f.studentsCount !== 1 ? 's' : ''}
                  </span>
                </div>
                <svg width="14" height="14" fill="none" stroke="oklch(0.45 0.018 225)" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
