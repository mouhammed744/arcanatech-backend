import { useEffect, useState, useCallback } from 'react';
import { api } from '~/lib/api';

// ── Types ──────────────────────────────────────────────────────────────────────

interface Filiere {
  id: number;
  name: string;
  code: string;
}

interface RfidCard {
  id: string;
  card_number: string;
  is_active: boolean;
  last_scanned_at?: string | null;
}

interface Student {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  registration_number: string;
  level: string;
  filiere: Filiere | null;
  registered_at: string | null;
  enrollment_count: number;
  rfid_card: RfidCard | null;
}

const LEVEL_LABELS: Record<string, string> = {
  L1: 'Licence 1', L2: 'Licence 2', L3: 'Licence 3',
  M1: 'Master 1',  M2: 'Master 2',  D:  'Doctorat',
};

const LEVEL_STYLES: Record<string, { bg: string; color: string }> = {
  L1: { bg: 'rgba(91,163,232,0.12)',   color: '#5BA3E8' },
  L2: { bg: 'rgba(91,163,232,0.12)',   color: '#5BA3E8' },
  L3: { bg: 'rgba(91,163,232,0.12)',   color: '#5BA3E8' },
  M1: { bg: 'rgba(155,120,235,0.12)', color: '#9B78EB' },
  M2: { bg: 'rgba(155,120,235,0.12)', color: '#9B78EB' },
  D:  { bg: 'rgba(232,160,32,0.12)',  color: '#E8A020' },
};

const AVATAR_GRADIENTS = [
  'linear-gradient(135deg, #E8A020, #C88A10)',
  'linear-gradient(135deg, #5BA3E8, #3A7EC8)',
  'linear-gradient(135deg, #2DB87A, #1E9A62)',
  'linear-gradient(135deg, #9B78EB, #7850C8)',
  'linear-gradient(135deg, #E55C5C, #C84040)',
];

interface StudentsPageProps {
  universityId: number;
}

// ── Component ──────────────────────────────────────────────────────────────────

// ── Modale RFID ───────────────────────────────────────────────────────────────

interface RfidModalProps {
  student: Student;
  universityId: number;
  onClose: () => void;
  onSuccess: (studentId: number, card: RfidCard | null) => void;
}

