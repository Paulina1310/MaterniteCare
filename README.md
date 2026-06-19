# -MaterniteCare 🩺👩🏽‍🍼

Plateforme de suivi Obstétrical et de gestion hospitalière.. Cette plateforme clinique et 
hospitalière est dédiée au suivi rigoureux des patientes (suivi prénatal, accouchements, séjours 
en obstétrique) et à la gestion opérationnelle du personnel médical au sein d'un centre de 
maternité

# 1-Fonctionnalités Clés

 ## -Côté Personnel Médical (Interne)
Tableau de bord par Workspace : Isolement des données par unité de soins (Consultations, Bloc, Post-Partum).
Code Couleur d'Urgence :

 ## -Identification visuelle immédiate des statuts :

🔴 Travail actif/Détresse, 
🔵 Observation, 
🟢 Stable/Sortie


## Niveau de Risque Obstétrical : 

Classification des grossesses 
🔴 Haut risque, 
🟠 Surveillance, 
⚪ Physiologique.

## Recherche Avancée & Corrélation : 

Moteur de recherche transversal scannant les symptômes et constantes pour identifier des risques sériels dans tout le workspace.

Gestion des Urgences (LocalStorage) : Système de "drapeau" pour ancrer les dossiers critiques en haut du tableau de bord lors des relèves d'équipe.

## -Côté Patiente (Public)

## Portail Mobile-First : 
Interface ultra-légère adaptée aux connexions instables.

 ## Suivi Personnalisé :
  Consultation des rendez-vous, rappels de vaccination et ordonnances via un code de suivi unique.

 ## Dépôt Sécurisé de Documents: 
 Téléversement confidentiel des échographies et bilans sanguins.

## 2-Tecnologie Utilisées 

## Couche et Technologies

| Couche          | Technologies & outils |
|----------------|-----------------------|
| Frontend       | HTML5 sémantique, CSS3, Tailwind CSS (CDN / fichier local), JavaScript Vanilla (ES6+) |
| Backend        | PHP 8.x orienté objet, architecture MVC légère, PDO, API REST |
| Base de données| PostgreSQL, schéma relationnel strict, types ENUM, contraintes CHECK |
| Sécurité       | Vérification stricte des MIME types, .htaccess anti-exécution, JWT/token de session, table d'audit complète |
| Hébergement    | Frontend : GitHub Pages, Backend/DB : Render ou Railway |


## 3- Architecture du Projet

```text
MaterniteCare/

├── ├── Frontend/
│   ├── Asset/
│   │   └── logo-maternite.png
│   ├── CSS/
│   │   ├── admin.css
│   │   ├── login.css
│   │   ├── medecin.css
│   │   └── patiente.css
│   ├── HTML/
│   │   ├── admin_dash.html
│   │   ├── dash_patiente.html
│   │   ├── inscription.html
│   │   ├── login.html
│   │   └── medecin_dash.html
│   └── JS/
│       ├── admin.js
│       ├── api.js
│       ├── dash.js
│       ├── login.js
│       ├── medecin.js
│       └── patiente.js              
│
├── Backend/                          # API REST PHP (PDO + PostgreSQL)
│   │
│   ├── config/
│   │   └── database.php              # Connexion PDO à PostgreSQL (lecture du .env)
│   │
│   ├── API/                          # Tous les endpoints REST (CRUD complet)
│   │   ├── auth.php                  # Authentification (login/logout/token)
│   │   ├── personne.php              # Gestion des identités civiles
│   │   ├── grossesse.php             # Suivi des grossesses
│   │   ├── consultation.php          # Consultations prénatales
│   │   ├── admission.php             # Admissions en maternité
│   │   ├── accouchement.php          # Accouchements
│   │   ├── lit.php                   # Gestion des lits
│   │   └── nouveau_ne.php            # Fiches nouveau-nés
│   │
│   ├── .env                          # Variables d'environnement (DB credentials)
│   ├── mot_de_passe.php               # Script utilitaire : hash bcrypt de mots de passe
│   
│   
│
├── database/                 # Gestion de la base de données
│   ├── schema/               # Scripts de création incrémentaux (01 à 14)
│   ├── seeds/                # Données de test réalistes (Pointe-Noire)
│   └── MaterniteCare_Tables.sql # Script maître consolidé et corrigé
    ── MaterniteCare_starUML # Modélisation du projet│
├            
├── tests/                    # Collections Postman et tests API HTTP
├── .env.example              # Modèle de variables d'environnement
├── .gitignore                # Exclusion des fichiers sensibles
└── README.md                 # Ce fichier
```




