# Aktifkan PHP 8.3 untuk sesi Git Bash ini:  source ./use-php83.sh
PHP83_DIR="${PHP83_DIR:-$HOME/php83}"
export PATH="$PHP83_DIR:$(echo "$PATH" | tr ':' '\n' | grep -vi 'xampp/php' | paste -sd:)"
export PHP_INI_SCAN_DIR=
php -v
