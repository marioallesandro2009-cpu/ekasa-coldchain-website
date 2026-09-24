# Aktifkan PHP 8.3 untuk sesi PowerShell ini:  . .\use-php83.ps1
# (XAMPP PHP 8.2 ada di PATH Machine; skrip ini menaruh PHP 8.3 di depan PATH
#  hanya untuk sesi terminal saat ini, tanpa mengubah pengaturan sistem.)
$php83 = if ($env:PHP83_DIR) { $env:PHP83_DIR } else { "$env:USERPROFILE\php83" }
$env:Path = "$php83;" + (($env:Path -split ';' | Where-Object { $_ -and $_ -notmatch 'xampp\php' }) -join ';')
# Folder ini global (dipakai XAMPP) dan memuat ekstensi gd yang diblokir untuk PHP 8.3.
$env:PHP_INI_SCAN_DIR = ''
php -v
