<?php

namespace Database\Seeders;

use App\Models\Filiere;
use App\Models\Student;
use App\Models\University;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * StudentSeeder — 400 étudiants LCS répartis par promotion (filière × niveau)
 *
 * Règle métier respectée :
 *   Un étudiant appartient à UNE promotion = filière + niveau.
 *   Il est impossible d'avoir un L1 et un L3 dans la même promotion.
 *
 * Répartition : 18 promotions (6 filières × 3 niveaux)
 *   L1 ≈ 50 %  |  L2 ≈ 30 %  |  L3 ≈ 20 %
 */
class StudentSeeder extends Seeder
{
    // ── Prénoms masculins ──────────────────────────────────────────
    private array $prenomsMasculins = [
        'Yaovi','Kossi','Kofi','Koffi','Kwami','Kodzo','Kokou','Kossivi',
        'Edem','Messan','Séna','Mawuli','Ayité','Kafui','Togbé',
        'Rodrigue','Wilfried','Joël','Gédéon','Didier','Franck','Steve',
        'Abdoulaye','Moustapha','Ibrahim','Issiaka','Saliou','Hamidou',
        'Prosper','Symphorien','Théodore','Alphonse','Barnabé','Clément',
        'Désiré','Edmond','Firmin','Gaston','Hervé','Innocent',
        'Jérôme','Léonard','Mathieu','Narcisse','Olivier','Pascal',
        'Raphaël','Séverin','Thierry','Urbain','Valentin','Yannick',
        'Zacharie','Amédée','Bertrand','Cyrille','Donald','Ernest',
        'Florent','Grégoire','Hubert','Idris','Jonas','Kevin',
        'Laurent','Marcel','Nathan','Octave','Patrick','Raoul',
        'Serge','Thomas','Ulrich','Victor','William','Yves',
        'Zéphirin','Arsène','Blaise','Casimir','Damien','Eugène',
        'Félix','Gilles','Honoré','Irénée','Justin','Lucien',
        'Martin','Noël','Oswald','Romuald','Stanislas','Timothée',
    ];

    // ── Prénoms féminins ───────────────────────────────────────────
    private array $prenomsFeminins = [
        'Afiwa','Akossiwa','Afi','Ama','Abla','Efua','Sewa','Abiba',
        'Yawa','Akouavi','Mawunyo','Dzidzor','Kafui','Ayélé','Séssévi',
        'Fatoumata','Mariama','Aminata','Bintou','Kadiatou','Salamatou',
        'Ghislaine','Rolande','Christelle','Estelle','Lauriane','Clarisse',
        'Eugénie','Andrée','Bernadette','Cécile','Dominique','Élise',
        'Florence','Gabrielle','Hortense','Irène','Joëlle','Karine',
        'Lucie','Madeleine','Nicole','Odile','Pauline','Rosine',
        'Sandrine','Thérèse','Ursule','Véronique','Yolande','Zoé',
        'Adèle','Brigitte','Colette','Delphine','Émeline','Françoise',
        'Geneviève','Hélène','Isabelle','Judith','Laetitia','Martine',
        'Nadège','Ophélie','Priscille','Régine','Stéphanie','Tatiana',
        'Victoire','Wilmine','Yvette','Zara','Aïcha','Balkissa',
        'Coumba','Djeneba','Fanta','Hadja','Inna','Korotoumou',
        'Lala','Mina','Nana','Oumou','Penda','Rokhaya',
        'Seynabou','Tanti','Ulrika','Viviane','Wéba','Xoèse',
    ];

    // ── Noms de famille ────────────────────────────────────────────
    private array $noms = [
        'AHOUANSOU','GNANCADJA','DJOSSOU','HOUNGBO','GNIMAVO','AGOSSOU',
        'HOUNSOU','KPOSSOU','ADJIBODÉ','HOUÉNOU','TCHÉGOUN','ZANNOU',
        'ADANHOUMÈ','TOFINLON','HOUNHTON','ATCHADÉ','DOSSOU','DOSSAVI',
        'TODJINOU','AÏGBÉ','ALIKPO','SAGBOHAN','LAGNIDE','GLÈLÈ',
        'KOUPAKI','MÈDÉGAN','AKOWANOU','AGBOSSOU','HOUNSSOU','GBÉNOU',
        'TOSSOU','AKAKPO','AHOSSOU','DÉGBÉVI','DJÈNONTIN','GBAGUIDI',
        'HOUÉDANOU','AHOSSUGBÉ','AZONHIHO','BAKARY','CHABI','DADO',
        'EDAH','FAGNON','GANGNON','HESSOU','IMOROU','JOSSOU',
        'KIKI','LOKO','MIGAN','NOUDÉHOU','ODJO','PADONOU',
        'QUENUM','RAIMI','SOSSOU','TALON','VODOUN','WHANNOU',
        'ZINSOU','AHOUNOU','BELLO','CODJIA','DÉGLA','ELEGBEDE',
        'FAGBOHOUN','GANGBE','HOLO','IDRISSOU','JOSEPH','KINDÉ',
        'LÉMOU','MAKPO','NIGNAN','OLOUFA','POKOU','SANNI',
        'TAÏROU','USSOU','VIGAN','WOROU','YAYA','ZINZINDOHOUÉ',
        'AÏDÉ','BIAOU','CODJA','DÈDÈHOU','FANDOHAN','GANDO',
        'HOUNKON','ISSIFO','JOBI','KPAKPO','LOKOSSOU','MOLOU',
        'NOUDÉKA','OGOUNÉ','PÈLÈBÈ','RAÏNATOU','SAMBIÉNI','TOFFA',
        'HOUEHA','AGBAYIZO','BAMISSO','CAPO','DANHA','EZIN',
        'FIOGBÉ','GNONLONFOUN','HOUNGUÈ','IMOUKHOUÉDÉ','JÈBI','KANGNI',
    ];

