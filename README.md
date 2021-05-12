# LOOM Cookie

Dieses Modul basiert auf der Version 1.9 von [EU Cookie Compliance](https://www.drupal.org/project/eu_cookie_compliance)

Zusätzlich beinhaltet es:

- Eine Erweiterung zum blocken von Tracking Scripten per Regex
- Client Side blockieren von Scripten

# Installation

Um die neuste Version zu installieren kopiere diesen Eintrag in die `composer.json` unter `repositories`.

```json
{
    "type": "git",
    "url": "https://github.com/loomgmbh/module-loom_cookie.git"
}
```

Dann führe folgenden Befehl aus `composer require "loomgmbh/loom_cookie:~1.0"`.

# Update

Führe dazu folgenden Befehl aus: `composer update "loomgmbh/loom_cookie"`

# Upgrade (auf 3.x)
1. eu_cookie_compliance auf 1.9 und loom_cookie auf die neueste 2.x Version updaten (`drush updb` nicht vergessen).
Am besten auch die Translations updaten, weil wir in Zukunft davon "abgeschnitten" sind
2) Modul `loom_cookie` über composer updaten (Branch 3.x)
3) Custom Code überprüfen
  - Folgende Hooks wurden geändert:
    - hook_eu_cookie_compliance_geoip_match_alter => hook_loom_cookie_geoip_match_alter
    - hook_eu_cookie_compliance_path_match_alter => entfernt
    - hook_eu_cookie_compliance_show_popup_alter => hook_loom_cookie_show_popup_alter

  - Folgende Templates wurden entfernt:
    - eu_cookie_compliance_popup_agreed.html.twig
    - eu_cookie_compliance_popup_info_consent_default.html.twig

  - Diese Optionen wurden entfernt:
    - popup_info_template
    - disabled_javascripts
    - popup_agreed_enabled
    - popup_hide_agreed
    - popup_agreed
    - popup_find_more_button_message
    - popup_hide_button_message

  - settings.php bzw. settings.*.php: Falls eingetragen
```
$config['eu_cookie_compliance.settings']['domain'] = '';
```
wird zu
```
$config['loom_cookie.settings']['domain'] = '';
```

  - `drupalSettings.eu_cookie_compliance` und `Drupal.eu_cookie_compliance` wird zwar noch durch ein Mapping unterstützt,
es wäre aber toll, wenn das auf `drupalSettings.loom_cookie` und `Drupal.loom_cookie` geändert wird

4) Update Hook zur Migration ausführen: `drush updb`
5) Modul `eu_cookie_compliance` deinstallieren: `drush pmu eu_cookie_compliance`
