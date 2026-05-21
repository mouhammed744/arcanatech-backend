<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\University;
use App\Models\JwtToken;
use App\Models\AuditLog;
use App\Mail\ResetPasswordMail;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends Controller
{
    // ═══════════════════════════════════════════════════════
    // ═══ INSCRIPTION ══════════════════════════════════════
    // ═══════════════════════════════════════════════════════

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name'      => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-ZÀ-ÿ\s\'-]+$/'],
            'last_name'       => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-ZÀ-ÿ\s\'-]+$/'],
            'email'           => ['required', 'email:rfc', 'max:255', 'unique:utilisateurs,email'],
            'phone'           => ['nullable', 'string', 'max:20', 'regex:/^(\+?\d{1,3})?[\s.\-]?\(?\d{1,4}\)?[\s.\-]?\d{1,4}[\s.\-]?\d{1,9}$/'],
            'password'        => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'university_code' => ['required', 'string', 'min:2', 'max:20'],
        ], [
            'first_name.regex'         => 'Le prénom contient des caractères invalides.',
            'last_name.regex'          => 'Le nom contient des caractères invalides.',
            'email.unique'             => 'Cette adresse email est déjà utilisée.',
            'password.confirmed'       => 'Les mots de passe ne correspondent pas.',
            'university_code.required' => 'Le code université est requis.',
        ]);

        $firstName = strip_tags(trim($validated['first_name']));
        $lastName  = strip_tags(trim($validated['last_name']));
        $email     = strtolower(trim(strip_tags($validated['email'])));
        $phone     = !empty($validated['phone']) ? strip_tags(trim($validated['phone'])) : null;

        $university = University::where('code', $validated['university_code'])->first();
        if (!$university) {
            return response()->json([
                'message' => 'Code université invalide.',
                'errors'  => ['university_code' => ['Ce code université n\'existe pas.']],
            ], 422);
        }

        $user = User::create([
            'universite_id' => $university->id,
            'prenom'        => $firstName,
            'nom'           => $lastName,
            'email'         => $email,
            'telephone'     => $phone,
            'password'      => $validated['password'],
            'role'          => 'admin',
            'est_actif'     => true,
        ]);

        AuditLog::create([
            'universite_id'  => $university->id,
            'utilisateur_id' => $user->id,
            'type_ressource' => 'User',
            'id_ressource'   => $user->id,
            'action'         => 'register',
            'adresse_ip'     => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Inscription réussie. Vous pouvez maintenant vous connecter.',
            'user'    => [
                'id'        => $user->id,
                'firstName' => $user->prenom,
                'lastName'  => $user->nom,
                'email'     => $user->email,
                'role'      => $user->role,
            ],
        ], 201);
    }

    // ═══════════════════════════════════════════════════════
    // ═══ INSCRIPTION ÉTUDIANT (MOBILE) ═══════════════════
    // ═══════════════════════════════════════════════════════

    public function registerStudent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name'  => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-ZÀ-ÿ\s\'-]+$/'],
            'last_name'   => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-ZÀ-ÿ\s\'-]+$/'],
            'email'       => ['required', 'email:rfc', 'max:255', 'unique:utilisateurs,email'],
            'phone'       => ['nullable', 'string', 'max:20'],
            'password'    => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'birth_date'  => ['nullable', 'date', 'before:today', 'after:1940-01-01'],
            'filiere'     => ['nullable', 'string', 'max:100'],
            'filiere_id'  => ['nullable', 'integer', 'exists:filieres,id'],
            'level'       => ['nullable', 'string', 'in:L1,L2,L3,M1,M2,D'],
            'student_card'=> ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'first_name.regex'   => 'Le prénom contient des caractères invalides.',
            'last_name.regex'    => 'Le nom contient des caractères invalides.',
            'email.unique'       => 'Cette adresse email est déjà utilisée.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        $firstName = strip_tags(trim($validated['first_name']));
        $lastName  = strip_tags(trim($validated['last_name']));
        $email     = strtolower(trim(strip_tags($validated['email'])));
        $phone     = !empty($validated['phone']) ? strip_tags(trim($validated['phone'])) : null;

        // Université unique : Les Cours Sonou (LCS)
        $university = University::where('code', 'LCS')->firstOrFail();

        $user = User::create([
            'universite_id' => $university->id,
            'prenom'        => $firstName,
            'nom'           => $lastName,
            'email'         => $email,
            'telephone'     => $phone,
            'password'      => $validated['password'],
            'role'          => 'student',
            'est_actif'     => true,
        ]);

        // Résolution filière : ID direct (dropdown) > recherche par nom
        $filiereId = null;
        if (!empty($validated['filiere_id'])) {
            $filiereId = (int) $validated['filiere_id'];
        } elseif (!empty($validated['filiere'])) {
            $filiere = \App\Models\Filiere::where('universite_id', $university->id)
                ->where(function ($q) use ($validated) {
                    $q->whereRaw('LOWER(nom) LIKE ?', ['%' . strtolower($validated['filiere']) . '%'])
                      ->orWhereRaw('LOWER(code) LIKE ?', ['%' . strtolower($validated['filiere']) . '%']);
                })->first();
            $filiereId = $filiere?->id;
        }

        // Génération automatique du matricule unique
        $matricule = $this->generateMatricule($university);

        $student = \App\Models\Student::create([
            'utilisateur_id'    => $user->id,
            'universite_id'     => $university->id,
            'filiere_id'        => $filiereId,
            'numero_matricule'  => $matricule,
            'niveau'            => $validated['level'] ?? 'L1',
            'annee_inscription' => date('Y'),
            'date_naissance'    => $validated['birth_date'] ?? null,
        ]);

        if ($request->hasFile('student_card')) {
            $path = $request->file('student_card')->store('student_cards', 'public');
            $user->update(['chemin_avatar' => $path]);
        }

        AuditLog::create([
            'universite_id'  => $university->id,
            'utilisateur_id' => $user->id,
            'type_ressource' => 'Student',
            'id_ressource'   => $student->id,
            'action'         => 'register_student',
            'adresse_ip'     => $request->ip(),
        ]);

        return response()->json([
            'message'             => 'Inscription réussie. Vous pouvez maintenant vous connecter.',
            'registration_number' => $matricule,
            'user'                => [
                'id'        => $user->id,
                'firstName' => $user->prenom,
                'lastName'  => $user->nom,
                'email'     => $user->email,
                'role'      => $user->role,
            ],
        ], 201);
    }

    /**
     * Génère un matricule unique pour une université.
     * Format : {PREFIX}{AA}{SEQUENCE}
     * Exemple LCS, 8 chiffres → LCS25001, LCS25002…
     */
    private function generateMatricule(University $university): string
    {
        $year      = date('Y');           // 4 chiffres : 2025
        $totalLen  = $university->nb_chiffres_matricule ?? 8;
        $seqDigits = max(1, $totalLen - 4); // ex: 8 - 4 = 4 chiffres de séquence

        // Nombre d'étudiants inscrits cette année dans cette université
        $count = \App\Models\Student::where('universite_id', $university->id)
            ->whereYear('created_at', $year)
            ->count();

        $matricule = $year . str_pad($count + 1, $seqDigits, '0', STR_PAD_LEFT);

        // Garantir l'unicité en cas de collision
        $attempt = 0;
        while (\App\Models\Student::where('numero_matricule', $matricule)->exists()) {
            $attempt++;
            $matricule = $year . str_pad($count + 1 + $attempt, $seqDigits, '0', STR_PAD_LEFT);
        }

        return $matricule;
    }

    // ═══════════════════════════════════════════════════════
    // ═══ CONNEXION ════════════════════════════════════════
    // ═══════════════════════════════════════════════════════

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim($request->email));

        $rateLimitKey = 'login:' . $email . ':' . $request->ip();
        $maxAttempts  = (int) env('RATE_LIMIT_LOGIN', 5);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            AuditLog::create([
                'universite_id'   => null,
                'utilisateur_id'  => null,
                'type_ressource'  => 'User',
                'id_ressource'    => null,
                'action'          => 'login_blocked',
                'adresse_ip'      => $request->ip(),
                'nouvelles_valeurs' => ['email' => $email, 'reason' => 'rate_limit'],
            ]);

            return response()->json([
                'message' => "Trop de tentatives. Réessayez dans {$seconds} secondes.",
            ], 429);
        }

        $user = User::where('email', $email)
                    ->where('est_actif', true)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($rateLimitKey, 60);

            AuditLog::create([
                'universite_id'  => $user?->universite_id,
                'utilisateur_id' => $user?->id,
                'type_ressource' => 'User',
                'id_ressource'   => $user?->id,
                'action'         => 'login_failed',
                'adresse_ip'     => $request->ip(),
                'nouvelles_valeurs' => ['email' => $email],
            ]);

            throw ValidationException::withMessages([
                'email' => ['Identifiants invalides.'],
            ]);
        }

        RateLimiter::clear($rateLimitKey);

        // ── Challenge 2FA si active sur ce compte ───────────────
        $twoFactor = app(TwoFactorService::class);
        if ($twoFactor->isEnabled($user)) {
            $challengeToken = $twoFactor->generateChallengeToken($user);

            // Envoi automatique du code si la methode est email-only
            if ($user->two_factor_method === 'email') {
                $twoFactor->sendEmailCode($user);
            }

            AuditLog::create([
                'universite_id'  => $user->universite_id,
                'utilisateur_id' => $user->id,
                'type_ressource' => 'User',
                'id_ressource'   => $user->id,
                'action'         => 'login_2fa_challenge',
                'adresse_ip'     => $request->ip(),
                'nouvelles_valeurs' => ['method' => $user->two_factor_method],
            ]);

            return response()->json([
                'requires2fa'     => true,
                'method'          => $user->two_factor_method,
                'challengeToken'  => $challengeToken,
                'maskedEmail'     => $this->maskEmailForChallenge($user->email),
                'message'         => 'Authentification supplementaire requise.',
            ]);
        }

        return $this->issueTokensResponse($user, $request, 'login');
    }

    /**
     * Finalise une connexion apres verification 2FA reussie.
     * Appele par TwoFactorController::verify().
     */
    public function finalize2faLogin(Request $request, User $user): JsonResponse
    {
        return $this->issueTokensResponse($user, $request, 'login_2fa');
    }

    /**
     * Emet les JWT d acces + refresh et construit la reponse JSON utilisateur.
     */
    private function issueTokensResponse(User $user, Request $request, string $auditAction): JsonResponse
    {
        // Révoquer les tokens expirés
        JwtToken::where('utilisateur_id', $user->id)
                ->where('type', 'access')
                ->whereNull('revoque_le')
                ->where('expire_le', '<', now())
                ->update(['revoque_le' => now()]);

        $accessTtl  = (int) env('JWT_ACCESS_TTL', 900);
        $refreshTtl = (int) env('JWT_REFRESH_TTL', 604800);

        $accessToken  = $this->generateSignedJwt($user, 'access', $accessTtl);
        $refreshToken = $this->generateSignedJwt($user, 'refresh', $refreshTtl);

        $user->update(['derniere_connexion_le' => now()]);

        AuditLog::create([
            'universite_id'  => $user->universite_id,
            'utilisateur_id' => $user->id,
            'type_ressource' => 'User',
            'id_ressource'   => $user->id,
            'action'         => $auditAction,
            'adresse_ip'     => $request->ip(),
        ]);

        $university = $user->university;

        // Inclure les données étudiant dans la réponse de connexion
        // pour que le dashboard étudiant fonctionne immédiatement sans page reload.
        $studentData = null;
        if ($user->role === 'student') {
            $studentRecord = \App\Models\Student::where('utilisateur_id', $user->id)
                ->with('filiere')
                ->first();
            if ($studentRecord) {
                $studentData = [
                    'studentId'          => $studentRecord->id,
                    'registrationNumber' => $studentRecord->numero_matricule,
                    'level'              => $studentRecord->niveau,
                    'filiere'            => $studentRecord->filiere ? [
                        'id'   => $studentRecord->filiere->id,
                        'name' => $studentRecord->filiere->nom,
                        'code' => $studentRecord->filiere->code,
                    ] : null,
                    'enrollmentYear' => $studentRecord->annee_inscription,
                ];
            }
        }

        return response()->json([
            'tokens' => [
                'accessToken'  => $accessToken->jeton_jwt,
                'refreshToken' => $refreshToken->jeton_jwt,
                'expiresIn'    => $accessTtl,
            ],
            'user' => [
                'id'           => $user->id,
                'firstName'    => $user->prenom,
                'lastName'     => $user->nom,
                'email'        => $user->email,
                'role'         => $user->role,
                'avatar'       => $user->avatar_url ?? null,
                'universityId' => $user->universite_id,
                'twoFactorEnabled' => !empty($user->two_factor_method) && !empty($user->two_factor_confirmed_at),
                'twoFactorMethod'  => $user->two_factor_method,
                'student'      => $studentData,
                'university'   => $university ? [
                    'id'              => $university->id,
                    'name'            => $university->nom,
                    'code'            => $university->code,
                    'city'            => $university->ville,
                    'primaryColor'    => $university->couleur_principale,
                    'secondaryColor'  => $university->couleur_secondaire,
                    'accentPreset'    => $university->preset_accent,
                    'studentIdDigits' => $university->nb_chiffres_matricule,
                    'studentIdPrefix' => $university->prefixe_matricule,
                    'logoUrl'         => $university->url_logo,
                ] : null,
            ],
        ]);
    }

    private function maskEmailForChallenge(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        return mb_substr($local, 0, 1) . str_repeat('*', max(1, mb_strlen($local) - 1)) . '@' . $domain;
    }

    // ═══════════════════════════════════════════════════════
    // ═══ REFRESH TOKEN ════════════════════════════════════
    // ═══════════════════════════════════════════════════════

    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refreshToken' => ['required', 'string'],
        ]);

        $refreshToken = JwtToken::where('jeton_jwt', $request->refreshToken)
                                ->where('type', 'refresh')
                                ->whereNull('revoque_le')
                                ->where('expire_le', '>', now())
                                ->first();

        if (!$refreshToken) {
            return response()->json([
                'message' => 'Token de rafraîchissement invalide ou expiré.',
            ], 401);
        }

        try {
            $secret = env('JWT_SECRET', config('app.key'));
            JWT::decode($request->refreshToken, new Key($secret, 'HS256'));
        } catch (\Exception $e) {
            $refreshToken->update(['revoque_le' => now()]);
            return response()->json(['message' => 'Token invalide.'], 401);
        }

        $user = $refreshToken->user;

        if (!$user || !$user->est_actif) {
            return response()->json(['message' => 'Utilisateur introuvable ou inactif.'], 401);
        }

        $accessTtl   = (int) env('JWT_ACCESS_TTL', 900);
        $accessToken = $this->generateSignedJwt($user, 'access', $accessTtl);

        return response()->json([
            'tokens' => [
                'accessToken' => $accessToken->jeton_jwt,
                'expiresIn'   => $accessTtl,
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════
    // ═══ DÉCONNEXION ══════════════════════════════════════
    // ═══════════════════════════════════════════════════════

    public function logout(Request $request): JsonResponse
    {
        $user = auth()->user();

        JwtToken::where('utilisateur_id', $user->id)
                ->whereNull('revoque_le')
                ->update(['revoque_le' => now()]);

        AuditLog::create([
            'universite_id'  => $user->universite_id,
            'utilisateur_id' => $user->id,
            'type_ressource' => 'User',
            'id_ressource'   => $user->id,
            'action'         => 'logout',
            'adresse_ip'     => $request->ip(),
        ]);

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    // ═══════════════════════════════════════════════════════
    // ═══ PROFIL ═══════════════════════════════════════════
    // ═══════════════════════════════════════════════════════

    public function me(Request $request): JsonResponse
    {
        $user       = auth()->user();
        $university = $user->university;

        $studentData = null;
        if ($user->role === 'student') {
            $student = \App\Models\Student::where('utilisateur_id', $user->id)->with('filiere')->first();
            if ($student) {
                $studentData = [
                    'studentId'          => $student->id,
                    'registrationNumber' => $student->numero_matricule,
                    'level'              => $student->niveau,
                    'filiere'            => $student->filiere ? [
                        'id'   => $student->filiere->id,
                        'name' => $student->filiere->nom,
                        'code' => $student->filiere->code,
                    ] : null,
                    'enrollmentYear'     => $student->annee_inscription,
                ];
            }
        }

        return response()->json([
            'id'           => $user->id,
            'firstName'    => $user->prenom,
            'lastName'     => $user->nom,
            'email'        => $user->email,
            'role'         => $user->role,
            'phone'        => $user->telephone,
            'avatar'       => $user->avatar_url ?? null,
            'universityId' => $user->universite_id,
            'isActive'     => $user->est_actif,
            'university' => $university ? [
                'id'              => $university->id,
                'name'            => $university->nom,
                'code'            => $university->code,
                'city'            => $university->ville,
                'primaryColor'    => $university->couleur_principale,
                'secondaryColor'  => $university->couleur_secondaire,
                'accentPreset'    => $university->preset_accent,
                'studentIdDigits' => $university->nb_chiffres_matricule,
                'studentIdPrefix' => $university->prefixe_matricule,
                'logoUrl'         => $university->url_logo,
            ] : null,
            'student'    => $studentData,
            'createdAt'  => $user->created_at?->toISOString(),
            'updatedAt'  => $user->updated_at?->toISOString(),
        ]);
    }

    // ═══════════════════════════════════════════════════════
    // ═══ AVATAR ═══════════════════════════════════════════
    // ═══════════════════════════════════════════════════════

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = auth()->user();

        if ($user->chemin_avatar) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->chemin_avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['chemin_avatar' => $path]);

        return response()->json([
            'message' => 'Avatar mis à jour.',
            'avatar'  => $user->avatar_url,
        ]);
    }

    public function deleteAvatar(): JsonResponse
    {
        $user = auth()->user();

        if ($user->chemin_avatar) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->chemin_avatar);
            $user->update(['chemin_avatar' => null]);
        }

        return response()->json(['message' => 'Avatar supprimé.']);
    }

    // ═══════════════════════════════════════════════════════
    // ═══ MOT DE PASSE OUBLIÉ ═════════════════════════════
    // ═══════════════════════════════════════════════════════

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        $email        = strtolower(trim($request->email));
        $rateLimitKey = 'forgot:' . $email;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            return response()->json(['message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.']);
        }
        RateLimiter::hit($rateLimitKey, 3600);

        $user = User::where('email', $email)->first();

        if ($user) {
            $token    = Str::random(64);
            $frontendUrl = $this->getFrontendUrl();
            $resetUrl = $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($email);

            Cache::put("password_reset:{$token}", $user->id, now()->addHour());

            // Envoi de l'email de réinitialisation
            try {
                Mail::to($user->email)->send(new ResetPasswordMail(
                    resetUrl:         $resetUrl,
                    userName:         $user->prenom . ' ' . $user->nom,
                    expiresInMinutes: 60,
                ));
            } catch (\Throwable $e) {
                // Logger l'erreur sans bloquer la réponse (sécurité : ne pas révéler l'échec)
                \Log::error('Erreur envoi email reset password : ' . $e->getMessage());
            }

            AuditLog::create([
                'universite_id'  => $user->universite_id,
                'utilisateur_id' => $user->id,
                'type_ressource' => 'User',
                'id_ressource'   => $user->id,
                'action'         => 'password_reset_requested',
                'adresse_ip'     => $request->ip(),
            ]);
        }

        return response()->json(['message' => 'Si cet email existe, un lien de réinitialisation a été envoyé.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $userId = Cache::get("password_reset:{$request->token}");
        if (!$userId) {
            return response()->json(['message' => 'Token invalide ou expiré.'], 422);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        $user->update(['password' => $request->password]);
        Cache::forget("password_reset:{$request->token}");

        JwtToken::where('utilisateur_id', $user->id)
                ->whereNull('revoque_le')
                ->update(['revoque_le' => now()]);

        AuditLog::create([
            'universite_id'  => $user->universite_id,
            'utilisateur_id' => $user->id,
            'type_ressource' => 'User',
            'id_ressource'   => $user->id,
            'action'         => 'password_change',
            'adresse_ip'     => $request->ip(),
        ]);

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }

    // ═══════════════════════════════════════════════════════
    // ═══ CHANGEMENT MOT DE PASSE ═════════════════════════
    // ═══════════════════════════════════════════════════════

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        if (Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Le nouveau mot de passe doit être différent de l\'ancien.'],
            ]);
        }

        $user->update(['password' => $request->password]);

        $currentToken = $request->bearerToken();
        JwtToken::where('utilisateur_id', $user->id)
                ->whereNull('revoque_le')
                ->where('jeton_jwt', '!=', $currentToken)
                ->update(['revoque_le' => now()]);

        AuditLog::create([
            'universite_id'  => $user->universite_id,
            'utilisateur_id' => $user->id,
            'type_ressource' => 'User',
            'id_ressource'   => $user->id,
            'action'         => 'password_change',
            'adresse_ip'     => $request->ip(),
        ]);

        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }

    // ═══════════════════════════════════════════════════════
    // ═══ OAUTH SOCIAL LOGIN ══════════════════════════════
    // ═══════════════════════════════════════════════════════

    public function oauthRedirect(Request $request, string $provider)
    {
        $allowedProviders = ['google'];

        if (!in_array($provider, $allowedProviders)) {
            return redirect($this->getFrontendUrl() . '/login?oauth_error=provider_invalide');
        }

        $providerUpper = strtoupper($provider);
        $clientId      = env("{$providerUpper}_CLIENT_ID");
        $redirectUri   = env("{$providerUpper}_REDIRECT_URI");

        if (!$clientId || !$redirectUri
            || str_starts_with($clientId, 'your-')
            || str_starts_with($redirectUri, 'your-')
        ) {
            return redirect($this->getFrontendUrl() . '/login?oauth_error=' . urlencode("La connexion {$provider} n'est pas encore configurée. Contactez l'administrateur."));
        }

        $state = Str::random(40);
        Cache::put("oauth_state:{$state}", $provider, now()->addMinutes(10));

        $params = http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => $this->getOAuthScope($provider),
            'state'         => $state,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
        ]);

        $baseUrls = [
            'google' => 'https://accounts.google.com/o/oauth2/v2/auth',
        ];

        return redirect($baseUrls[$provider] . '?' . $params);
    }

    public function oauthCallback(Request $request, string $provider)
    {
        $frontendUrl      = $this->getFrontendUrl();
        $allowedProviders = ['google'];

        if (!in_array($provider, $allowedProviders)) {
            return redirect("{$frontendUrl}/login?oauth_error=provider_invalide");
        }

        $state = $request->query('state');
        if (!$state || !Cache::pull("oauth_state:{$state}")) {
            return redirect("{$frontendUrl}/login?oauth_error=" . urlencode('Session expirée, réessayez.'));
        }

        if ($request->has('error')) {
            return redirect("{$frontendUrl}/login?oauth_error=" . urlencode('Authentification annulée.'));
        }

        $code = $request->query('code');
        if (!$code) {
            return redirect("{$frontendUrl}/login?oauth_error=" . urlencode('Code d\'autorisation manquant.'));
        }

        try {
            $tokenData  = $this->exchangeOAuthCode($provider, $code);
            $socialUser = $this->getOAuthUser($provider, $tokenData['access_token']);

            if (!$socialUser || !isset($socialUser['email'])) {
                return redirect("{$frontendUrl}/login?oauth_error=" . urlencode('Impossible de récupérer votre profil.'));
            }

            $user = User::where('email', strtolower($socialUser['email']))->first();

            if (!$user) {
                $user = User::create([
                    'universite_id' => null,
                    'prenom'        => $socialUser['first_name'] ?? '',
                    'nom'           => $socialUser['last_name'] ?? '',
                    'email'         => strtolower($socialUser['email']),
                    'password'      => Hash::make(Str::random(32)),
                    'role'          => 'admin',
                    'est_actif'     => true,
                ]);

                AuditLog::create([
                    'universite_id'  => $user->universite_id,
                    'utilisateur_id' => $user->id,
                    'type_ressource' => 'User',
                    'id_ressource'   => $user->id,
                    'action'         => 'register_oauth',
                    'adresse_ip'     => $request->ip(),
                    'nouvelles_valeurs' => ['provider' => $provider],
                ]);
            }

            if (!$user->est_actif) {
                return redirect("{$frontendUrl}/login?oauth_error=" . urlencode('Ce compte est désactivé.'));
            }

            $accessTtl  = (int) env('JWT_ACCESS_TTL', 900);
            $refreshTtl = (int) env('JWT_REFRESH_TTL', 604800);

            $accessToken  = $this->generateSignedJwt($user, 'access', $accessTtl);
            $refreshToken = $this->generateSignedJwt($user, 'refresh', $refreshTtl);

            $user->update(['derniere_connexion_le' => now()]);

            AuditLog::create([
                'universite_id'  => $user->universite_id,
                'utilisateur_id' => $user->id,
                'type_ressource' => 'User',
                'id_ressource'   => $user->id,
                'action'         => 'login_oauth',
                'adresse_ip'     => $request->ip(),
                'nouvelles_valeurs' => ['provider' => $provider],
            ]);

            $params = http_build_query([
                'accessToken'  => $accessToken->jeton_jwt,
                'refreshToken' => $refreshToken->jeton_jwt,
                'provider'     => $provider,
            ]);

            return redirect("{$frontendUrl}/auth/oauth/callback?{$params}");

        } catch (\Exception $e) {
            report($e);
            return redirect("{$frontendUrl}/login?oauth_error=" . urlencode('Erreur serveur, réessayez.'));
        }
    }

    // ═══════════════════════════════════════════════════════
    // ═══ MOBILE OAUTH — token direct depuis l'app ══════════
    // ═══════════════════════════════════════════════════════

    public function socialLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', 'in:google,apple,tiktok'],
            'token'    => ['required', 'string'],
        ]);

        $provider = $validated['provider'];
        $token    = $validated['token'];

        try {
            $socialUser = match ($provider) {
                'google' => $this->verifyGoogleToken($token),
                'tiktok' => $this->getTikTokUserFromCode($token),
                default  => throw new \InvalidArgumentException("Provider non supporté: {$provider}"),
            };

            if (!$socialUser || !isset($socialUser['email'])) {
                return response()->json(['message' => 'Impossible de récupérer votre profil.'], 422);
            }

            $user = User::where('email', strtolower($socialUser['email']))->first();

            if (!$user) {
                $user = User::create([
                    'universite_id' => null,
                    'prenom'        => $socialUser['first_name'] ?? '',
                    'nom'           => $socialUser['last_name'] ?? '',
                    'email'         => strtolower($socialUser['email']),
                    'password'      => Hash::make(Str::random(32)),
                    'role'          => 'admin',
                    'est_actif'     => true,
                ]);

                AuditLog::create([
                    'universite_id'  => $user->universite_id,
                    'utilisateur_id' => $user->id,
                    'type_ressource' => 'User',
                    'id_ressource'   => $user->id,
                    'action'         => 'register_oauth',
                    'adresse_ip'     => $request->ip(),
                    'nouvelles_valeurs' => ['provider' => $provider],
                ]);
            }

            if (!$user->est_actif) {
                return response()->json(['message' => 'Ce compte est désactivé.'], 403);
            }

            return $this->issueTokensResponse($user, $request, 'login_oauth');

        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    private function verifyGoogleToken(string $token): array
    {
        // Essaie d'abord comme idToken (Google Sign-In mobile)
        $response = \Illuminate\Support\Facades\Http::get(
            'https://oauth2.googleapis.com/tokeninfo',
            ['id_token' => $token]
        );

        if ($response->successful()) {
            $data     = $response->json();
            $clientId = env('GOOGLE_CLIENT_ID');
            if ($clientId && ($data['aud'] ?? '') !== $clientId) {
                throw new \RuntimeException("Token Google invalide (audience incorrecte)");
            }
            return [
                'email'      => $data['email'] ?? null,
                'first_name' => $data['given_name'] ?? '',
                'last_name'  => $data['family_name'] ?? '',
            ];
        }

        // Fallback: accessToken via userinfo
        return $this->getOAuthUser('google', $token);
    }

    private function getTikTokUserFromCode(string $code): array
    {
        $clientKey    = env('TIKTOK_CLIENT_KEY', 'awyggtij427l6cqm');
        $clientSecret = env('TIKTOK_CLIENT_SECRET');

        if (!$clientSecret) {
            throw new \RuntimeException("TikTok non configuré (TIKTOK_CLIENT_SECRET manquant dans .env)");
        }

        $tokenResponse = \Illuminate\Support\Facades\Http::asForm()->post(
            'https://open.tiktokapis.com/v2/oauth/token/',
            [
                'client_key'    => $clientKey,
                'client_secret' => $clientSecret,
                'code'          => $code,
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => 'arcanatech://oauth/tiktok',
            ]
        );

        if (!$tokenResponse->successful()) {
            throw new \RuntimeException("Échec échange code TikTok");
        }

        $accessToken = $tokenResponse->json('data.access_token')
                    ?? $tokenResponse->json('access_token');

        if (!$accessToken) {
            throw new \RuntimeException("Access token TikTok manquant");
        }

        $userResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)
            ->get('https://open.tiktokapis.com/v2/user/info/', [
                'fields' => 'open_id,display_name',
            ]);

        if (!$userResponse->successful()) {
            throw new \RuntimeException("Échec récupération profil TikTok");
        }

        $userData = $userResponse->json('data.user') ?? [];
        $openId   = $userData['open_id'] ?? null;

        if (!$openId) {
            throw new \RuntimeException("Impossible de récupérer l'identifiant TikTok");
        }

        // TikTok ne fournit pas d'email — on construit un email synthétique stable
        return [
            'email'      => "tiktok_{$openId}@tiktok.arcanatech.app",
            'first_name' => $userData['display_name'] ?? 'Utilisateur',
            'last_name'  => 'TikTok',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // ═══ SÉLECTION UNIVERSITÉ (post-OAuth) ════════════════
    // ═══════════════════════════════════════════════════════

    public function selectUniversity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'university_id' => ['required', 'integer', 'exists:universites,id'],
        ]);

        $user = auth()->user();

        if ($user->universite_id !== null) {
            return response()->json(['message' => 'Université déjà configurée pour ce compte.'], 422);
        }

        $user->update(['universite_id' => $validated['university_id']]);

        $university = University::find($validated['university_id']);

        AuditLog::create([
            'universite_id'  => $validated['university_id'],
            'utilisateur_id' => $user->id,
            'type_ressource' => 'User',
            'id_ressource'   => $user->id,
            'action'         => 'select_university',
            'adresse_ip'     => $request->ip(),
            'nouvelles_valeurs' => ['universite_id' => $validated['university_id']],
        ]);

        return response()->json([
            'message' => 'Université sélectionnée avec succès.',
            'user'    => [
                'id'           => $user->id,
                'firstName'    => $user->prenom,
                'lastName'     => $user->nom,
                'email'        => $user->email,
                'role'         => $user->role,
                'universityId' => $user->universite_id,
                'university'   => $university ? [
                    'id'              => $university->id,
                    'name'            => $university->nom,
                    'code'            => $university->code,
                    'city'            => $university->ville,
                    'primaryColor'    => $university->couleur_principale,
                    'secondaryColor'  => $university->couleur_secondaire,
                    'accentPreset'    => $university->preset_accent,
                    'studentIdDigits' => $university->nb_chiffres_matricule,
                    'studentIdPrefix' => $university->prefixe_matricule,
                    'logoUrl'         => $university->url_logo,
                ] : null,
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════
    // ═══ MÉTHODES PRIVÉES ════════════════════════════════
    // ═══════════════════════════════════════════════════════

    private function generateSignedJwt(User $user, string $type, int $expiresIn): JwtToken
    {
        $secret = env('JWT_SECRET', config('app.key'));
        $now    = time();

        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'iat' => $now,
            'exp' => $now + $expiresIn,
            'jti' => Str::uuid()->toString(),
            'typ' => $type,
            'uid' => $user->universite_id,
            'rol' => $user->role,
        ];

        $tokenJwt = JWT::encode($payload, $secret, 'HS256');

        return JwtToken::create([
            'utilisateur_id'   => $user->id,
            'universite_id'    => $user->universite_id,
            'type'             => $type,
            'jeton'            => Str::random(64),
            'jeton_jwt'        => $tokenJwt,
            'expire_le'        => now()->addSeconds($expiresIn),
            'adresse_ip'       => request()->ip(),
            'agent_utilisateur'=> request()->userAgent(),
        ]);
    }

    private function getFrontendUrl(): string
    {
        // Priorité : variable dédiée FRONTEND_URL, sinon première origine CORS
        $url = env('FRONTEND_URL')
            ?? explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173'))[0];
        return rtrim($url, '/');
    }

    private function getOAuthScope(string $provider): string
    {
        return match ($provider) {
            'google' => 'openid email profile',
            default  => 'email',
        };
    }

    private function exchangeOAuthCode(string $provider, string $code): array
    {
        $providerUpper = strtoupper($provider);
        $tokenUrls     = [
            'google' => 'https://oauth2.googleapis.com/token',
        ];

        $response = \Illuminate\Support\Facades\Http::asForm()->post($tokenUrls[$provider], [
            'client_id'     => env("{$providerUpper}_CLIENT_ID"),
            'client_secret' => env("{$providerUpper}_CLIENT_SECRET"),
            'redirect_uri'  => env("{$providerUpper}_REDIRECT_URI"),
            'code'          => $code,
            'grant_type'    => 'authorization_code',
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Échec de l'échange du code OAuth: " . $response->body());
        }

        return $response->json();
    }

    private function getOAuthUser(string $provider, string $accessToken): array
    {
        $userInfoUrls = [
            'google' => 'https://www.googleapis.com/oauth2/v3/userinfo',
        ];

        $response = \Illuminate\Support\Facades\Http::withToken($accessToken)->get($userInfoUrls[$provider]);

        if (!$response->successful()) {
            throw new \RuntimeException("Échec de la récupération du profil OAuth");
        }

        $data = $response->json();

        return match ($provider) {
            'google' => [
                'email'      => $data['email'] ?? null,
                'first_name' => $data['given_name'] ?? '',
                'last_name'  => $data['family_name'] ?? '',
            ],
            default => [],
        };
    }
}
