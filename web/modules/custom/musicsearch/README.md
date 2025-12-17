Drupal Verkefni Tónlistarkerfi

Music Search Module (Spotify & Discogs)

This project is a Drupal site with a custom module called Music Search (musicsearch) plus provider sub‑modules for Spotify and Discogs.
The module is intended to:


Search for albums via external APIs (Spotify / Discogs).

Normalize the results into a common structure.


Prepare data for importing into existing album/artist content types.

This README explains how to get the site running and how to reach the custom UI.



Note: Some parts are incomplete or may still contain errors (see “Known Issues” below).



1. Requirements

You will need:
-PHP 8.1+ (or whatever version your local Drupal supports).

-A web server (Apache/Nginx) or a local environment (DDEV/Lando/XAMPP/etc.).

-A database server (MySQL/MariaDB/PostgreSQL).

-Composer installed on your machine.

-(Optional but recommended) Drush for command‑line Drupal tasks.



2. Getting the Code

-Unpack the project archive (or clone the repository) into a directory, e.g.:

-/var/www/musicsearch (Linux/macOS), or

-C:\xampp\htdocs\musicsearch (Windows + XAMPP), or

-Any directory mapped to your local web root.

-In a terminal, change to the project root:

-cd /path/to/musicsearch


3. Install PHP Dependencies

From the project root, install dependencies with Composer:

composer install

This will:

Download Drupal core and contrib modules.

Set up the vendor/ directory and autoloading.

If you see memory limit errors with Composer, you may need to temporarily raise memory_limit in php.ini, or run Composer with COMPOSER_MEMORY_LIMIT=-1.


4. Create a Database

Create an empty database for this Drupal site, for example:

Database name: musicsearch

Database user: drupal

Database password: drupal

You can do this with phpMyAdmin, Adminer, or the MySQL CLI:

CREATE DATABASE musicsearch CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
GRANT ALL PRIVILEGES ON musicsearch.* TO 'drupal'@'localhost' IDENTIFIED BY 'drupal';
FLUSH PRIVILEGES;

Adjust names/credentials as needed for your environment.



5. Run the Drupal Installer

You can install Drupal via the browser or via Drush.



5.1 Browser‑based install

Point your browser to the site URL, e.g.:

http://localhost/musicsearch/web

or whatever virtual host you set up.

Follow the Drupal installation wizard:

Choose the Standard profile (unless the project already includes a specific profile).

Enter the database credentials from step 4.

Create an admin account.

5.2 Drush‑based install (optional)

If Drush is available in the project (e.g. vendor/bin/drush):



cd /path/to/musicsearch/web
../vendor/bin/drush site:install standard -y \
  --db-url=mysql://drupal:drupal@localhost/musicsearch \
  --account-name=admin --account-pass=admin

Adjust user/password and DB URL as appropriate.



6. Enable the Custom Modules

Once Drupal is installed and you can log in as an administrator:


Go to Extend (/admin/modules).

Enable:

Music Search (machine name: musicsearch)

Spotify Lookup (if present; e.g. spotify_lookup)

Discogs Lookup (if present; e.g. discogs_lookup)

Alternatively, with Drush:



cd /path/to/musicsearch/web
../vendor/bin/drush en musicsearch spotify_lookup discogs_lookup -y


If any provider sub‑module is missing, just enable the ones that exist.



7. Configure API Keys

The Music Search module uses configuration to store external API keys and settings.

As admin, go to:

/admin/config/services/musicsearch

Enter the required values (depending on what the module supports in this submission), for example:

Spotify Client ID

Spotify Client Secret

Discogs API key / token

Save the configuration.

If keys are not set, provider calls may fail gracefully or return empty results (depending on implementation).



8. Music Search UI

The main UI for the assignment is provided by the musicsearch module.

As admin, visit:

/admin/content/musicsearch

This is intended to show:


A form for searching albums (query string + provider selection).

A result list of normalized album data from Spotify / Discogs.

Links or buttons to import/merge albums into Drupal content.

Current state: In this submission, there may still be a fatal error when visiting this page due to a missing or mis‑wired service class (SpotifyLookupService). See “Known Issues” below.



9. Code Structure (High‑Level)

Relevant custom code lives under web/modules/custom/:

web/modules/custom/musicsearch/

musicsearch.info.yml – module info.

musicsearch.routing.yml – routes, including /admin/content/musicsearch.

musicsearch.services.yml – service definitions.

src/Service/MusicSearchService.php – central service that:

receives normalized calls from provider services,

normalizes data into a shared album structure,

exposes methods like searchAlbums(), getSpotifyAlbumById(), getDiscogsAlbumById().

Controllers / Forms:

src/Controller/MusicSearchController.php

src/Form/MusicSearchForm.php

web/modules/custom/spotify_lookup/ (if present)


src/Service/SpotifyLookupService.php – low‑level Spotify API calls.

web/modules/custom/discogs_lookup/ (if present)


src/Service/DiscogsLookupService.php – low‑level Discogs API calls.



10. Known Issues / Limitations

For transparency, here are the main outstanding problems in this submission:


Fatal error on Music Search form
Visiting /admin/content/musicsearch may produce:



Class "Drupal\spotify_lookup\Service\SpotifyLookupService" not found in Drupal\Component\DependencyInjection\Container->createService()

This indicates that the service definition is pointing to a SpotifyLookupService class that Drupal cannot autoload (namespace mismatch / file missing).



Incomplete integration
The underlying normalization and search service (MusicSearchService) is implemented, but in some cases the provider services or DI wiring may not be fully correct, so the UI may not function end‑to‑end.

Despite these issues, the code demonstrates:


Custom module and routing setup.

Service definitions and dependency injection.

A central search/normalization service that aggregates results from multiple providers.

A configuration form for external API keys.




12. Contact / Notes

This is a student project created for a university assignment.
If anything does not run as expected, the main points to check are:



Composer dependencies (composer install).

Database credentials.

Enabled modules (musicsearch and provider modules).

Configuration at /admin/config/services/musicsearch.



