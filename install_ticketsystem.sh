#!/bin/bash

# prepare_environment.sh - Installationsskript für das Ticketsystem

# Standardwerte
DEFAULT_INSTALL_DIR="/var/www/html/"
RELEASE_URL="https://github.com/MCServer-Owner/Ticketsystem/releases/download/updated/ticketsystem-v1.3.zip"

# Farben für die Ausgabe
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Funktion zum Prüfen von Abhängigkeiten
check_dependencies() {
    local missing=0
    for cmd in wget unzip; do
        if ! command -v $cmd &> /dev/null; then
            echo -e "${RED}Fehler: '$cmd' ist nicht installiert.${NC}"
            missing=$((missing+1))
        fi
    done
    return $missing
}

# Installationsverzeichnis abfragen
read -p "In welches Verzeichnis soll das Ticketsystem installiert werden? [${DEFAULT_INSTALL_DIR}]: " INSTALL_DIR
INSTALL_DIR=${INSTALL_DIR:-$DEFAULT_INSTALL_DIR}

# Bestätigung
echo -e "\n${YELLOW}Installationsdetails:${NC}"
echo -e " - Download-URL: ${GREEN}${RELEASE_URL}${NC}"
echo -e " - Zielverzeichnis: ${GREEN}${INSTALL_DIR}${NC}\n"

read -p "Fortfahren? (j/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[JjYy]$ ]]; then
    echo -e "${RED}Installation abgebrochen.${NC}"
    exit 1
fi

# Abhängigkeiten prüfen
echo -e "\n${YELLOW}Prüfe Systemvoraussetzungen...${NC}"
if ! check_dependencies; then
    echo -e "${RED}Bitte installieren Sie die fehlenden Pakete und führen Sie das Skript erneut aus.${NC}"
    exit 1
fi

# Verzeichnis erstellen
echo -e "\n${YELLOW}Erstelle Installationsverzeichnis...${NC}"
sudo mkdir -p "$INSTALL_DIR"
if [ $? -ne 0 ]; then
    echo -e "${RED}Konnte Verzeichnis ${INSTALL_DIR} nicht erstellen.${NC}"
    exit 1
fi

# Temporäres Verzeichnis für den Download
TEMP_DIR=$(mktemp -d)

# Download durchführen
echo -e "\n${YELLOW}Lade Ticketsystem herunter...${NC}"
wget -q --show-progress -O "${TEMP_DIR}/ticketsystem.zip" "$RELEASE_URL"
if [ $? -ne 0 ]; then
    echo -e "${RED}Download fehlgeschlagen!${NC}"
    rm -rf "$TEMP_DIR"
    exit 1
fi

# Dateien entpacken
echo -e "\n${YELLOW}Entpacke Dateien...${NC}"
sudo unzip -q "${TEMP_DIR}/ticketsystem.zip" -d "$INSTALL_DIR"
if [ $? -ne 0 ]; then
    echo -e "${RED}Entpacken fehlgeschlagen!${NC}"
    rm -rf "$TEMP_DIR"
    exit 1
fi

# Berechtigungen setzen
echo -e "\n${YELLOW}Setze Dateiberechtigungen...${NC}"
sudo chown -R www-data:www-data "$INSTALL_DIR"
sudo find "$INSTALL_DIR" -type d -exec chmod 755 {} \;
sudo find "$INSTALL_DIR" -type f -exec chmod 644 {} \;

# Aufräumen
rm -rf "$TEMP_DIR"

# Erfolgsmeldung
echo -e "\n${GREEN}Installation erfolgreich abgeschlossen!${NC}"
echo -e "Das Ticketsystem wurde installiert nach: ${GREEN}${INSTALL_DIR}${NC}"
echo -e "\nNächste Schritte:"
echo -e "1. Konfiguration anpassen: ${INSTALL_DIR}/config.php"
echo -e "2. Datenbank einrichten (falls benötigt)"
echo -e "3. Webserver konfigurieren\n"

exit 0
