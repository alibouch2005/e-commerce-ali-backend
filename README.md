# 🛒 E-commerce API – Backend (Laravel)

![PHP](https://img.shields.io/badge/PHP-8.2+-blue?logo=php)
![Laravel](https://img.shields.io/badge/Laravel-12-red?logo=laravel)
![MySQL](https://img.shields.io/badge/MySQL-Database-blue?logo=mysql)
![Sanctum](https://img.shields.io/badge/Auth-Sanctum-green)
![Status](https://img.shields.io/badge/Status-Completed-brightgreen)

---

## 📌 Description

Backend d’une application **E-commerce** développée avec **Laravel**, permettant la gestion complète de :

- 🛍️ Produits et catégories  
- 🧺 Panier  
- 📦 Commandes  
- 🚚 Livraison  
- 👥 Utilisateurs et rôles  

Le projet est inspiré du fonctionnement de plateformes comme **Marjane**.

---

## 🎯 Objectifs

- Permettre l’achat de produits en ligne  
- Gérer les commandes et leur cycle de vie  
- Suivre la livraison des commandes  
- Gérer les produits, catégories et stock  
- Sécuriser les accès avec rôles  

---

## 🧱 Architecture

Le projet respecte l’architecture **MVC (Model – View – Controller)** avec ORM Eloquent.

### 🔄 Workflow

Client → Route → Controller → Model → Database → JSON Response

---

## 🔐 Authentification (Laravel Sanctum)

- Inscription (Register)  
- Connexion (Login)  
- Déconnexion (Logout)  
- Protection CSRF  
- Authentification via cookies sécurisés  
- Hash des mots de passe  
- Validation avec FormRequest  

---

## 👥 Gestion des rôles (RBAC)

Trois rôles :

- 👑 Admin  
- 👤 Client  
- 🚚 Livreur  

### 🔒 Sécurité

- Middleware `auth:sanctum`  
- Middleware `role` personnalisé  
- Accès contrôlé aux routes  

### Codes HTTP

- 401 → Non authentifié  
- 403 → Non autorisé  

---

## 👤 Gestion utilisateur

- 🔍 Voir profil  
- ✏️ Modifier profil  
- 🔐 Changer mot de passe  
- 🗑 Supprimer compte  

---

## 🛍️ Catalogue

- Catégories (CRUD – Admin)  
- Produits (CRUD – Admin)  
- Consultation publique  

---

## 🧺 Panier

- Ajouter produit  
- Modifier quantité  
- Supprimer produit  
- Vider panier  

---

## 📦 Commandes

- Création commande (checkout)  
- Consultation des commandes  
- Mise à jour du statut :

En attente → Préparation → Expédié → Livré

---

## 🚚 Livraison

- Consultation des commandes assignées  
- Mise à jour du statut (livré)  

---

## ⚙️ Fonctionnalités avancées

### 🔁 Trigger (MySQL)
Réduction automatique du stock lors du passage :
En attente → Préparation

---

### ⚙️ Procédure stockée

- Statistiques des commandes  
- Filtrage par statut  

---

### 📄 Génération PDF

- Facture de commande  

---

### 📊 Graphiques

- Commandes par statut  
- Statistiques de vente  

---

### 📧 Envoi d’email

- Confirmation de commande  
- Notifications utilisateur  

---

### 🌍 Multilangue

- Support Français / Arabe  
- Backend + Frontend  

---

### 🌱 Seeder & Factory

- Génération de données de test  
- Users / Produits / Commandes  

---

## 🗄 Base de données

- Migrations Laravel  
- Contraintes (foreign keys, unique)  
- Relations :
  - User → Orders  
  - Order → OrderItems  
  - Product → Category  

---

## 🛠 Technologies

- PHP 8.2+  
- Laravel 12  
- MySQL  
- Laravel Sanctum  
- Postman  
- Git & GitHub  
- XAMPP  

---

## 🚀 Installation

git clone https://github.com/your-repo/ecommerce-api.git  
cd ecommerce-api  

composer install  
cp .env.example .env  
php artisan key:generate  

php artisan migrate --seed  

php artisan serve  

---

## 🧪 Tests API

Utiliser Postman pour tester :

- Auth  
- Produits  
- Panier  
- Commandes  

---

## 📐 Modélisation

- MCD  
- MPD  
- Use Case  
- Diagramme de classe  
- Diagramme de séquence  

---

## 📚 Conclusion

Ce projet représente une solution complète de gestion e-commerce avec :

- Architecture MVC  
- Sécurité avancée  
- Gestion des rôles  
- Fonctionnalités métier complètes  

Il constitue une base solide pour un système professionnel.

---

## ✍️ Auteur

ALI BOUCHOUAR  
2025 / 2026  
