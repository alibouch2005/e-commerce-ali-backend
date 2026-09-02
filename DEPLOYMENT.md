# Deploiement AliShop

1. Definir `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` et une `APP_KEY` unique.
2. Configurer MySQL et `FRONTEND_URLS` avec l'URL exacte du frontend. Mettre aussi ce domaine dans `SANCTUM_STATEFUL_DOMAINS` si l'authentification par cookie est utilisee.
3. Configurer le frontend avec `VITE_API_URL=https://api.votre-domaine.tld`.
4. Executer :

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

5. Configurer CMI avant d'activer le paiement carte :

```env
CMI_CLIENT_ID=identifiant_fourni_par_cmi
CMI_STORE_KEY=cle_secrete_fournie_par_cmi
CMI_GATEWAY_URL=https://payment.cmi.co.ma/fim/est3Dgate
CMI_OK_URL="${APP_URL}/api/payments/cmi/ok"
CMI_FAIL_URL="${APP_URL}/api/payments/cmi/fail"
CMI_CALLBACK_URL="${APP_URL}/api/payments/cmi/callback"
CMI_CURRENCY=504
CMI_TEST_MODE=false
```

6. Verifier que le domaine API et le domaine frontend utilisent HTTPS.
7. Verifier que `storage:link` fonctionne pour les images produits et preuves de livraison.
8. Tester sur mobile avant ouverture :

```bash
npm run build
php artisan test --filter=CommerceFlowTest
```

9. Tester manuellement :

- Ajouter un produit sans compte, puis creer un compte : le panier doit rester rempli.
- Choisir paiement carte : redirection CMI attendue.
- Choisir retrait magasin : la carte magasin doit s'afficher.
- Livreur sur telephone : la preuve doit ouvrir la camera et envoyer la photo.
- Admin : produit, promo, stock faible, commandes, support et recu PDF.

10. Configurer une file de jobs persistante et un service de mail transactionnel avant d'activer les emails clients.

Ne jamais committer le fichier `.env` ni des cles de paiement.
