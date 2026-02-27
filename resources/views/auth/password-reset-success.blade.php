<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mot de passe réinitialisé</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-400 via-emerald-500 to-teal-600">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8 text-center">

        <!-- Icon -->
        <div class="flex justify-center mb-4">
            <div class="bg-green-100 p-4 rounded-full">
                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>

        <!-- Title -->
        <h2 class="text-2xl font-bold text-gray-800 mb-3">
            Mot de passe réinitialisé !
        </h2>

        <!-- Message -->
        <p class="text-gray-600 mb-6">
            Votre mot de passe a été modifié avec succès.
            Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.
        </p>

        <!-- Button -->
        <a href="{{ url('/login') }}"
           class="inline-block w-full bg-green-600 text-white py-2 rounded-lg font-semibold hover:bg-green-700 transition duration-300 shadow-lg">
            Aller à la connexion
        </a>

    </div>

</body>
</html>