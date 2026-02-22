
# 🛒 E-commerce API – Gestion des Commandes et Livraison

![PHP](https://img.shields.io/badge/PHP-8.2+-blue?logo=php)
![Laravel](https://img.shields.io/badge/Laravel-12-red?logo=laravel)
![MySQL](https://img.shields.io/badge/MySQL-Database-blue?logo=mysql)
![Sanctum](https://img.shields.io/badge/Auth-Sanctum-green)
![License](https://img.shields.io/badge/License-MIT-yellow)
![Status](https://img.shields.io/badge/Status-In%20Progress-orange)

Backend de l'application **E-commerce**, une plateforme de vente en ligne avec gestion des **produits**, **commandes** et **livraisons**, inspirée du fonctionnement du site **Marjane**.  
Cette API permet aux **clients** de commander des produits en ligne et à un **administrateur** de gérer l’ensemble du système.

Cette API est construite avec **Laravel**.

---
## 🎯 Objectifs

- Permettre l’achat de produits en ligne
- Gérer les commandes et la livraison
- Gérer les produits, catégories et stock
- Offrir une interface client et une interface administrateur
- Suivre l’état des commandes et livraisons
  
---
🔐 Système d’authentification (Laravel Sanctum – Stateful Cookies)

Inscription (Register)
Connexion (Login)
Déconnexion (Logout)
Protection CSRF
Authentification via cookies sécurisés
Hash sécurisé des mots de passe
Validation via FormRequest

👥 Gestion des rôles (RBAC)

Trois rôles sont implémentés :

👑 Admin

👤 Client

🚚 Livreur

Middleware personnalisé RoleMiddleware
Protection des routes par rôle
Support multi-rôles (role:admin,livreur)
Codes HTTP appropriés :
401 → Non authentifié
403 → Non autorisé

👤 Gestion du compte utilisateur

Fonctionnalités disponibles :

🔍 Voir son profil
✏️ Modifier son profil
🔐 Changer son mot de passe (avec vérification current_password)
🗑 Supprimer son compte (avec destruction de session)

Validation sécurisée via :
UpdateRequest
ChangePasswordRequest

---

## 🛠 Technologies utilisées

PHP 8.2+
Laravel 12
MySQL
Laravel Sanctum
Postman (tests API)
Git & GitHub
XAMPP
Visual Studio Code


## 🔐 Sécurité

Authentification Sanctum (stateful)
Middleware auth:sanctum
Middleware role
Validation via FormRequest
Vérification du mot de passe actuel
Sessions invalidées après logout ou suppression de compte

---

## 📚 Conclusion

Ce projet e-commerce permet une gestion complète des ventes en ligne, des commandes et des livraisons.  
Il constitue une base solide pour un site de vente en ligne professionnel, inspiré du modèle de Marjane.

---
## ✍️ Auteur

Projet réalisé par : **[ALI BOUCHOUAR]**  
Année : **2025 / 2026**

