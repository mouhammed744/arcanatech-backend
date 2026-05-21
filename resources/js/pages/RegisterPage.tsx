import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { api } from '~/lib/api';

export default function RegisterPage() {
  const navigate = useNavigate();

  const [form, setForm] = useState({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
  });

  const [isLoading, setIsLoading]   = useState(false);
  const [error, setError]           = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]> | null>(null);
  const [focused, setFocused]       = useState<string | null>(null);

  const set = (field: string) => (e: React.ChangeEvent<HTMLInputElement>) =>
    setForm(prev => ({ ...prev, [field]: e.target.value }));

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setFieldErrors(null);

    if (form.password !== form.password_confirmation) {
      setError('Les mots de passe ne correspondent pas.');
      return;
    }

    setIsLoading(true);
    try {
      await api.register(form);
      navigate('/login');
    } catch (err: any) {
      if (err?.errors) {
        setFieldErrors(err.errors);
        const firstMsg = Object.values(err.errors).flat()[0] as string;
        setError(firstMsg || "Erreur lors de l'inscription.");
      } else {
        setError(err?.message || "Erreur lors de l'inscription.");
      }
    } finally {
      setIsLoading(false);
    }
  };

  const inputStyle = (name: string): React.CSSProperties => ({
    width: '100%',
    padding: '13px 16px',
    background: focused === name ? 'oklch(0.13 0.015 240)' : 'oklch(0.11 0.014 240)',
    border: `1px solid ${focused === name ? 'rgba(232,160,32,0.4)' : 'oklch(0.19 0.018 240)'}`,
    borderRadius: 12,
    color: 'oklch(0.90 0.015 220)',
    fontSize: 14,
    outline: 'none',
    transition: 'all 0.2s',
    boxShadow: focused === name ? '0 0 0 3px rgba(232,160,32,0.08)' : 'none',
    fontFamily: "'Plus Jakarta Sans', sans-serif",
  });

  const labelStyle = (name: string): React.CSSProperties => ({
    display: 'block',
    fontSize: 12,
    fontWeight: 700,
    color: focused === name ? '#E8A020' : 'oklch(0.55 0.020 225)',
    letterSpacing: '0.08em',
    textTransform: 'uppercase',
    marginBottom: 8,
    transition: 'color 0.2s',
  });

  return (
    <div
      style={{
        fontFamily: "'Plus Jakarta Sans', sans-serif",
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background:
          'radial-gradient(ellipse 80% 60% at 20% 50%, rgba(232,160,32,0.06) 0%, transparent 60%), radial-gradient(ellipse 60% 60% at 80% 80%, rgba(91,163,232,0.05) 0%, transparent 60%), oklch(0.085 0.012 240)',
        padding: '24px 16px',
      }}
    >
      <style>{`
        @keyframes spin { to { transform: rotate(360deg); } }
        input::placeholder { color: oklch(0.35 0.020 225); }
        input:-webkit-autofill {
          -webkit-box-shadow: 0 0 0 1000px oklch(0.11 0.014 240) inset !important;
          -webkit-text-fill-color: oklch(0.90 0.015 220) !important;
        }
      `}</style>

      <div style={{ width: '100%', maxWidth: 440 }}>

        {/* Logo */}
        <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 36, justifyContent: 'center' }}>
          <div style={{
            width: 44, height: 44,
            background: 'linear-gradient(135deg, #E8A020, #C87B0A)',
            borderRadius: 12,
            display: 'flex', alignItems: 'center', justifyContent: 'center',
            boxShadow: '0 0 24px rgba(232,160,32,0.35)',
          }}>
            <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="white" strokeWidth="1.8">
              <path strokeLinecap="round" strokeLinejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/>
              <path strokeLinecap="round" strokeLinejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
            </svg>
          </div>
          <div>
            <p style={{ color: '#E8A020', fontWeight: 800, fontSize: 15, letterSpacing: '0.08em' }}>LCS</p>
            <p style={{ color: 'oklch(0.55 0.020 225)', fontSize: 11, letterSpacing: '0.04em', marginTop: -2 }}>
              Les Cours Sonou
            </p>
          </div>
        </div>

        {/* Card */}
        <div style={{
          background: 'oklch(0.11 0.014 240)',
          border: '1px solid oklch(0.19 0.018 240)',
          borderRadius: 18,
          padding: '32px 32px',
          position: 'relative', overflow: 'hidden',
        }}>
          {/* Top accent */}
          <div style={{
            position: 'absolute', top: 0, left: 0, right: 0, height: 3,
            background: 'linear-gradient(90deg, #E8A020, #5BA3E8)',
          }} />

          <h2 style={{
            fontSize: 24, fontWeight: 800,
            color: 'oklch(0.90 0.015 220)',
            letterSpacing: '-0.02em', marginBottom: 6,
          }}>
            Créer un compte
          </h2>
          <p style={{ color: 'oklch(0.55 0.020 225)', fontSize: 13.5, marginBottom: 28 }}>
            Renseignez vos informations pour vous inscrire
          </p>

          {/* Error banner */}
          {error && (
            <div style={{
              padding: '11px 15px', borderRadius: 10, marginBottom: 20,
              background: 'rgba(229,92,92,0.10)',
              border: '1px solid rgba(229,92,92,0.25)',
              color: '#E55C5C', fontSize: 13, fontWeight: 500,
              display: 'flex', alignItems: 'center', gap: 8,
            }}>
              <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" style={{ flexShrink: 0 }}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit}>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>

              {/* Prénom + Nom */}
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
                <div>
                  <label style={labelStyle('first_name')}>Prénom</label>
                  <input
                    type="text"
                    value={form.first_name}
                    onChange={set('first_name')}
                    onFocus={() => setFocused('first_name')}
                    onBlur={() => setFocused(null)}
                    placeholder="Jean"
                    required
                    disabled={isLoading}
                    style={inputStyle('first_name')}
                  />
                  {fieldErrors?.first_name && (
                    <p style={{ color: '#E55C5C', fontSize: 11.5, marginTop: 5 }}>{fieldErrors.first_name[0]}</p>
                  )}
                </div>
                <div>
                  <label style={labelStyle('last_name')}>Nom</label>
                  <input
                    type="text"
                    value={form.last_name}
                    onChange={set('last_name')}
                    onFocus={() => setFocused('last_name')}
                    onBlur={() => setFocused(null)}
                    placeholder="Dupont"
                    required
                    disabled={isLoading}
                    style={inputStyle('last_name')}
                  />
                  {fieldErrors?.last_name && (
                    <p style={{ color: '#E55C5C', fontSize: 11.5, marginTop: 5 }}>{fieldErrors.last_name[0]}</p>
                  )}
                </div>
              </div>

              {/* Email */}
              <div>
                <label style={labelStyle('email')}>Adresse email</label>
                <input
                  type="email"
                  value={form.email}
                  onChange={set('email')}
                  onFocus={() => setFocused('email')}
                  onBlur={() => setFocused(null)}
                  placeholder="email@lcs.edu"
                  required
                  disabled={isLoading}
                  style={inputStyle('email')}
                />
                {fieldErrors?.email && (
                  <p style={{ color: '#E55C5C', fontSize: 11.5, marginTop: 5 }}>{fieldErrors.email[0]}</p>
                )}
              </div>

              {/* Mot de passe */}
              <div>
                <label style={labelStyle('password')}>Mot de passe</label>
                <input
                  type="password"
                  value={form.password}
                  onChange={set('password')}
                  onFocus={() => setFocused('password')}
                  onBlur={() => setFocused(null)}
                  placeholder="••••••••"
                  required
                  disabled={isLoading}
                  style={inputStyle('password')}
                />
                {fieldErrors?.password && (
                  <p style={{ color: '#E55C5C', fontSize: 11.5, marginTop: 5 }}>{fieldErrors.password[0]}</p>
                )}
              </div>

              {/* Confirmation */}
              <div>
                <label style={labelStyle('password_confirmation')}>Confirmer le mot de passe</label>
                <input
                  type="password"
                  value={form.password_confirmation}
                  onChange={set('password_confirmation')}
                  onFocus={() => setFocused('password_confirmation')}
                  onBlur={() => setFocused(null)}
                  placeholder="••••••••"
                  required
                  disabled={isLoading}
                  style={inputStyle('password_confirmation')}
                />
              </div>

              {/* Submit */}
              <button
                type="submit"
                disabled={isLoading}
                style={{
                  marginTop: 4,
                  width: '100%', padding: '14px 24px',
                  background: isLoading
                    ? 'oklch(0.19 0.018 240)'
                    : 'linear-gradient(135deg, #E8A020 0%, #C87B0A 100%)',
                  border: 'none', borderRadius: 12,
                  cursor: isLoading ? 'not-allowed' : 'pointer',
                  color: isLoading ? 'oklch(0.55 0.020 225)' : '#08090E',
                  fontSize: 14, fontWeight: 800, letterSpacing: '0.03em',
                  display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8,
                  transition: 'all 0.2s',
                  boxShadow: isLoading ? 'none' : '0 0 24px rgba(232,160,32,0.30)',
                  fontFamily: "'Plus Jakarta Sans', sans-serif",
                }}
              >
                {isLoading ? (
                  <>
                    <div style={{
                      width: 16, height: 16,
                      border: '2px solid oklch(0.35 0.020 225)',
                      borderTop: '2px solid #E8A020',
                      borderRadius: '50%',
                      animation: 'spin 0.8s linear infinite',
                    }} />
                    Inscription en cours…
                  </>
                ) : (
                  <>
                    Créer mon compte
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                      <path strokeLinecap="round" strokeLinejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                  </>
                )}
              </button>
            </div>
          </form>

          {/* Link to login */}
          <p style={{
            textAlign: 'center', fontSize: 13,
            color: 'oklch(0.50 0.018 225)', marginTop: 24,
          }}>
            Vous avez déjà un compte ?{' '}
            <Link
              to="/login"
              style={{ color: '#E8A020', fontWeight: 600, textDecoration: 'none' }}
            >
              Se connecter
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
}
