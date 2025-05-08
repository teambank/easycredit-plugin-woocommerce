#!/bin/bash
set -e

# In das Projektverzeichnis wechseln
DIR="$(cd "$(dirname "$0")"; pwd)"
cd "$DIR/.."

# Ordnerstruktur vorbereiten
mkdir -p build dist
rm -rf build/*

# Composer-Abhängigkeiten ohne Dev-Pakete installieren
composer install --no-dev

# Quellcode nach build/ kopieren, bestimmte Dateien und Ordner ausschließen
rsync -rv \
  --exclude '*backup*' \
  --exclude 'test' \
  --exclude 'obsolete' \
  --exclude '.gitignore' \
  --exclude '.vscode' \
  --exclude '.git' \
  --exclude 'merchant-interface' \
 src/* build/

# Plugin-Version aus PHP-Datei extrahieren
version_statement=$(grep "define('WC_EASYCREDIT_VERSION" src/wc-easycredit/wc-easycredit.php)
version=$(php -r "$version_statement echo WC_EASYCREDIT_VERSION;")

# Sicherstellen, dass eine Version gefunden wurde
if [ -z "$version" ]; then
  echo "❌ Version konnte nicht extrahiert werden." >&2
  exit 1
fi

echo "📦 Version: $version"

# Alte ZIP-Datei löschen, falls vorhanden
rm -f "dist/wc-easycredit-$version.zip"

# Build-Inhalte zippen und in dist/ ablegen
(cd build && zip -r "../dist/wc-easycredit-$version.zip" .)

echo "✅ Build abgeschlossen: dist/wc-easycredit-$version.zip"
