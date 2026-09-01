<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\HttpFoundation\Response;

class EnsurePublicFrenchTranslations
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->getLocale() === 'fr') {
            Lang::addLines([
                'landing.signup_hero_line1' => 'Créez votre compte et',
                'landing.signup_hero_line2' => 'commencez en quelques minutes',
                'landing.signup_hero_sub' => 'Tout ce dont vous avez besoin pour accepter les réservations et gérer vos clients.',
                'landing.signup_form_title' => 'Créez votre compte professionnel',
                'landing.signup_form_sub' => 'Commencez votre essai gratuit de :days jours — aucune carte bancaire requise.',
                'landing.signup_business_name' => 'Nom de l’entreprise',
                'landing.signup_business_type' => 'Quel est le type de votre entreprise ?',
                'landing.signup_booking_slug' => 'Lien de réservation',
                'landing.signup_booking_slug_hint' => 'C’est le lien que vos clients utiliseront pour prendre rendez-vous avec vous.',
                'landing.signup_email' => 'Adresse e-mail',
                'landing.signup_password' => 'Mot de passe',
                'landing.signup_password_hint' => '8 caractères minimum',
                'landing.signup_password_confirmation' => 'Confirmer le mot de passe',
                'landing.signup_country' => 'Pays',
                'landing.signup_admin_locale' => 'Langue du tableau de bord',
                'landing.signup_terms_prefix' => 'J’accepte les',
                'landing.signup_terms' => 'Conditions générales d’utilisation',
                'landing.signup_and' => 'et la',
                'landing.signup_privacy' => 'Politique de confidentialité',
                'landing.signup_submit' => 'Créer mon compte',
                'landing.signup_existing' => 'Vous avez déjà un compte ?',
                'landing.signup_login' => 'Se connecter',
                'landing.signup_isolated_data' => 'Vos données sont isolées dans une base de données privée et dédiée — elles ne sont jamais partagées avec d’autres entreprises.',
                'landing.signup_show_password' => 'Afficher le mot de passe',
                'landing.signup_type_salon' => 'Salon',
                'landing.signup_type_barber' => 'Salon de coiffure pour hommes',
                'landing.signup_type_clinic' => 'Clinique',
                'landing.signup_type_spa' => 'Spa',
                'landing.signup_type_gym' => 'Salle de sport',
                'landing.signup_type_restaurant' => 'Restaurant',
                'landing.signup_type_studio' => 'Studio',
                'landing.signup_type_school' => 'École',
                'landing.signup_type_other' => 'Autre',
                'landing.signup_what_next' => 'Et ensuite ?',
                'landing.signup_step1_title' => 'Essai gratuit de :days jours',
                'landing.signup_step1_desc' => 'Bénéficiez d’un accès complet à toutes les fonctionnalités pendant votre essai.',
                'landing.signup_step2_title' => 'Période de grâce',
                'landing.signup_step2_desc' => 'Choisissez une formule pour garder votre compte actif après l’essai.',
                'landing.signup_step3_title' => 'Mode lecture seule',
                'landing.signup_step3_desc' => 'Votre compte devient limité si aucune formule n’est sélectionnée.',
                'landing.back_to_home' => 'Retour à l’accueil',
            ], 'fr');

            Lang::addLines([
                'messages.login' => 'Se connecter',
                'messages.login_to_account' => 'Connectez-vous à votre compte',
                'messages.password' => 'Mot de passe',
                'messages.remember_me' => 'Se souvenir de moi',
                'messages.loading' => 'Connexion...',
                'messages.login_success' => 'Connexion réussie.',
                'messages.login_failed' => 'Identifiants invalides.',
            ], 'fr');
        }

        return $next($request);
    }
}
