<x-mail::message>

{{-- Logo / Nom du shop --}}
# 🛍️ {{ config('app.name') }}

{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
# Bonjour 👋
@endif

---

{{-- Message principal --}}
Vous avez demandé la réinitialisation de votre mot de passe sur votre compte client.

Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe en toute sécurité 🔐.

---

{{-- Bouton action --}}
@isset($actionText)
<?php
    $color = 'primary';
?>
<x-mail::button :url="$actionUrl" :color="$color">
🔑 Réinitialiser mon mot de passe
</x-mail::button>
@endisset

---

{{-- Info sécurité --}}
⚠️ **Important :**
- Ce lien expirera dans quelques minutes.
- Si vous n’êtes pas à l’origine de cette demande, ignorez cet email.

---

{{-- Footer --}}
Merci de faire confiance à **{{ config('app.name') }}** ❤️  
Votre boutique en ligne préférée.

---

{{-- Salutation --}}
Cordialement,  
L’équipe {{ config('app.name') }}

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :

👉 {{ $actionUrl }}
</x-slot:subcopy>
@endisset

</x-mail::message>