<?php

return [
    // Nav & page titles
    'Redirects' => 'Redirects',
    'Import' => 'Importeren',
    '404 Log' => '404 Log',
    'Import Redirects' => 'Redirects importeren',
    'Map CSV Columns' => 'CSV-kolommen koppelen',
    'Import Results' => 'Importresultaten',
    'Edit redirect' => 'Redirect bewerken',
    'New redirect' => 'Nieuwe redirect',

    // Buttons & actions
    'Export CSV' => 'CSV exporteren',
    'New redirect' => 'Nieuwe redirect',
    'Enable' => 'Inschakelen',
    'Disable' => 'Uitschakelen',
    'Delete' => 'Verwijderen',
    'Change type' => 'Type wijzigen',
    'Edit' => 'Bewerken',
    'Import' => 'Importeren',
    'Cancel' => 'Annuleren',
    'Import more' => 'Meer importeren',
    'View redirects' => 'Redirects bekijken',
    'Upload & Preview' => 'Uploaden & Voorbeeld',
    'Download example CSV' => 'Voorbeeld CSV downloaden',
    'Create redirect' => 'Redirect aanmaken',
    'Clear all' => 'Alles wissen',
    'Skip' => 'Overslaan',

    // Table headers
    'Enabled' => 'Actief',
    'From' => 'Van',
    'To' => 'Naar',
    'Type' => 'Type',
    'Match' => 'Match',
    'Label' => 'Label',
    'Hits' => 'Hits',
    'Last Hit' => 'Laatste hit',
    'Notes' => 'Notities',
    'Created' => 'Aangemaakt',
    'Created by' => 'Aangemaakt door',
    'URL' => 'URL',
    'First Seen' => 'Eerste keer gezien',
    'Row' => 'Rij',
    'Data' => 'Data',
    'Errors' => 'Fouten',
    'Site' => 'Site',

    // Site
    'All Sites' => 'Alle sites',
    'Unknown site' => 'Onbekende site',
    'The site this redirect applies to. Select "All Sites" to apply to every site.' => 'De site waarvoor deze redirect geldt. Kies "Alle sites" om op elke site toe te passen.',
    'Default site for imported redirects' => 'Standaard site voor geïmporteerde redirects',
    'Used when a row does not specify a site. Select "All Sites" to make them global.' => 'Wordt gebruikt wanneer een rij geen site bevat. Kies "Alle sites" om ze globaal te maken.',

    // Toggle & status
    'On' => 'Aan',
    'Off' => 'Uit',
    'Toggle enabled' => 'Actief schakelen',
    'selected' => 'geselecteerd',

    // Search
    'Search redirects...' => 'Redirects zoeken...',
    'Search 404s...' => '404s zoeken...',

    // Flash messages & notifications
    'Redirect saved.' => 'Redirect opgeslagen.',
    'Warning:' => 'Waarschuwing:',
    'Could not save redirect.' => 'Redirect kon niet worden opgeslagen.',
    'Redirect deleted.' => 'Redirect verwijderd.',
    'Could not delete redirect.' => 'Redirect kon niet worden verwijderd.',
    'Redirect enabled.' => 'Redirect ingeschakeld.',
    'Redirect disabled.' => 'Redirect uitgeschakeld.',
    'Could not toggle redirect.' => 'Redirect kon niet worden geschakeld.',
    'Bulk action failed.' => 'Bulkactie mislukt.',
    'Are you sure you want to delete these redirects?' => 'Weet je zeker dat je deze redirects wilt verwijderen?',
    'Are you sure you want to delete this redirect?' => 'Weet je zeker dat je deze redirect wilt verwijderen?',
    '404 entry removed.' => '404-vermelding verwijderd.',
    'Could not delete 404 entry.' => '404-vermelding kon niet worden verwijderd.',
    'Are you sure you want to clear all 404 entries?' => 'Weet je zeker dat je alle 404-vermeldingen wilt wissen?',
    'Could not clear 404 log.' => '404 log kon niet worden gewist.',

    // Import messages
    'No file uploaded.' => 'Geen bestand geüpload.',
    'Please upload a CSV file.' => 'Upload een CSV-bestand.',
    'Could not read file.' => 'Bestand kon niet worden gelezen.',
    'CSV file is empty or invalid.' => 'CSV-bestand is leeg of ongeldig.',
    'Invalid file reference.' => 'Ongeldige bestandsverwijzing.',
    'Temporary file not found. Please re-upload.' => 'Tijdelijk bestand niet gevonden. Upload opnieuw.',
    '{imported} of {total} redirects imported successfully.' => '{imported} van {total} redirects succesvol geïmporteerd.',

    // Import page
    'CSV File' => 'CSV-bestand',
    'Upload a CSV file with redirect data. The first row should contain column headers. Column mapping happens in the next step.' => 'Upload een CSV-bestand met redirectgegevens. De eerste rij moet kolomkoppen bevatten. Kolomkoppeling gebeurt in de volgende stap.',
    'Example CSV' => 'Voorbeeld CSV',
    'Not sure about the format?' => 'Niet zeker over het formaat?',
    'Map each CSV column to a redirect field. Columns mapped to <strong>Skip</strong> will be ignored.' => 'Koppel elke CSV-kolom aan een redirectveld. Kolommen die op <strong>Overslaan</strong> staan worden genegeerd.',

    // Form labels & instructions
    'Whether this redirect is active.' => 'Of deze redirect actief is.',
    'Match Type' => 'Matchtype',
    '<strong>Exact match</strong>: matches the URL literally. <strong>Regex</strong>: use a regex pattern (without delimiters). Use <code>$1</code>, <code>$2</code> etc. in the To URL for captured groups.' => '<strong>Exacte match</strong>: komt letterlijk overeen met de URL. <strong>Regex</strong>: gebruik een regex-patroon (zonder delimiters). Gebruik <code>$1</code>, <code>$2</code> etc. in de Naar URL voor capture groups.',
    'From URL' => 'Van URL',
    'Regex pattern without delimiters, e.g. <code>^/blog/(.*)$</code>' => 'Regex-patroon zonder delimiters, bijv. <code>^/blog/(.*)$</code>',
    'The path to redirect from. Must start with <code>/</code>.' => 'Het pad om vandaan te redirecten. Moet beginnen met <code>/</code>.',
    'To URL' => 'Naar URL',
    'Destination URL. Use <code>$1</code>, <code>$2</code> for captured groups, e.g. <code>/articles/$1</code>' => 'Bestemmings-URL. Gebruik <code>$1</code>, <code>$2</code> voor capture groups, bijv. <code>/articles/$1</code>',
    'The destination URL. Can be a relative path or absolute URL.' => 'De bestemmings-URL. Kan een relatief pad of absolute URL zijn.',
    'The HTTP status code for the redirect. <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Status#redirection_messages" target="_blank" rel="noopener">Learn more on MDN</a>.' => 'De HTTP-statuscode voor de redirect. <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Status#redirection_messages" target="_blank" rel="noopener">Meer informatie op MDN</a>.',
    'Optional label to categorize this redirect, e.g. "Livegang", "Redesign".' => 'Optioneel label om deze redirect te categoriseren, bijv. "Livegang", "Redesign".',
    'Optional notes about this redirect.' => 'Optionele notities over deze redirect.',

    // Type options
    '302 — Temporary (browser does not cache)' => '302 — Tijdelijk (browser cachet niet)',
    '301 — Permanent (browser caches redirect)' => '301 — Permanent (browser cachet redirect)',
    '307 — Temporary (preserve method, browser does not cache)' => '307 — Tijdelijk (behoudt methode, browser cachet niet)',
    '308 — Permanent (preserve method, browser caches redirect)' => '308 — Permanent (behoudt methode, browser cachet redirect)',

    // Match type options
    'Exact match' => 'Exacte match',
    'Regex pattern' => 'Regex-patroon',

    // Settings
    '404 Logging' => '404 Logging',
    'Log all 404 (not found) requests.' => 'Log alle 404 (niet gevonden) verzoeken.',

    // Test redirect
    'Test URL' => 'URL testen',
    'Test' => 'Testen',
    'Enter a path, e.g. /old-page' => 'Voer een pad in, bijv. /old-page',
    'Match found' => 'Match gevonden',
    'No matching redirect found.' => 'Geen overeenkomende redirect gevonden.',
    'Test failed.' => 'Test mislukt.',

    // Empty states
    'No redirects yet.' => 'Nog geen redirects.',
    'No 404s logged yet.' => 'Nog geen 404s gelogd.',
];
