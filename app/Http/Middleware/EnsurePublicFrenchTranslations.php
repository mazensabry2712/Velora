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
                'signup_hero_line1' => 'Créez votre compte et',
                'signup_hero_line2' => 'commencez en quelques minutes',
                'signup_hero_sub' => 'Tout ce dont vous avez besoin pour accepter les réservations et gérer vos clients.',
                'signup_form_title' => 'Créez votre compte professionnel',
                'signup_form_sub' => 'Commencez votre essai gratuit de :days jours — aucune carte bancaire requise.',
                'signup_business_name' => 'Nom de l’entreprise',
                'signup_business_type' => 'Quel est le type de votre entreprise ?',
                'signup_booking_slug' => 'Lien de réservation',
                'signup_booking_slug_hint' => 'C’est le lien que vos clients utiliseront pour prendre rendez-vous avec vous.',
                'signup_email' => 'Adresse e-mail',
                'signup_password' => 'Mot de passe',
                'signup_password_hint' => '8 caractères minimum',
                'signup_password_confirmation' => 'Confirmer le mot de passe',
                'signup_country' => 'Pays',
                'signup_admin_locale' => 'Langue du tableau de bord',
                'signup_terms_prefix' => 'J’accepte les',
                'signup_terms' => 'Conditions générales d’utilisation',
                'signup_and' => 'et la',
                'signup_privacy' => 'Politique de confidentialité',
                'signup_submit' => 'Créer mon compte',
                'signup_existing' => 'Vous avez déjà un compte ?',
                'signup_login' => 'Se connecter',
                'signup_isolated_data' => 'Vos données sont isolées dans une base de données privée et dédiée — elles ne sont jamais partagées avec d’autres entreprises.',
                'signup_show_password' => 'Afficher le mot de passe',
                'signup_type_salon' => 'Salon',
                'signup_type_barber' => 'Salon de coiffure pour hommes',
                'signup_type_clinic' => 'Clinique',
                'signup_type_spa' => 'Spa',
                'signup_type_gym' => 'Salle de sport',
                'signup_type_restaurant' => 'Restaurant',
                'signup_type_studio' => 'Studio',
                'signup_type_school' => 'École',
                'signup_type_other' => 'Autre',
                'signup_what_next' => 'Et ensuite ?',
                'signup_step1_title' => 'Essai gratuit de :days jours',
                'signup_step1_desc' => 'Bénéficiez d’un accès complet à toutes les fonctionnalités pendant votre essai.',
                'signup_step2_title' => 'Période de grâce',
                'signup_step2_desc' => 'Choisissez une formule pour garder votre compte actif après l’essai.',
                'signup_step3_title' => 'Mode lecture seule',
                'signup_step3_desc' => 'Votre compte devient limité si aucune formule n’est sélectionnée.',
                'back_to_home' => 'Retour à l’accueil',
            ], 'fr', 'landing');

            Lang::addLines([
                'login' => 'Se connecter',
                'login_to_account' => 'Connectez-vous à votre compte',
                'password' => 'Mot de passe',
                'remember_me' => 'Se souvenir de moi',
                'loading' => 'Connexion...',
                'login_success' => 'Connexion réussie.',
                'login_failed' => 'Identifiants invalides.',
            ], 'fr', 'messages');
        }

        return $next($request);
    }
}
