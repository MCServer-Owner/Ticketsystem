#!/bin/bash
# prepare_environment.sh - Optimierte Installation des Ticketsystems

# Farbdefinitionen
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Standardwerte
DEFAULT_INSTALL_DIR="/var/www/ticketsystem"
RELEASE_URL="https://github.com/MCServer-Owner/Ticketsystem/releases/download/updated/ticketsystem-latest.zip"

# Funktion zur Fehlerbehandlung
error_exit() {
    echo -e "${RED}Fehler: $1${NC}" >&2
    exit 1
}

# Abhängigkeiten prüfen
check_dependencies() {
    local missing=0
    for cmd in wget unzip mysql composer; do
        if ! command -v $cmd &>/dev/null; then
            echo -e "${RED}Fehlende Abhängigkeit: $cmd${NC}"
            missing=$((missing+1))
        fi
    done
    return $missing
}

# Installationsverzeichnis festlegen
read -p "Installationsverzeichnis [${DEFAULT_INSTALL_DIR}]: " INSTALL_DIR
INSTALL_DIR=${INSTALL_DIR:-$DEFAULT_INSTALL_DIR}

# Bestätigung
echo -e "\n${YELLOW}Installationsdetails:${NC}"
echo -e " - Download-URL: ${GREEN}${RELEASE_URL}${NC}"
echo -e " - Zielverzeichnis: ${GREEN}${INSTALL_DIR}${NC}"
echo -e " - Vendor-Verzeichnis: ${GREEN}${INSTALL_DIR}/vendor${NC}\n"

read -p "Fortfahren? (j/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[JjYy]$ ]]; then
    echo -e "${RED}Installation abgebrochen.${NC}"
    exit 1
fi

# 1. Abhängigkeiten prüfen
echo -e "\n${YELLOW}Prüfe Systemvoraussetzungen...${NC}"
if ! check_dependencies; then
    echo -e "${RED}Bitte installieren Sie die fehlenden Pakete.${NC}"
    echo -e "Für Ubuntu/Debian:"
    echo -e "  sudo apt-get install wget unzip mariadb-client composer"
    exit 1
fi

# 2. Verzeichnisstruktur erstellen
echo -e "\n${YELLOW}Erstelle Verzeichnisstruktur...${NC}"
sudo mkdir -p "$INSTALL_DIR" || error_exit "Konnte Installationsverzeichnis nicht erstellen"
sudo chown -R $USER:$USER "$INSTALL_DIR" || error_exit "Konnte Besitzer nicht ändern"

# 3. Ticketsystem herunterladen
echo -e "\n${YELLOW}Lade Ticketsystem herunter...${NC}"
TEMP_ZIP=$(mktemp)
wget -q --show-progress -O "$TEMP_ZIP" "$RELEASE_URL" || error_exit "Download fehlgeschlagen"

# 4. Dateien entpacken
echo -e "\n${YELLOW}Entpacke Dateien...${NC}"
unzip -q "$TEMP_ZIP" -d "$INSTALL_DIR" || error_exit "Entpacken fehlgeschlagen"
rm "$TEMP_ZIP"
# Vendor-Verzeichnis an endgültigen Ort verschieben
cp -r "${INSTALL_DIR}/vendor/vendor/." "${INSTALL_DIR}/vendor" || error_exit "Konnte Vendor-Verzeichnis nicht verschieben"
rm -rf "${INSTALL_DIR}/vendor/vendor"

# 5. Berechtigungen setzen
echo -e "\n${YELLOW}Setze Dateiberechtigungen...${NC}"
sudo chown -R www-data:www-data "$INSTALL_DIR" || error_exit "Konnte Besitzer nicht setzen"
sudo find "$INSTALL_DIR" -type d -exec chmod 755 {} \; || error_exit "Konnte Verzeichnisberechtigungen nicht setzen"
sudo find "$INSTALL_DIR" -type f -exec chmod 644 {} \; || error_exit "Konnte Dateiberechtigungen nicht setzen"

# 6. Erfolgsmeldung
echo -e "\n${GREEN}Installation erfolgreich abgeschlossen!${NC}"
echo -e "Das Ticketsystem wurde installiert in: ${GREEN}${INSTALL_DIR}${NC}"
echo -e "\n${YELLOW}Nächste Schritte:${NC}"
echo -e "1. Datenbank konfigurieren (bearbeiten Sie ${INSTALL_DIR}/.env)"
echo -e "2. Webserver einrichten (Apache/Nginx)"
echo -e "3. Das System unter http://your-domain.com aufrufen\n"

exit 0
