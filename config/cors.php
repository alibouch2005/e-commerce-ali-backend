<?php
 // ce file contient la configuration de CORS (Cross-Origin Resource Sharing) pour l'application Laravel. CORS est un mécanisme de sécurité qui permet ou restreint les ressources d'une page web à être demandées à partir d'un domaine différent de celui de la page.
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],// les chemins pour lesquels les règles CORS s'appliquent
    'allowed_methods' => ['*'],// les méthodes HTTP autorisées (GET, POST, etc.)
    'allowed_origins' => ['http://localhost:3000'],// les origines autorisées à accéder aux ressources (dans ce cas, le frontend en local)
    'allowed_headers' => ['*'],// les en-têtes HTTP autorisés dans les requêtes
    'exposed_headers' => [],// les en-têtes HTTP exposés dans les réponses
    'max_age' => 0,// le temps en secondes pendant lequel les résultats d'une requête pré-vol peuvent être mis en cache
    'supports_credentials' => true,// indique si les requêtes peuvent inclure des informations d'identification (cookies, etc.)
];