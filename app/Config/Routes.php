<?php

use CodeIgniter\Router\RouteCollection;

$routes->get('/', 'Dashboard::index');

// Auth routes
$routes->get('/login', 'Auth::login');
$routes->post('/login', 'Auth::login');
// $routes->get('/register', 'Auth::register');
// $routes->post('/register', 'Auth::register');
$routes->get('/logout', 'Auth::logout');

// Dashboard
$routes->get('/dashboard', 'Dashboard::index');

// Google OAuth routes
$routes->get('/google/connect', 'GoogleAuth::connect');
$routes->get('/google/callback', 'GoogleAuth::callback');

// Google Ads routes
$routes->get('/google/ads/accounts', 'GoogleAdsController::accounts');
$routes->get('/google/ads/sync/(:num)', 'GoogleAdsController::syncAccounts/$1');
$routes->get('/google/ads/campaigns/(:num)', 'GoogleAdsController::campaigns/$1');