###  URLs de test (environnement local)

| API | URL de test |
| :--- | :--- |
| **Auth** | `http://localhost/MaterniteCare/Backend/API/auth.php` |
| **Personne** | `http://localhost/MaterniteCare/Backend/API/personne.php` |
| **Grossesse** | `http://localhost/MaterniteCare/Backend/API/grossesse.php` |
| **Consultation** | `http://localhost/MaterniteCare/Backend/API/consultation.php` |
| **Admission** | `http://localhost/MaterniteCare/Backend/API/admission.php` |
| **Accouchement** | `http://localhost/MaterniteCare/Backend/API/accouchement.php` |
| **Lit** | `http://localhost/MaterniteCare/Backend/API/lit.php` |
| **Nouveau-né** | `http://localhost/MaterniteCare/Backend/API/nouveau_ne.php` |


##  4. Modélisation de la Base de Données (PostgreSQL)

La structure des données a été conçue pour garantir l'intégrité référentielle, l'isolation des Workspaces et la traçabilité des actions médicales. Le schéma relie de manière stricte les entités du projet.

### Modèle Conceptuel de Données (MCD)
Voici la représentation visuelle des entités et de leurs relations (générée via StartUML) :


### Script de Création des Tables
Le schéma relationnel complet, incluant les types ENUM, les contraintes `CHECK` (poids, taille, score Apgar) et les clés étrangères, est disponible dans le dépôt.

**[Voir le script SQL complet : `Database/MaterniteCare_Tables.sql`](Database/MaterniteCare_Tables.sql)**


## 5. Guide de Test

### Configuration de l'environnement

Ce projet utilise **XAMPP** pour l'environnement de développement local. Pour tester l'application :

1. Assurez-vous que XAMPP est installé et que les services Apache et PostgreSQL sont démarrés
2. Placez le dossier `MaterniteCare` dans le dossier `htdocs` de XAMPP (`C:\xampp\htdocs\MaterniteCare`)
3. Accédez à l'application via l'URL : `http://localhost/MaterniteCare`
4. Naviguez dans le dossier `Frontend/HTML` pour accéder aux pages de l'application

### Connexion à l'application

Pour vous connecter, ouvrez le fichier `login.html` dans votre navigateur.

**Comptes de test disponibles :**

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| **Patiente (Claire Dubois)** | claire.dubois@email.com | Patient123! |
| **Patiente (Laura Petit)** | laura.petit@email.com | Patient123! |
| **Médecin (Dr. Dupont)** | dr.dupont@maternite.com | Medecin123! |
| **Administrateur** | admin@maternite.com | Admin123! |

### Dashboard Patiente

Le dashboard patiente permet aux patientes de :
- Consulter les informations de leur grossesse (trimestre, date d'accouchement prévue, niveau de risque)
- Voir leurs rendez-vous à venir
- Accéder à l'historique complet de leurs consultations
- Déposer des documents médicaux (échographies, bilans sanguins)
- Faire une auto-évaluation de leurs symptômes (en cours d'evolution)

**Exemples de patientes :**
- **Claire Dubois** : Patiente avec une grossesse en cours, plusieurs rendez-vous planifiés et des consultations médicales enregistrées
- **Laura Petit** : Patiente avec un dossier médical complet

### Statut des Dashboards

- **Dashboard Patiente** : Fonctionnel sur certaines sections pour le moment
- **Dashboard Médecin** : En maintenance
- **Dashboard Administrateur** : En maintenance

### Note sur le projet

Ce projet a été développé dans le cadre d'un projet académique avec une deadline stricte. En raison de contraintes de temps, le projet n'a pas pu être entièrement abouti jusqu'à la fin. Cependant, il reste en maintenance et ouvert à toute modification et amélioration future.

Les fonctionnalités principales du dashboard patiente sont opérationnelles et peuvent être testées avec les comptes fournis ci-dessus.
