<?php
/**
 * Przekierowanie do katalogu publicznego, gdy document root wskazuje
 * na katalog projektu (np. http://localhost/bookit/).
 */
header('Location: public/');
exit;
