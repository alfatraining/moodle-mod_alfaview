<?php

// This file is part of the alfaview plugin for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     mod_alfaview
 * @category    string
 * @copyright   alfatraining Bildungszentrum GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginadministration'] = 'alfaview verwalten';
$string['pluginname'] = 'alfaview';
$string['settings'] = 'Konfiguration';
$string['settings_desc'] = 'alfaview konfigurieren';
$string['api_description'] = 'API-Informationen finden Sie in der Administrationsoberfläche unter <a href="https://app.alfaview.com/#/settings/api-keys" target="_blank">Kontoverwaltung</a>.';
$string['api_client_id'] = 'Alias (Client ID)';
$string['api_client_id_desc'] = 'Der Nutzername Ihrer alfaview API Zugangsdaten.';
$string['api_code'] = 'Secret';
$string['api_code_desc'] = 'Das Passwort Ihrer alfaview API Zugangsdaten.';
$string['api_company_id'] = 'Account ID';
$string['api_company_id_desc'] = 'Der Kontoname Ihrer alfaview API Zugangsdaten.';
$string['connection_status_ok'] = 'Verbindung erfolgreich.';
$string['connection_status_error'] = 'Verbindung fehlgeschlagen. Überprüfen Sie, ob Sie die richtigen Werte eingegeben haben und ob der Benutzer, der den API-Schlüssel erstellt hat, über Berechtigungen zum Verwalten von Benutzern und Räumen verfügt.';

$string['modulename'] = 'alfaview Klassenraum';
$string['modulenameplural'] = 'alfaview Klassenräume';
$string['modulename_help'] = 'Nutzen Sie die alfaview Videokonferenz-Technologie um zuverlässige, qualitativ hochwertige Online-Seminare durchzuführen.';

$string['room_id'] = 'Raum auswählen';
$string['room_select'] = 'Raum suchen';
$string['room_select_help'] = 'Besuchen Sie <a href="https://app.alfaview.com/" target="_blank">Ihr alfaview-Konto</a> um weitere Räume zu erstellen.';

$string['join_button_label'] = 'Klassenraum betreten';
$string['join_help_download'] = 'Stellen Sie sicher, dass alfaview installiert ist. Download <a href="https://alfaview.com/download" target="_blank">hier</a>.';
$string['join_help_support'] = 'Benötigen Sie weitere Hilfe? Besuchen Sie das <a href="http://support.alfaview.com" target="_blank">alfaview Support-Center</a>.';

$string['alfaview:view'] = 'alfaview Klassenraum';
$string['alfaview:addinstance'] = 'alfaview Klassenraum hinzufügen';

$string['privacy:metadata:alfaview'] = 'Um einen alfaview Klassenraum zu betreten, muss ein Teilnehmer einen Anzeigenamen vergeben. alfaview nutzt den Vor- und den Nachnamen eines Moodle-Nutzers und verbindet diese zu einer einzelnen Zeichenfolge. Diese Zeichenfolge wird an den Server übermittelt und eine Stunde nach Verlassen des Konferenzraumes gelöscht.';
$string['privacy:metadata:alfaview:display_name'] = 'Der Anzeigename eines Teilnehmers in einem alfaview Klassenraum';
