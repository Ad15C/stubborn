# Application e-commerce Stubborn

## Description
Stubborn Sweatshirts est une application e-commerce locale permettant de parcourir et acheter des sweat-shirts de la marque Stubborn. L'application inclut un système de gestion des utilisateurs, un panier interactif, et un backoffice pour les administrateurs.

Elle est développée dans le cadre d’un projet pédagogique et inclut les fonctionnalités suivantes :
- Gestion des utilisateurs (inscription, connexion, activation par email)
- Parcours des produits
- Gestion du panier et achat-test via Stripe
- Backoffice pour gérer les produits

---

## Fonctionnalités principales

### Pages utilisateurs
- **Page d’accueil (`/`)**
  - Menu dynamique selon l’état de connexion
    - Non connecté : Accueil / Se connecter / S’inscrire
    - Connecté : Accueil / Boutique / Panier / Se déconnecter
  - Trois sweat-shirts mis en avant
  - Présentation courte de la société Stubborn

- **Page de connexion (`/login`)**
  - Formulaire de connexion
  - Liens vers l’accueil et vers l’inscription

- **Page d’inscription (`/register`)**
  - Formulaire d’inscription
  - Activation du compte par email
  - Liens vers la page de connexion

- **Page Produits (`/products`)**
  - Liste complète des sweat-shirts avec images, noms et prix
  - Bouton « Voir » pour chaque produit

- **Page Produit (`/product/id`)**
  - Détails du sweat-shirt (image, nom, prix)
  - Sélection de la taille
  - Bouton « Ajouter au panier »

- **Page Panier (`/cart`)**
  - Liste des produits ajoutés
  - Bouton pour supprimer un produit
  - Lien pour continuer les achats
  - Paiement test via Stripe

### Backoffice (`/admin`)
- Accessible uniquement aux administrateurs
- Gestion complète des sweat-shirts :
  - Ajouter / modifier / supprimer un sweat-shirt
  - Gérer la mise en avant sur la page d’accueil
  - Mettre à jour les stocks par taille

---

## Installation et exécution

1. **Cloner le projet**
```bash
git clone <URL_DU_REPO_GITHUB>
cd nom_du_projet

Pour toutes informations:
Adeline CANON
ad15canon@gmail.com