    public function run(): void
    {
        $university = University::where('code', 'LCS')->first();
        $annee      = now()->year;

        // Filières indexées par code
        $filieres = Filiere::where('universite_id', $university->id)
            ->get()
            ->keyBy('code');

        /*
         * Distribution : 400 étudiants dans 18 promotions
         * Filières × niveaux avec répartition L1 > L2 > L3
         */
        $distribution = [];
        $codesNiveaux = [
            'L1' => 12,  // ~50 % (12/20 des slots par filière)
            'L2' => 5,   // ~25 %
            'L3' => 3,   // ~15 %
        ];
        $filiereCodes = ['SJ', 'SEG', 'SA', 'ANG', 'GAT', 'CJ'];

        // Remplir les 400 slots équitablement
        // Chaque filière reçoit ≈ 66 étudiants (400 / 6 ≈ 66)
        // Répartis : L1 ≈ 40, L2 ≈ 15, L3 ≈ 11
        // Chaque promotion (filière + niveau) est indépendante : SJ-L1, SJ-L2, SJ-L3, etc.
        $slotsParFiliere = [
            'SJ'  => ['L1' => 44, 'L2' => 19, 'L3' => 12],  // 75
            'SEG' => ['L1' => 50, 'L2' => 21, 'L3' => 14],  // 85
            'SA'  => ['L1' => 33, 'L2' => 14, 'L3' => 9],   // 56
            'ANG' => ['L1' => 33, 'L2' => 14, 'L3' => 9],   // 56
            'GAT' => ['L1' => 37, 'L2' => 16, 'L3' => 10],  // 63
            'CJ'  => ['L1' => 37, 'L2' => 16, 'L3' => 12],  // 65  → Total 400
        ];

        // Construire la liste de (filiereCode, niveau) pour les 400 étudiants
        $assignements = [];
        foreach ($slotsParFiliere as $fCode => $niveaux) {
            foreach ($niveaux as $niveau => $count) {
                for ($i = 0; $i < $count; $i++) {
                    $assignements[] = ['filiere' => $fCode, 'niveau' => $niveau];
                }
            }
        }
        shuffle($assignements);

        $usedEmails = [];
        $index = 1;

        foreach ($assignements as $assign) {
            $filiereCode = $assign['filiere'];
            $niveau      = $assign['niveau'];
            // Lookup par la filière spécifique au niveau : ex. "SJ-L1", "SEG-L2"
            $filiere     = $filieres[$filiereCode . '-' . $niveau] ?? null;

            $isFemale = ($index % 2 === 0);
            $prenoms  = $isFemale ? $this->prenomsFeminins : $this->prenomsMasculins;
            $prenom   = $prenoms[array_rand($prenoms)];
            $nom      = $this->noms[array_rand($this->noms)];
            $matricule = 'SON' . str_pad($index, 8, '0', STR_PAD_LEFT);

            // Email sans accents
            $raw = mb_strtolower($prenom[0] . '.' . $nom, 'UTF-8');
            $accents = ['à','â','ä','é','è','ê','ë','î','ï','ô','ö','ù','û','ü','ÿ','ç','œ','æ','ñ','ã','õ','ì','í','ó','ò','ú','ý'];
            $sans    = ['a','a','a','e','e','e','e','i','i','o','o','u','u','u','y','c','oe','ae','n','a','o','i','i','o','o','u','y'];
            $raw = str_replace($accents, $sans, $raw);
            $baseEmail = preg_replace('/[^a-z0-9.]/', '', $raw);
            $email = $baseEmail . '@etud.lcs.edu';
            if (isset($usedEmails[$email])) {
                $email = $baseEmail . $index . '@etud.lcs.edu';
            }
            $usedEmails[$email] = true;

            $user = User::create([
                'universite_id' => $university->id,
                'prenom'        => $prenom,
                'nom'           => $nom,
                'email'         => $email,
                'password'      => 'Student@2024!',
                'role'          => 'student',
                'est_actif'     => true,
            ]);

            Student::create([
                'universite_id'     => $university->id,
                'utilisateur_id'    => $user->id,
                'numero_matricule'  => $matricule,
                'niveau'            => $niveau,           // niveau = niveau de la promotion
                'annee_inscription' => $annee,
                'filiere_id'        => $filiere?->id,
            ]);

            $index++;
        }
    }
}