function RfidModal({ student, universityId, onClose, onSuccess }: RfidModalProps) {
  const [cardNumber, setCardNumber]   = useState('');
  const [isSaving, setIsSaving]       = useState(false);
  const [isToggling, setIsToggling]   = useState(false);
  const [isDeleting, setIsDeleting]   = useState(false);
  const [feedback, setFeedback]       = useState<{ type: 'ok' | 'err'; msg: string } | null>(null);

  const existing = student.rfid_card;

  const handleAssign = async () => {
    if (!cardNumber.trim()) return;
    setIsSaving(true); setFeedback(null);
    try {
      const res = await api.assignRfidCard(universityId, student.id, cardNumber.trim());
      onSuccess(student.id, {
        id: res.card.id,
        card_number: res.card.card_number,
        is_active: res.card.is_active,
      });
      setFeedback({ type: 'ok', msg: res.message || 'Carte attribuée !' });
      setCardNumber('');
    } catch (e: any) {
      setFeedback({ type: 'err', msg: e.message || "Erreur lors de l'attribution." });
    } finally { setIsSaving(false); }
  };

  const handleToggle = async () => {
    if (!existing) return;
    setIsToggling(true); setFeedback(null);
    try {
      const res = await api.toggleRfidCard(universityId, existing.id);
      onSuccess(student.id, { ...existing, is_active: res.card.is_active });
      setFeedback({ type: 'ok', msg: res.message });
    } catch (e: any) {
      setFeedback({ type: 'err', msg: e.message || 'Erreur.' });
    } finally { setIsToggling(false); }
  };

  const handleDelete = async () => {
    if (!existing || !window.confirm('Supprimer définitivement cette carte RFID ?')) return;
    setIsDeleting(true); setFeedback(null);
    try {
      await api.deleteRfidCard(universityId, existing.id);
      onSuccess(student.id, null);
      setFeedback({ type: 'ok', msg: 'Carte supprimée.' });
    } catch (e: any) {
      setFeedback({ type: 'err', msg: e.message || 'Erreur.' });
    } finally { setIsDeleting(false); }
  };

  const s: React.CSSProperties = {
    fontFamily: "'Plus Jakarta Sans', sans-serif",
    colorScheme: 'dark',
  };

  return (
    <div
      style={{
        position: 'fixed', inset: 0, zIndex: 1000,
        background: 'rgba(0,0,0,0.60)',
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        padding: 16,
      }}
      onClick={onClose}
    >
      <div
        style={{
          ...s,
          background: 'oklch(0.10 0.014 240)',
          border: '1px solid oklch(0.22 0.020 240)',
          borderRadius: 18,
          padding: 28,
          width: '100%', maxWidth: 480,
          boxShadow: '0 24px 60px rgba(0,0,0,0.5)',
        }}
        onClick={e => e.stopPropagation()}
      >
        {/* Header */}
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 20 }}>
          <div>
            <h3 style={{ fontSize: 16, fontWeight: 700, color: 'oklch(0.90 0.015 220)', margin: 0 }}>
              Carte RFID
            </h3>
            <p style={{ fontSize: 12, color: 'oklch(0.50 0.018 225)', marginTop: 3 }}>
              {student.name} · {student.registration_number}
            </p>
          </div>
          <button
            onClick={onClose}
            style={{
              background: 'oklch(0.16 0.016 240)',
              border: '1px solid oklch(0.22 0.020 240)',
              color: 'oklch(0.55 0.020 225)', borderRadius: 8,
              width: 32, height: 32, cursor: 'pointer', fontSize: 16,
              display: 'flex', alignItems: 'center', justifyContent: 'center',
            }}
          >✕</button>
        </div>

        {/* Feedback */}
        {feedback && (
          <div style={{
            padding: '10px 14px', borderRadius: 8, marginBottom: 16, fontSize: 13,
            background: feedback.type === 'ok' ? 'rgba(45,184,122,0.10)' : 'rgba(229,92,92,0.10)',
            border: `1px solid ${feedback.type === 'ok' ? 'rgba(45,184,122,0.30)' : 'rgba(229,92,92,0.30)'}`,
            color: feedback.type === 'ok' ? '#2DB87A' : '#E55C5C',
          }}>
            {feedback.type === 'ok' ? '✓ ' : '✗ '}{feedback.msg}
          </div>
        )}

        {/* Carte existante */}
        {existing && (
          <div style={{
            background: 'oklch(0.13 0.015 240)',
            border: '1px solid oklch(0.20 0.018 240)',
            borderRadius: 12, padding: 16, marginBottom: 20,
          }}>
            <p style={{ fontSize: 11, fontWeight: 700, textTransform: 'uppercase',
              letterSpacing: '0.07em', color: 'oklch(0.50 0.018 225)', marginBottom: 12 }}>
              Carte actuelle
            </p>
            <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
              {/* Icône carte */}
              <div style={{
                width: 40, height: 40, borderRadius: 10,
                background: existing.is_active ? 'rgba(45,184,122,0.12)' : 'rgba(229,92,92,0.12)',
                display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 18,
              }}>
                💳
              </div>
              <div style={{ flex: 1 }}>
                <p style={{
                  fontFamily: "'IBM Plex Mono', monospace", fontSize: 14, fontWeight: 600,
                  color: 'oklch(0.88 0.015 220)', marginBottom: 2,
                }}>
                  {existing.card_number}
                </p>
                <span style={{
                  display: 'inline-flex', alignItems: 'center', gap: 5, fontSize: 11.5,
                  color: existing.is_active ? '#2DB87A' : '#E55C5C',
                }}>
                  <span style={{
                    width: 6, height: 6, borderRadius: '50%',
                    background: existing.is_active ? '#2DB87A' : '#E55C5C',
                    boxShadow: existing.is_active ? '0 0 6px rgba(45,184,122,0.5)' : 'none',
                    display: 'inline-block',
                  }} />
                  {existing.is_active ? 'Active' : 'Inactive'}
                </span>
              </div>
              {/* Actions carte existante */}
              <div style={{ display: 'flex', gap: 6 }}>
                <button
                  onClick={handleToggle}
                  disabled={isToggling}
                  title={existing.is_active ? 'Désactiver' : 'Réactiver'}
                  style={{
                    padding: '6px 12px', borderRadius: 7, fontSize: 11.5, cursor: 'pointer',
                    fontFamily: 'inherit', fontWeight: 600,
                    background: existing.is_active ? 'rgba(232,160,32,0.10)' : 'rgba(45,184,122,0.10)',
                    border: `1px solid ${existing.is_active ? 'rgba(232,160,32,0.30)' : 'rgba(45,184,122,0.30)'}`,
                    color: existing.is_active ? '#E8A020' : '#2DB87A',
                    opacity: isToggling ? 0.6 : 1,
                  }}
                >
                  {isToggling ? '…' : existing.is_active ? 'Désactiver' : 'Réactiver'}
                </button>
                <button
                  onClick={handleDelete}
                  disabled={isDeleting}
                  title="Supprimer la carte"
                  style={{
                    padding: '6px 10px', borderRadius: 7, fontSize: 11.5, cursor: 'pointer',
                    fontFamily: 'inherit', fontWeight: 600,
                    background: 'rgba(229,92,92,0.10)',
                    border: '1px solid rgba(229,92,92,0.30)',
                    color: '#E55C5C',
                    opacity: isDeleting ? 0.6 : 1,
                  }}
                >
                  {isDeleting ? '…' : '🗑'}
                </button>
              </div>
            </div>
          </div>
        )}

        {/* Attribution d'une nouvelle carte */}
        <div>
          <p style={{ fontSize: 12, fontWeight: 600, color: 'oklch(0.65 0.018 225)', marginBottom: 10 }}>
            {existing ? 'Remplacer par une nouvelle carte' : 'Attribuer une carte RFID'}
          </p>

          {/* Hint scan USB */}
          <div style={{
            background: 'rgba(91,163,232,0.06)', border: '1px solid rgba(91,163,232,0.18)',
            borderRadius: 8, padding: '9px 12px', marginBottom: 12,
            display: 'flex', alignItems: 'flex-start', gap: 8,
          }}>
            <span style={{ fontSize: 16 }}>💡</span>
            <p style={{ fontSize: 11.5, color: 'rgba(91,163,232,0.85)', lineHeight: 1.5 }}>
              Placez le curseur dans le champ ci-dessous, puis passez la carte sur le lecteur RFID USB.
              Le numéro sera saisi automatiquement.
            </p>
          </div>

          <div style={{ display: 'flex', gap: 8 }}>
            <input
              type="text"
              placeholder="Numéro de carte (ex: F4A92B1C00)"
              value={cardNumber}
              onChange={e => setCardNumber(e.target.value.toUpperCase())}
              onKeyDown={e => { if (e.key === 'Enter') handleAssign(); }}
              autoFocus
              style={{
                flex: 1, background: 'oklch(0.13 0.015 240)',
                border: '1px solid oklch(0.22 0.020 240)',
                borderRadius: 9, padding: '10px 14px', fontSize: 13,
                color: 'oklch(0.90 0.015 220)', outline: 'none',
                fontFamily: "'IBM Plex Mono', monospace",
                colorScheme: 'dark',
              }}
              onFocus={e => (e.target.style.borderColor = 'rgba(232,160,32,0.55)')}
              onBlur={e  => (e.target.style.borderColor = 'oklch(0.22 0.020 240)')}
            />
            <button
              onClick={handleAssign}
              disabled={isSaving || !cardNumber.trim()}
              style={{
                padding: '10px 18px', borderRadius: 9, fontSize: 13, fontWeight: 700,
                fontFamily: 'inherit', cursor: isSaving || !cardNumber.trim() ? 'not-allowed' : 'pointer',
                background: cardNumber.trim() ? 'linear-gradient(135deg,#E8A020,#C88A10)' : 'oklch(0.16 0.016 240)',
                border: 'none',
                color: cardNumber.trim() ? '#0E0F11' : 'oklch(0.40 0.015 225)',
                opacity: isSaving ? 0.7 : 1,
                transition: 'all 0.2s',
                whiteSpace: 'nowrap',
              }}
            >
              {isSaving ? 'En cours…' : existing ? 'Remplacer' : 'Attribuer'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

// ── Component principal ────────────────────────────────────────────────────────

export default function StudentsPage({ universityId }: StudentsPageProps) {
  const [students, setStudents]         = useState<Student[]>([]);
  const [filtered, setFiltered]         = useState<Student[]>([]);
  const [isLoading, setIsLoading]       = useState(true);
  const [error, setError]               = useState<string | null>(null);
  const [search, setSearch]             = useState('');
  const [levelFilter, setLevelFilter]   = useState('');
  const [filiereFilter, setFiliereFilter] = useState('');
  const [filieres, setFilieres]         = useState<string[]>([]);
  const [lastRefresh, setLastRefresh]   = useState<Date>(new Date());
  const [rfidModal, setRfidModal]       = useState<Student | null>(null);

  const loadStudents = useCallback(async () => {
    try {
      setIsLoading(true);
      setError(null);
      const resp = await api.getStudents(universityId, {
        search: search || undefined,
        level:  levelFilter || undefined,
        limit:  100,
      });
      const list: Student[] = Array.isArray(resp.data) ? resp.data : [];
      setStudents(list);
      // Guard: only keep string values — never let a filiere object slip into the dropdown
      const names = [...new Set(
        list
          .map(s => {
            const n = s.filiere?.name;
            return typeof n === 'string' && n.trim() !== '' ? n : null;
          })
          .filter((n): n is string => n !== null)
      )].sort();
      setFilieres(names);
    } catch (e: any) {
      setError(e.message || 'Erreur chargement');
    } finally {
      setIsLoading(false);
    }
  }, [universityId, search, levelFilter]);

  useEffect(() => {
    let list = students;
    if (filiereFilter) list = list.filter(s => s.filiere?.name === filiereFilter);
    setFiltered(list);
  }, [students, filiereFilter]);

  // Callback après action RFID : mise à jour locale sans rechargement complet
  const handleRfidSuccess = useCallback((studentId: number, card: RfidCard | null) => {
    const update = (list: Student[]) =>
      list.map(s => s.id === studentId ? { ...s, rfid_card: card } : s);
    setStudents(update);
    setFiltered(update);
  }, []);

  useEffect(() => { loadStudents(); }, [loadStudents, lastRefresh]);

  useEffect(() => {
    const interval = setInterval(() => setLastRefresh(new Date()), 30_000);
    return () => clearInterval(interval);
  }, []);

  const handleSearchKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter') loadStudents();
  };

  // Shared input style
  const inputStyle: React.CSSProperties = {
    background: 'oklch(0.11 0.014 240)',
    border: '1px solid oklch(0.19 0.018 240)',
    borderRadius: 8, padding: '8px 12px', fontSize: 13,
    color: 'oklch(0.90 0.015 220)', outline: 'none',
    fontFamily: "'Plus Jakarta Sans', sans-serif",
    transition: 'border-color 0.2s', colorScheme: 'dark',
  } as React.CSSProperties;

  // ── Loading ─────────────────────────────────────────────────────────────────
  if (isLoading && students.length === 0) {
    return (
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: 240, flexDirection: 'column', gap: 14 }}>
        <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>
        <div style={{
          width: 36, height: 36, borderRadius: '50%',
          border: '3px solid rgba(232,160,32,0.15)', borderTop: '3px solid #E8A020',
          animation: 'spin 0.8s linear infinite',
        }} />
        <p style={{ fontSize: 13, color: 'oklch(0.55 0.020 225)' }}>Chargement des étudiants…</p>
      </div>
    );
  }

  // ── Error ────────────────────────────────────────────────────────────────────
  if (error) {
    return (
      <div style={{
        background: 'rgba(229,92,92,0.10)', border: '1px solid rgba(229,92,92,0.25)',
        color: '#E55C5C', padding: '14px 18px', borderRadius: 10, fontSize: 13,
        display: 'flex', alignItems: 'center', gap: 12,
      }}>
        Erreur : {error}
        <button onClick={loadStudents} style={{
          color: '#E8A020', background: 'none', border: 'none',
          textDecoration: 'underline', cursor: 'pointer', fontSize: 13,
        }}>Réessayer</button>
      </div>
    );
  }

  // ── Render ───────────────────────────────────────────────────────────────────
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20, fontFamily: "'Plus Jakarta Sans', sans-serif" }}>
      {/* Modale RFID */}
      {rfidModal && (
        <RfidModal
          student={rfidModal}
          universityId={universityId}
          onClose={() => setRfidModal(null)}
          onSuccess={(id, card) => { handleRfidSuccess(id, card); }}
        />
      )}

      <style>{`
        @keyframes spin { to { transform: rotate(360deg); } }
        select option { background: #0E1117; color: #DDE6F0; }
      `}</style>

      {/* Header */}
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <div>
          <h2 style={{ fontSize: 20, fontWeight: 700, color: 'oklch(0.90 0.015 220)', letterSpacing: '-0.4px' }}>
            Étudiants inscrits
          </h2>
          <p style={{ fontSize: 12.5, color: 'oklch(0.55 0.020 225)', marginTop: 3 }}>
            <span style={{ color: '#E8A020', fontWeight: 700 }}>{filtered.length}</span>
            {' '}étudiant{filtered.length !== 1 ? 's' : ''}
            {students.length !== filtered.length && (
              <span style={{ color: 'oklch(0.45 0.018 225)' }}> / {students.length} total</span>
            )}
          </p>
        </div>
        <button
          onClick={() => setLastRefresh(new Date())}
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

      {/* Filters */}
      <div style={{ display: 'flex', flexWrap: 'wrap', gap: 10 }}>
        <input
          type="text"
          placeholder="Rechercher (nom, email, matricule)…"
          value={search}
          onChange={e => setSearch(e.target.value)}
          onKeyDown={handleSearchKeyDown}
          style={{ ...inputStyle, flex: '1 1 220px' }}
          onFocus={e => (e.target.style.borderColor = 'rgba(232,160,32,0.5)')}
          onBlur={e  => (e.target.style.borderColor = 'oklch(0.19 0.018 240)')}
        />
        <select
          value={levelFilter}
          onChange={e => setLevelFilter(e.target.value)}
          style={inputStyle}
          onFocus={e => (e.target.style.borderColor = 'rgba(232,160,32,0.5)')}
          onBlur={e  => (e.target.style.borderColor = 'oklch(0.19 0.018 240)')}
        >
          <option value="">Tous les niveaux</option>
          {Object.entries(LEVEL_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
        </select>
        <select
          value={filiereFilter}
          onChange={e => setFiliereFilter(e.target.value)}
          style={inputStyle}
          onFocus={e => (e.target.style.borderColor = 'rgba(232,160,32,0.5)')}
          onBlur={e  => (e.target.style.borderColor = 'oklch(0.19 0.018 240)')}
        >
          <option value="">Toutes les filières</option>
          {filieres.map(f => {
              // Safety: only render string values — never an object
              const label = typeof f === 'string' ? f : String(f);
              return <option key={label} value={label}>{label}</option>;
            })}
        </select>
      </div>

      {/* Empty state */}
      {filtered.length === 0 ? (
        <div style={{
          textAlign: 'center', padding: '60px 24px',
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
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <p style={{ color: 'oklch(0.70 0.015 220)', fontSize: 14, fontWeight: 600 }}>Aucun étudiant trouvé</p>
          <p style={{ color: 'oklch(0.45 0.015 225)', fontSize: 12.5, marginTop: 6 }}>
            Les étudiants inscrits via l'app mobile apparaîtront ici automatiquement
          </p>
        </div>
      ) : (
        /* Table */
        <div style={{
          background: 'oklch(0.11 0.014 240)',
          border: '1px solid oklch(0.19 0.018 240)',
          borderRadius: 12, overflow: 'hidden',
        }}>
          <div style={{ overflowX: 'auto' }}>
            <table style={{ width: '100%', fontSize: 13, borderCollapse: 'collapse' }}>
              <thead>
                <tr style={{ background: 'oklch(0.095 0.013 240)', borderBottom: '1px solid oklch(0.19 0.018 240)' }}>
                  {['Étudiant', 'Matricule', 'Filière', 'Niveau', 'Cours', 'Carte RFID', 'Inscrit le'].map(h => (
                    <th key={h} style={{
                      textAlign: 'left', padding: '12px 16px',
                      fontSize: 10.5, fontWeight: 700, textTransform: 'uppercase',
                      letterSpacing: '0.08em', color: 'oklch(0.50 0.018 225)',
                    }}>{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {filtered.map(student => {
                  const grad = AVATAR_GRADIENTS[student.id % AVATAR_GRADIENTS.length];
                  const initials = student.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
                  const lvStyle = LEVEL_STYLES[student.level] ?? { bg: 'oklch(0.14 0.016 240)', color: 'oklch(0.55 0.020 225)' };

                  return (
                    <tr
                      key={student.id}
                      style={{ borderBottom: '1px solid oklch(0.14 0.015 240)', transition: 'background 0.15s' }}
                      onMouseEnter={e => (e.currentTarget.style.background = 'oklch(0.13 0.015 240)')}
                      onMouseLeave={e => (e.currentTarget.style.background = 'transparent')}
                    >
                      {/* Name */}
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

                      {/* Matricule */}
                      <td style={{ padding: '13px 16px' }}>
                        <span style={{
                          fontFamily: "'IBM Plex Mono', monospace", fontSize: 11.5,
                          background: 'rgba(232,160,32,0.10)', color: '#E8A020',
                          border: '1px solid rgba(232,160,32,0.22)',
                          padding: '3px 8px', borderRadius: 6,
                        }}>
                          {student.registration_number || '—'}
                        </span>
                      </td>

                      {/* Filière */}
                      <td style={{ padding: '13px 16px' }}>
                        {student.filiere && typeof student.filiere === 'object' ? (
                          <div>
                            <p style={{ fontWeight: 500, color: 'oklch(0.85 0.015 220)', fontSize: 13 }}>
                              {String(student.filiere.name ?? '')}
                            </p>
                            <p style={{ fontSize: 11, color: 'oklch(0.50 0.018 225)', fontFamily: "'IBM Plex Mono', monospace" }}>
                              {String(student.filiere.code ?? '')}
                            </p>
                          </div>
                        ) : (
                          <span style={{ color: 'oklch(0.40 0.015 225)', fontSize: 12, fontStyle: 'italic' }}>Non assignée</span>
                        )}
                      </td>

                      {/* Niveau */}
                      <td style={{ padding: '13px 16px' }}>
                        <span style={{
                          padding: '3px 10px', borderRadius: 20, fontSize: 11.5, fontWeight: 600,
                          background: lvStyle.bg, color: lvStyle.color,
                        }}>
                          {LEVEL_LABELS[student.level] || student.level || '—'}
                        </span>
                      </td>

                      {/* Cours */}
                      <td style={{ padding: '13px 16px', textAlign: 'center' }}>
                        <span style={{ fontWeight: 700, color: 'oklch(0.80 0.015 220)', fontSize: 15 }}>
                          {student.enrollment_count}
                        </span>
                      </td>

                      {/* RFID */}
                      <td style={{ padding: '13px 16px' }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                          {student.rfid_card ? (
                            <span style={{
                              display: 'flex', alignItems: 'center', gap: 5,
                              fontSize: 12, fontWeight: 500,
                              color: student.rfid_card.is_active ? '#2DB87A' : 'oklch(0.50 0.018 225)',
                            }}>
                              <span style={{
                                width: 7, height: 7, borderRadius: '50%', display: 'inline-block',
                                background: student.rfid_card.is_active ? '#2DB87A' : 'oklch(0.35 0.015 225)',
                                boxShadow: student.rfid_card.is_active ? '0 0 6px rgba(45,184,122,0.5)' : 'none',
                              }} />
                              {student.rfid_card.is_active ? 'Active' : 'Inactive'}
                            </span>
                          ) : (
                            <span style={{ color: 'oklch(0.40 0.015 225)', fontSize: 12, fontStyle: 'italic' }}>Aucune</span>
                          )}
                          {/* Bouton gérer RFID */}
                          <button
                            onClick={() => setRfidModal(student)}
                            title={student.rfid_card ? 'Gérer la carte RFID' : 'Attribuer une carte RFID'}
                            style={{
                              padding: '3px 8px', fontSize: 10.5, fontFamily: 'inherit',
                              fontWeight: 600, borderRadius: 6, cursor: 'pointer',
                              background: student.rfid_card
                                ? 'rgba(155,120,235,0.12)'
                                : 'rgba(232,160,32,0.12)',
                              border: `1px solid ${student.rfid_card
                                ? 'rgba(155,120,235,0.30)'
                                : 'rgba(232,160,32,0.30)'}`,
                              color: student.rfid_card ? '#9B78EB' : '#E8A020',
                              transition: 'all 0.15s',
                            }}
                            onMouseEnter={e => { e.currentTarget.style.opacity = '0.75'; }}
                            onMouseLeave={e => { e.currentTarget.style.opacity = '1'; }}
                          >
                            {student.rfid_card ? 'Gérer' : '+ Attribuer'}
                          </button>
                        </div>
                      </td>

                      {/* Date */}
                      <td style={{
                        padding: '13px 16px',
                        fontFamily: "'IBM Plex Mono', monospace", fontSize: 11.5,
                        color: 'oklch(0.50 0.018 225)',
                      }}>
                        {student.registered_at
                          ? new Date(student.registered_at).toLocaleDateString('fr-FR')
                          : '—'}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Footer note */}
      <p style={{ fontSize: 11, color: 'oklch(0.40 0.015 225)', textAlign: 'right' }}>
        Mis à jour : {lastRefresh.toLocaleTimeString('fr-FR')} · Actualisation auto toutes les 30s
      </p>
    </div>
  );
}
