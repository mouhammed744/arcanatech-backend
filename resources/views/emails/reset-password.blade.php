<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Réinitialisation du mot de passe</title>
</head>
<body style="margin:0;padding:0;background:#0a0f1e;font-family:'Segoe UI',Arial,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0f1e;padding:40px 20px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

          {{-- Header --}}
          <tr>
            <td align="center" style="padding-bottom:32px;">
              <div style="display:inline-block;background:linear-gradient(135deg,#1E3A8A,#2563EB);border-radius:16px;padding:16px 28px;">
                <span style="color:#ffffff;font-size:22px;font-weight:700;letter-spacing:1px;">ARCANA TECH</span>
              </div>
            </td>
          </tr>

          {{-- Card --}}
          <tr>
            <td style="background:#111827;border-radius:20px;border:1px solid rgba(255,255,255,0.08);overflow:hidden;">

              {{-- Blue top bar --}}
              <div style="height:4px;background:linear-gradient(90deg,#1D4ED8,#3B82F6,#60A5FA);"></div>

              <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 40px 36px;">

                {{-- Icon --}}
                <tr>
                  <td align="center" style="padding-bottom:24px;">
                    <div style="width:64px;height:64px;border-radius:50%;background:rgba(37,99,235,0.15);border:1px solid rgba(59,130,246,0.40);display:inline-flex;align-items:center;justify-content:center;font-size:28px;">
                      🔐
                    </div>
                  </td>
                </tr>

                {{-- Title --}}
                <tr>
                  <td align="center" style="padding-bottom:12px;">
                    <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">
                      Réinitialisation du mot de passe
                    </h1>
                  </td>
                </tr>

                {{-- Greeting --}}
                <tr>
                  <td style="padding-bottom:20px;">
                    <p style="margin:0;color:rgba(255,255,255,0.70);font-size:15px;line-height:1.6;text-align:center;">
                      Bonjour <strong style="color:#ffffff;">{{ $userName }}</strong>,<br/>
                      Vous avez demandé la réinitialisation de votre mot de passe.<br/>
                      Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe.
                    </p>
                  </td>
                </tr>

                {{-- Button --}}
                <tr>
                  <td align="center" style="padding:24px 0;">
                    <a href="{{ $resetUrl }}"
                       style="display:inline-block;background:linear-gradient(135deg,#1D4ED8,#2563EB);color:#ffffff;text-decoration:none;font-size:16px;font-weight:600;padding:14px 36px;border-radius:12px;letter-spacing:0.3px;">
                      Réinitialiser mon mot de passe
                    </a>
                  </td>
                </tr>

                {{-- Expiry notice --}}
                <tr>
                  <td align="center" style="padding-bottom:28px;">
                    <p style="margin:0;color:rgba(255,255,255,0.45);font-size:13px;">
                      ⏱ Ce lien expire dans <strong style="color:rgba(255,255,255,0.65);">{{ $expiresInMinutes }} minutes</strong>.
                    </p>
                  </td>
                </tr>

                {{-- Divider --}}
                <tr>
                  <td style="border-top:1px solid rgba(255,255,255,0.08);padding-top:24px;">
                    <p style="margin:0;color:rgba(255,255,255,0.35);font-size:12px;line-height:1.6;">
                      Si vous n'avez pas demandé cette réinitialisation, ignorez cet email — votre mot de passe reste inchangé.<br/><br/>
                      Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br/>
                      <span style="color:#60A5FA;word-break:break-all;">{{ $resetUrl }}</span>
                    </p>
                  </td>
                </tr>

              </table>
            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td align="center" style="padding-top:28px;">
              <p style="margin:0;color:rgba(255,255,255,0.25);font-size:12px;">
                ARCANA TECH &copy; {{ date('Y') }} — Système de gestion universitaire
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
