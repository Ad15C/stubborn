# Stubborn – Application e-commerce Symfony

Application e-commerce développée en PHP permettant de parcourir et acheter des sweat-shirts de la marque fictive Stubborn.

Le projet inclut :
- un système d’authentification utilisateur
- une gestion du panier
- un paiement test avec Stripe
- un backoffice administrateur
- la gestion des produits et des stocks

# Technologies utilisées
- PHP
- Twig
- Symfony
- Stripe
- HTML/CSS
- JavaScript
- MySQL
- Git & GitHub

# Compétences développées
- Développement backend avec Symfony
- Gestion des utilisateurs
- Authentification et sécurité
- Intégration de Stripe
- Gestion de panier e-commerce
- Gestion de stock
- Création d'un backoffice administrateur
- Architecture MVC
- Utilisation de Twig

# Fonctionnalités principales

## Utilisateurs
- inscription
- connexion
- activation de compte par email
- gestion des sessions

## Boutique
- affichage des produits
- page produit détaillée
- sélection des tailles
- ajout au panier

## Panier
- suppression de produits
- calcul du panier
- paiement via Stripe

## Backoffice
- ajout / modification / suppression des produits
- gestion des stocks
- gestion des produits mis en avant

# Structure du projet
```text
stubborn/
├── src/
├── templates/
├── public/
├── config/
├── migrations/
├── tests/
└── README.md
```

# Sécurité

Les données sensibles (clés API Stripe, accès base de données, variables d’environnement) ne sont pas incluses dans le repository.


# Installation et exécution

1. **Cloner le projet**
```bash
git clone <URL_DU_REPO_GITHUB>
cd nom_du_projet
```

2. **Installer les dépendances**
```bash
composer install
```

3. Configurer l'environnement
Créer un fichier **.env.local** et configurer:
- la base de données
- les clés Stripe

4. Lancer le serveur
```bash
symfony server:start
```

# Contact
📧 ad15canon@gmail.com
