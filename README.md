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

 ## Dépôt Sécurisé de Documents : 
 Téléversement confidentiel des échographies et bilans sanguins.

# 2-Tecnologie Utilisées 

## Couche et Technologies

| Couche          | Technologies & outils |
|----------------|-----------------------|
| Frontend       | HTML5 sémantique, CSS3, Tailwind CSS (CDN / fichier local), JavaScript Vanilla (ES6+) |
| Backend        | PHP 8.x orienté objet, architecture MVC légère, PDO, API REST |
| Base de données| PostgreSQL, schéma relationnel strict, types ENUM, contraintes CHECK |
| Sécurité       | Vérification stricte des MIME types, .htaccess anti-exécution, JWT/token de session, table d'audit complète |
| Hébergement    | Frontend : GitHub Pages, Backend/DB : Render ou Railway |


# 3- Architecture du Projet

```text
MaterniteCare/
├── frontend/                 # Interface utilisateur (Vanilla JS + Tailwind)
│   ├── index.html            # Portail public patiente
│   ├── dashboard.html        # Tableau de bord soignants
│   ├── css/                  # Styles (style.css, tailwind.min.css)
│   ├── js/                   # Logique métier (api.js, auth.js, critical_patients.js...)
│   └── assets/               # Images et icônes
│
├── backend/                  # API REST PHP
│   ├── index.php             # Routeur principal (Front Controller)
│   ├── config.php            # Connexion PDO PostgreSQL
│   ├── controllers/          # Logique métier (Auth, Patiente, Document, etc.)
│   ├── models/               # Couche d'accès aux données (ORM léger)
│   ├── middleware/           # Sécurité (auth.php, cors.php, roles.php)
│   ├── routes/               # Déclaration des endpoints API
│   ├── uploads/              # Stockage sécurisé (protégé par .htaccess)
│   └── logs/                 # Journalisation des erreurs (errors.log)
│
├── database/                 # Gestion de la base de données
│   ├── schema/               # Scripts de création incrémentaux (01 à 14)
│   ├── seeds/                # Données de test réalistes (Pointe-Noire)
│   └── MaterniteCare_Tables.sql # Script maître consolidé et corrigé
│
├── docs/                     # Modélisation (MCD, MLD, UML, API_Documentation.md)
├── tests/                    # Collections Postman et tests API HTTP
├── .env.example              # Modèle de variables d'environnement
├── .gitignore                # Exclusion des fichiers sensibles
└── README.md                 # Ce fichier
```

##  4. Modélisation de la Base de Données (PostgreSQL)

La structure des données a été conçue pour garantir l'intégrité référentielle, l'isolation des Workspaces et la traçabilité des actions médicales. Le schéma relie de manière stricte les entités du projet.

### Modèle Conceptuel de Données (MCD)
Voici la représentation visuelle des entités et de leurs relations (générée via StartUML) :

![Modèle Conceptuel de Données (MCD) de MaterniteCare](docs/B:\MaterniteCare\docs\MaterniteCare.jpg)


### Script de Création des Tables
Le schéma relationnel complet, incluant les types ENUM, les contraintes `CHECK` (poids, taille, score Apgar) et les clés étrangères, est disponible dans le dépôt.

🔗 **[Voir le script SQL complet : `Database/MaterniteCare_Tables.sql`](Database/MaterniteCare_Tables.sql)**

