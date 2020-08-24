# LOOM Cookie

Dieses Modul beinhaltet:

- eine Funktion zum laden von `.preprocess.php`-Dateien für jedes Template
- Ein `EntityWrapper` zur vereinfachung des preprocesses
- Twig include Paths für `./components`
- Twig Funktionen:
  - `getModifier()`

# Dependencies

- eu_cookie_compliance - https://www.drupal.org/project/eu_cookie_compliance

# Install

Um die neuste Version zu installieren kopiere diesen Eintrag in die `composer.json` unter `repositories`.

```json
{
    "type": "git",
    "url": "https://github.com/loomgmbh/module-loom_cookie.git"
}
```

Dann führe folgenden Befehl aus `composer require "loomgmbh/loom_cookie:~1.0"`.

```

# Update

Führe dazu folgenden Befehl aus: `composer update "loomgmbh/loom_cookie"`
