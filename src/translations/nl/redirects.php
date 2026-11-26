<?php

return [
    // Nav & page titles
    'Redirects' => 'Redirects',
    'Import' => 'Importeren',
    'Import Redirects' => 'Redirects importeren',
    'Map CSV Columns' => 'CSV-kolommen koppelen',
    'Import Results' => 'Importresultaten',
    'Edit redirect' => 'Redirect bewerken',
    'New redirect' => 'Nieuwe redirect',

    // Buttons & actions
    'Export CSV' => 'CSV exporteren',
    'Enable' => 'Inschakelen',
    'Disable' => 'Uitschakelen',
    'Delete' => 'Verwijderen',
    'Change type' => 'Type wijzigen',
    'Edit' => 'Bewerken',
    'Cancel' => 'Annuleren',
    'Import more' => 'Meer importeren',
    'View redirects' => 'Redirects bekijken',
    'Upload & Preview' => 'Uploaden & Voorbeeld',
    'Download example CSV' => 'Voorbeeld CSV downloaden',
    'Skip' => 'Overslaan',

    // Table headers
    'Enabled' => 'Actief',
    'From' => 'Van',
    'To' => 'Naar',
    'Type' => 'Type',
    'Match' => 'Match',
    'Label' => 'Label',
    'Notes' => 'Notities',
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

    // Test redirect
    'Test URL' => 'URL testen',
    'Test' => 'Testen',
    'Enter a path, e.g. /old-page' => 'Voer een pad in, bijv. /old-page',
    'Match found' => 'Match gevonden',
    'No matching redirect found.' => 'Geen overeenkomende redirect gevonden.',
    'Test failed.' => 'Test mislukt.',

    // Empty states
    'No redirects yet.' => 'Nog geen redirects.',

    // How it works
    'How do redirects work?' => 'Hoe werken redirects?',
    'Visitors requesting a URL that matches a "From" path are automatically forwarded to the "To" URL, using the HTTP status code in the "Type" column. Query strings (e.g. <code>?utm_source=...</code>) are preserved.' => 'Bezoekers die een URL opvragen die overeenkomt met een "Van"-pad worden automatisch doorgestuurd naar de "Naar"-URL, met de HTTP-statuscode uit de kolom "Type". Query strings (bijv. <code>?utm_source=...</code>) blijven behouden.',
    'Automatic redirects' => 'Automatische redirects',
    'When the URL of an entry changes (for example after editing a slug or moving it in a structure), a {type} redirect from the old URL to the new one is created automatically — including for any child pages. These entries are recognizable by the note “Automatically created after a URI change.” You don’t need to do anything yourself.' => 'Wanneer de URL van een pagina verandert (bijvoorbeeld na het aanpassen van een slug of het verplaatsen in een structuur), wordt automatisch een {type}-redirect aangemaakt van de oude naar de nieuwe URL — ook voor eventuele onderliggende pagina\'s. Deze zijn te herkennen aan de notitie "Automatically created after a URI change." Je hoeft hier zelf niets voor te doen.',
    'The plugin also keeps things tidy: existing redirects pointing to the old URL are re-pointed to the new one (no chains), and redirects that would cause a loop are removed.' => 'De plugin houdt het ook netjes: bestaande redirects die naar de oude URL wezen worden omgezet naar de nieuwe (geen kettingen), en redirects die een loop zouden veroorzaken worden verwijderd.',
    'Note: deleting a page does not create a redirect — there is no new URL to point to. Add one manually if needed.' => 'Let op: bij het verwijderen van een pagina wordt géén redirect aangemaakt — er is dan geen nieuwe URL om naartoe te verwijzen. Voeg er zo nodig handmatig één toe.',
    'Automatic redirect creation is currently disabled in the plugin settings.' => 'Het automatisch aanmaken van redirects staat momenteel uitgeschakeld in de plugininstellingen.',
    'Matching' => 'Matching',
    '<strong>Exact</strong> redirects match the path literally (trailing slashes and letter case are ignored). <strong>Regex</strong> redirects use a pattern; lower "Priority" values are checked first. Redirects with an "Expires" date stop working after that date.' => '<strong>Exacte</strong> redirects komen letterlijk overeen met het pad (slashes aan het einde en hoofdletters worden genegeerd). <strong>Regex</strong>-redirects gebruiken een patroon; lagere "Prioriteit"-waarden worden eerst gecontroleerd. Redirects met een "Verloopt"-datum werken na die datum niet meer.',
    'Use the "Test URL" tool below to check which redirect (if any) applies to a given path.' => 'Gebruik de tool "URL testen" hieronder om te controleren welke redirect (indien aanwezig) van toepassing is op een pad.',

    'Priority' => 'Prioriteit',
    'Expires' => 'Verloopt',
    'Expiry date' => 'Vervaldatum',
    // Hits & groups
    'Hits' => 'Hits',
    'Last triggered' => 'Laatst getriggerd',
    'Reset hits' => 'Hits resetten',
    'Reset the hit counter of these redirects?' => 'De hitteller van deze redirects resetten?',
    '{count} times, last on {date}' => '{count} keer, laatst op {date}',
    'Not triggered yet.' => 'Nog niet getriggerd.',
    'Group' => 'Groep',
    'All groups' => 'Alle groepen',
    'Show this group' => 'Toon deze groep',
    '{enabled} of {total} enabled' => '{enabled} van {total} actief',
    'Enable group' => 'Groep inschakelen',
    'Disable group' => 'Groep uitschakelen',
    'Enable all redirects in this group?' => 'Alle redirects in deze groep inschakelen?',
    'Disable all redirects in this group?' => 'Alle redirects in deze groep uitschakelen?',
    'Could not toggle group.' => 'Groep kon niet worden geschakeld.',
    'No group selected.' => 'Geen groep geselecteerd.',
    'No redirects in this group.' => 'Geen redirects in deze groep.',
];
