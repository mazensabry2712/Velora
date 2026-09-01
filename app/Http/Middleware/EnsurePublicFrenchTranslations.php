<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\HttpFoundation\Response;

class EnsurePublicFrenchTranslations
{
    public function handle(Request $request, Closure $next): Response
    {
        // The public locale middleware runs later in the pipeline, so app()->getLocale()
        // may still be the default locale when this middleware executes. Prefer the
        // locale encoded in the public URL and fall back to the current app locale.
        $requestedLocale = strtolower((string) $request->segment(1));
        $locale = $requestedLocale === 'fr' || app()->getLocale() === 'fr'
            ? 'fr'
            : null;

        if ($locale === 'fr') {
            Lang::addLines([
                'hero_line1' => 'Créez votre compte et',
                'hero_line2' => 'commencez en quelques minutes',
                'hero_sub' => 'Tout ce dont vous avez besoin pour accepter les réservations et gérer vos clients.',
                'form_title' => 'Créez votre compte professionnel',
                'form_sub' => 'Commencez votre essai gratuit de :days jours — aucune carte bancaire requise.',
                'business_name' => 'Nom de l’entreprise',
                'business_type' => 'Quel est le type de votre entreprise ?',
                'booking_slug' => 'Lien de réservation',
                'booking_slug_hint' => 'C’est le lien que vos clients utiliseront pour prendre rendez-vous avec vous.',
                'email' => 'Adresse e-mail',
                'password' => 'Mot de passe',
                'password_hint' => '8 caractères minimum',
                'password_confirmation' => 'Confirmer le mot de passe',
                'country' => 'Pays',
                'admin_locale' => 'Langue du tableau de bord',
                'terms_prefix' => 'J’accepte les',
                'terms' => 'Conditions générales d’utilisation',
                'and' => 'et la',
                'privacy' => 'Politique de confidentialité',
                'submit' => 'Créer mon compte',
                'existing' => 'Vous avez déjà un compte ?',
                'login' => 'Se connecter',
                'isolated_data' => 'Vos données sont isolées dans une base de données privée et dédiée — elles ne sont jamais partagées avec d’autres entreprises.',
                'show_password' => 'Afficher le mot de passe',
                'type_salon' => 'Salon',
                'type_barber' => 'Salon de coiffure pour hommes',
                'type_clinic' => 'Clinique',
                'type_spa' => 'Spa',
                'type_gym' => 'Salle de sport',
                'type_restaurant' => 'Restaurant',
                'type_studio' => 'Studio',
                'type_school' => 'École',
                'type_other' => 'Autre',
                'what_next' => 'Et ensuite ?',
                'step1_title' => 'Essai gratuit de :days jours',
                'step1_desc' => 'Bénéficiez d’un accès complet à toutes les fonctionnalités pendant votre essai.',
                'step2_title' => 'Période de grâce',
                'step2_desc' => 'Choisissez une formule pour garder votre compte actif après l’essai.',
                'step3_title' => 'Mode lecture seule',
                'step3_desc' => 'Votre compte devient limité si aucune formule n’est sélectionnée.',
                'back_to_home' => 'Retour à l’accueil',
                'nav_features' => 'Fonctionnalités',
                'nav_how_it_works' => 'Comment ça marche',
                'nav_pricing' => 'Tarifs',
                'nav_company_admin_sign_in' => 'Connexion administrateur de l’entreprise',
                'nav_start_trial' => 'Commencer l’essai gratuit',
                'switcher_lang_label' => 'Changer de langue',
                'dark_mode' => 'Mode sombre',
                'footer_rights' => 'Tous droits réservés.',
            ], 'fr', 'landing');

            Lang::addLines([
                'login' => 'Connexion',
                'login_to_account' => 'Connectez-vous à votre compte',
                'password' => 'Mot de passe',
                'remember_me' => 'Se souvenir de moi',
                'loading' => 'Connexion...',
                'login_success' => 'Connexion réussie.',
                'login_failed' => 'Identifiants invalides.',
                'toggle_theme' => 'Changer de thème',
                'back_to_workspace' => 'Retour à l’espace de travail',
            ], 'fr', 'messages');
        }

        return $next($request);
    }
}
