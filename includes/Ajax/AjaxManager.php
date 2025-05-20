<?php
namespace AICA\Ajax;

class AjaxManager {
    public function __construct() {
        // Inicjalizacja wszystkich handlerów AJAX
        new ChatHandler();
        new RepositoryHandler();
        new SettingsHandler();
        new DiagnosticsHandler();
    }
}