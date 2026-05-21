import { useState, useEffect } from 'react';
import { useAuthContext } from '~/contexts/AuthContext';
import { useNavigate, Link } from 'react-router-dom';

export default function LoginPage() {
  const navigate = useNavigate();
  const { login, isLoading, error, user, isAuthenticated, fieldErrors } = useAuthContext();

  const [email, setEmail]               = useState('');
  const [password, setPassword]         = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [focused, setFocused]           = useState<'email' | 'password' | null>(null);

  useEffect(() => {
    if (isAuthenticated && user) {
      setTimeout(() => {
        const routes: Record<string, string> = {
          admin:   '/admin/dashboard',
          teacher: '/teacher/dashboard',
          student: '/student/dashboard',
        };
        navigate(routes[user.role] ?? '/login', { replace: true });
      }, 300);
    }
  }, [isAuthenticated, user, navigate]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email || !password) return;
    setIsSubmitting(true);
    try { await login({ email, password }); }
    finally { setIsSubmitting(false); }
  };

  return (
    <div style={{ fontFamily: "'Plus Jakarta Sans', sans-serif" }}
      className="min-h-screen flex overflow-hidden relative"
      css-bg="true"
    >
      {/* ── Background ── */}
      <div className="absolute inset-0" style={{
        background: 'radial-gradient(ellipse 80% 60% at 20% 50%, rgba(232,160,32,0.06) 0%, transparent 60%), radial-gradient(ellipse 60% 60% at 80% 80%, rgba(91,163,232,0.05) 0%, transparent 60%), oklch(0.085 0.012 240)',
      }} />

      {/* Decorative grid */}
      <div className="absolute inset-0 opacity-[0.03]" style={{
        backgroundImage: 'linear-gradient(rgba(232,160,32,0.8) 1px, transparent 1px), linear-gradient(90deg, rgba(232,160,32,0.8) 1px, transparent 1px)',
        backgroundSize: '60px 60px',
      }} />

      {/* ── Left panel (hero) ── */}
      <div className="hidden lg:flex flex-col justify-between w-[520px] flex-shrink-0 relative z-10 p-12"
        style={{ borderRight: '1px solid rgba(232,160,32,0.08)' }}>

        {/* Logo */}
        <div className="animate-slide-up">
          <div className="flex items-center gap-3 mb-16">
            <div style={{
              width: 44, height: 44,
              background: 'linear-gradient(135deg, #E8A020, #C87B0A)',
              borderRadius: 12,
              display: 'flex', alignItems: 'center', justifyContent: 'center',
              boxShadow: '0 0 24px rgba(232,160,32,0.35)',
            }}>
              <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="white" strokeWidth="1.8">
                <path strokeLinecap="round" strokeLinejoin="round"
                  d="M12 14l9-5-9-5-9 5 9 5z"/>
                <path strokeLinecap="round" strokeLinejoin="round"
                  d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
              </svg>
            </div>
            <div>
              <div style={{ color: '#E8A020', fontWeight: 800, fontSize: 15, letterSpacing: '0.08em' }}>
                LCS
              </div>
              <div style={{ color: 'oklch(0.55 0.020 225)', fontSize: 11, letterSpacing: '0.04em', marginTop: -2 }}>
                Les Cours SONOU
              </div>
            </div>
          </div>

          {/* Big headline */}
          <div>
            <h1 style={{
              fontSize: 52, fontWeight: 900, lineHeight: 1.05,
              color: 'oklch(0.90 0.015 220)',
              letterSpacing: '-0.03em',
            }}>
              Portail<br />
              <span style={{ color: '#E8A020' }}>Admin</span>
            </h1>
            <p style={{ color: 'oklch(0.55 0.020 225)', fontSize: 15, marginTop: 20, lineHeight: 1.6, maxWidth: 360 }}>
              Gérez vos étudiants, filières et présences depuis une interface unifiée.
            </p>
          </div>
        </div>

        {/* Bottom stats */}
        <div className="animate-slide-up delay-200">
          <div style={{
            display: 'grid', gridTemplateColumns: '1fr 1fr 1fr',
            gap: 1, background: 'rgba(232,160,32,0.08)',
            borderRadius: 16, overflow: 'hidden',
            border: '1px solid rgba(232,160,32,0.12)',
          }}>
            {[
              { value: '100%', label: 'Numérique' },
              { value: '13', label: 'chiffres/matricule' },
              { value: '24/7', label: 'Disponible' },
            ].map(stat => (
              <div key={stat.label} style={{
                padding: '20px 16px',
                background: 'oklch(0.11 0.014 240)',
                textAlign: 'center',
              }}>
                <div style={{ fontSize: 22, fontWeight: 800, color: '#E8A020', fontFamily: "'IBM Plex Mono', monospace" }}>
                  {stat.value}
                </div>
                <div style={{ fontSize: 11, color: 'oklch(0.55 0.020 225)', marginTop: 4, letterSpacing: '0.03em' }}>
                  {stat.label}
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* ── Right panel (form) ── */}
      <div className="flex-1 flex items-center justify-center p-6 lg:p-12 relative z-10">
        <div style={{ width: '100%', maxWidth: 420 }}>

          {/* Mobile logo */}
          <div className="lg:hidden flex items-center gap-3 mb-10 animate-slide-up">
            <div style={{
              width: 40, height: 40,
              background: 'linear-gradient(135deg, #E8A020, #C87B0A)',
              borderRadius: 10, display: 'flex', alignItems: 'center', justifyContent: 'center',
            }}>
              <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="white" strokeWidth="1.8">
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 14l9-5-9-5-9 5 9 5z"/>
              </svg>
            </div>
            <span style={{ color: '#E8A020', fontWeight: 800, fontSize: 16 }}>Les Cours SONOU</span>
          </div>

          <div className="animate-slide-up delay-100">
            <h2 style={{
              fontSize: 28, fontWeight: 800, color: 'oklch(0.90 0.015 220)',
              letterSpacing: '-0.02em', marginBottom: 6,
            }}>
              Connexion
            </h2>
            <p style={{ color: 'oklch(0.55 0.020 225)', fontSize: 14, marginBottom: 32 }}>
              Entrez vos identifiants administrateur
            </p>

            {/* Error */}
            {error && (
              <div style={{
                padding: '12px 16px', borderRadius: 10, marginBottom: 20,
                background: 'rgba(229,92,92,0.10)',
                border: '1px solid rgba(229,92,92,0.25)',
                color: '#E55C5C', fontSize: 13, fontWeight: 500,
                display: 'flex', alignItems: 'center', gap: 8,
              }}>
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" style={{ flexShrink: 0 }}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit}>

              {/* Email */}
              <div style={{ marginBottom: 16 }}>
                <label style={{
                  display: 'block', fontSize: 12, fontWeight: 700,
                  color: focused === 'email' ? '#E8A020' : 'oklch(0.55 0.020 225)',
                  letterSpacing: '0.08em', textTransform: 'uppercase', marginBottom: 8,
                  transition: 'color 0.2s',
                }}>
                  Adresse email
                </label>
                <div style={{ position: 'relative' }}>
                  <input
                    type="email"
                    value={email}
                    onChange={e => setEmail(e.target.value)}
                    onFocus={() => setFocused('email')}
                    onBlur={() => setFocused(null)}
                    placeholder="admin@lcs.edu"
                    disabled={isSubmitting || isLoading}
                    style={{
                      width: '100%', padding: '13px 16px 13px 44px',
                      background: focused === 'email' ? 'oklch(0.13 0.015 240)' : 'oklch(0.11 0.014 240)',
                      border: `1px solid ${focused === 'email' ? 'rgba(232,160,32,0.4)' : 'oklch(0.19 0.018 240)'}`,
                      borderRadius: 12,
                      color: 'oklch(0.90 0.015 220)', fontSize: 14,
                      outline: 'none', transition: 'all 0.2s',
                      boxShadow: focused === 'email' ? '0 0 0 3px rgba(232,160,32,0.08)' : 'none',
                      fontFamily: "'Plus Jakarta Sans', sans-serif",
                    }}
                  />
                  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke={focused === 'email' ? '#E8A020' : 'oklch(0.45 0.020 225)'}
                    strokeWidth="1.8" style={{ position: 'absolute', left: 14, top: '50%', transform: 'translateY(-50%)', transition: 'stroke 0.2s' }}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                </div>
                {fieldErrors?.email && (
                  <p style={{ color: '#E55C5C', fontSize: 12, marginTop: 6 }}>{fieldErrors.email[0]}</p>
                )}
              </div>

              {/* Password */}
              <div style={{ marginBottom: 28 }}>
                <label style={{
                  display: 'block', fontSize: 12, fontWeight: 700,
                  color: focused === 'password' ? '#E8A020' : 'oklch(0.55 0.020 225)',
                  letterSpacing: '0.08em', textTransform: 'uppercase', marginBottom: 8,
                  transition: 'color 0.2s',
                }}>
                  Mot de passe
                </label>
                <div style={{ position: 'relative' }}>
                  <input
                    type={showPassword ? 'text' : 'password'}
                    value={password}
                    onChange={e => setPassword(e.target.value)}
                    onFocus={() => setFocused('password')}
                    onBlur={() => setFocused(null)}
                    placeholder="••••••••"
                    disabled={isSubmitting || isLoading}
                    style={{
                      width: '100%', padding: '13px 44px 13px 44px',
                      background: focused === 'password' ? 'oklch(0.13 0.015 240)' : 'oklch(0.11 0.014 240)',
                      border: `1px solid ${focused === 'password' ? 'rgba(232,160,32,0.4)' : 'oklch(0.19 0.018 240)'}`,
                      borderRadius: 12,
                      color: 'oklch(0.90 0.015 220)', fontSize: 14,
                      outline: 'none', transition: 'all 0.2s',
                      boxShadow: focused === 'password' ? '0 0 0 3px rgba(232,160,32,0.08)' : 'none',
                      fontFamily: "'Plus Jakarta Sans', sans-serif",
                    }}
                  />
                  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke={focused === 'password' ? '#E8A020' : 'oklch(0.45 0.020 225)'}
                    strokeWidth="1.8" style={{ position: 'absolute', left: 14, top: '50%', transform: 'translateY(-50%)', transition: 'stroke 0.2s' }}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                  </svg>
                  <button type="button" onClick={() => setShowPassword(!showPassword)}
                    style={{
                      position: 'absolute', right: 12, top: '50%', transform: 'translateY(-50%)',
                      background: 'none', border: 'none', cursor: 'pointer',
                      color: 'oklch(0.45 0.020 225)', padding: 4,
                    }}>
                    {showPassword
                      ? <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.8"><path strokeLinecap="round" strokeLinejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                      : <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.8"><path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    }
                  </button>
                </div>
                {fieldErrors?.password && (
                  <p style={{ color: '#E55C5C', fontSize: 12, marginTop: 6 }}>{fieldErrors.password[0]}</p>
                )}
              </div>

              {/* Submit */}
              <button
                type="submit"
                disabled={isSubmitting || isLoading}
                style={{
                  width: '100%', padding: '14px 24px',
                  background: isSubmitting || isLoading
                    ? 'oklch(0.19 0.018 240)'
                    : 'linear-gradient(135deg, #E8A020 0%, #C87B0A 100%)',
                  border: 'none', borderRadius: 12, cursor: isSubmitting ? 'not-allowed' : 'pointer',
                  color: isSubmitting || isLoading ? 'oklch(0.55 0.020 225)' : '#08090E',
                  fontSize: 14, fontWeight: 800, letterSpacing: '0.03em',
                  display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8,
                  transition: 'all 0.2s',
                  boxShadow: isSubmitting || isLoading ? 'none' : '0 0 24px rgba(232,160,32,0.30)',
                  fontFamily: "'Plus Jakarta Sans', sans-serif",
                }}
              >
                {isSubmitting || isLoading ? (
                  <>
                    <div style={{
                      width: 16, height: 16,
                      border: '2px solid oklch(0.35 0.020 225)',
                      borderTop: '2px solid #E8A020',
                      borderRadius: '50%',
                      animation: 'spin 0.8s linear infinite',
                    }} />
                    Connexion en cours…
                  </>
                ) : (
                  <>
                    Se connecter
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                      <path strokeLinecap="round" strokeLinejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                  </>
                )}
              </button>
            </form>

            {/* Quick fill */}
            <div style={{ marginTop: 24, paddingTop: 24, borderTop: '1px solid oklch(0.17 0.018 240)' }}>
              <p style={{ fontSize: 11, color: 'oklch(0.40 0.020 225)', textTransform: 'uppercase', letterSpacing: '0.10em', textAlign: 'center', marginBottom: 12 }}>
                Accès rapide
              </p>
              <button
                onClick={() => { setEmail('admin@management.edu'); setPassword('password123'); }}
                disabled={isSubmitting || isLoading}
                style={{
                  width: '100%', padding: '10px 16px',
                  background: 'rgba(232,160,32,0.06)',
                  border: '1px solid rgba(232,160,32,0.15)',
                  borderRadius: 10, cursor: 'pointer',
                  color: '#E8A020', fontSize: 13, fontWeight: 600,
                  display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8,
                  transition: 'all 0.2s',
                  fontFamily: "'Plus Jakarta Sans', sans-serif",
                }}
                onMouseEnter={e => (e.currentTarget.style.background = 'rgba(232,160,32,0.10)')}
                onMouseLeave={e => (e.currentTarget.style.background = 'rgba(232,160,32,0.06)')}
              >
                <span style={{ fontSize: 15 }}>👨‍💼</span>
                Compte administrateur
              </button>
            </div>
          </div>

          <p style={{ textAlign: 'center', fontSize: 13, color: 'oklch(0.50 0.018 225)', marginTop: 24 }}>
            Pas encore de compte ?{' '}
            <Link
              to="/register"
              style={{ color: '#E8A020', fontWeight: 600, textDecoration: 'none' }}
            >
              Créer un compte
            </Link>
          </p>

          <p style={{ textAlign: 'center', color: 'oklch(0.35 0.020 225)', fontSize: 12, marginTop: 16 }}>
            Les Cours SONOU · Système de gestion universitaire
          </p>
        </div>
      </div>

      <style>{`
        @keyframes spin { to { transform: rotate(360deg); } }
        input::placeholder { color: oklch(0.35 0.020 225); }
        input:-webkit-autofill {
          -webkit-box-shadow: 0 0 0 1000px oklch(0.11 0.014 240) inset !important;
          -webkit-text-fill-color: oklch(0.90 0.015 220) !important;
        }
      `}</style>
    </div>
  );
}
