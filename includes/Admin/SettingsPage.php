<?php
namespace AICA\Admin;

class SettingsPage {
    public function __construct() {
        // Nie potrzebujemy już rejestrować ustawień, ponieważ używamy własnych funkcji
    }
    
    /**
     * Renderowanie strony ustawień
     */
    public function render() {
        // Sprawdzenie uprawnień
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Tworzenie instancji klienta Claude do pobrania dostępnych modeli
        $api_key = aica_get_option('claude_api_key', '');
        $claude_client = null;
        $available_models = aica_get_option('claude_available_models', []);
        
        // Jeśli nie ma zapisanych modeli w opcjach lub gdy API key jest ustawiony
        if (empty($available_models) && !empty($api_key)) {
            $claude_client = new \AICA\API\ClaudeClient($api_key);
            $models = $claude_client->get_available_models();
            if (!empty($models)) {
                $available_models = $models;
            }
        } elseif (empty($available_models)) {
            // Domyślna lista modeli, jeśli nie ma ani zapisanych ani API key
            $available_models = [
                'claude-3.5-sonnet-20240620' => 'Claude 3.5 Sonnet (2024-06-20)',
                'claude-3-opus-20240229' => 'Claude 3 Opus (2024-02-29)',
                'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (2024-02-29)',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku (2024-03-07)',
                'claude-2.1' => 'Claude 2.1',
                'claude-2.0' => 'Claude 2.0',
                'claude-instant-1.2' => 'Claude Instant 1.2'
            ];
        }
        
        // Renderowanie szablonu
        include AICA_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    /**
     * Zwraca tekst etykiety modelu
     */
    public function get_model_badge_text($model_id) {
        if (strpos($model_id, 'opus') !== false) {
            return __('Najpotężniejszy', 'ai-chat-assistant');
        } elseif (strpos($model_id, 'sonnet') !== false) {
            if (strpos($model_id, '3.5') !== false) {
                return __('Najnowszy', 'ai-chat-assistant');
            }
            return __('Zbalansowany', 'ai-chat-assistant');
        } elseif (strpos($model_id, 'haiku') !== false) {
            return __('Najszybszy', 'ai-chat-assistant');
        } elseif (strpos($model_id, 'instant') !== false) {
            return __('Ekonomiczny', 'ai-chat-assistant');
        } elseif (strpos($model_id, '2.1') !== false) {
            return __('Klasyczny', 'ai-chat-assistant');
        } else {
            return __('Standard', 'ai-chat-assistant');
        }
    }
    
    /**
     * Zwraca opis modelu
     */
    public function get_model_description($model_id) {
        if (strpos($model_id, 'opus') !== false) {
            return __('Najwyższej klasy model Claude, oferujący najlepszą dostępną jakość dla najbardziej wymagających zadań.', 'ai-chat-assistant');
        } elseif (strpos($model_id, '3.5-sonnet') !== false) {
            return __('Najnowszy model Claude z ulepszonymi zdolnościami rozumowania i wykonywania złożonych zadań.', 'ai-chat-assistant');
        } elseif (strpos($model_id, 'sonnet') !== false) {
            return __('Zrównoważona kombinacja inteligencji i szybkości, idealna dla większości zastosowań.', 'ai-chat-assistant');
        } elseif (strpos($model_id, 'haiku') !== false) {
            return __('Szybki i wydajny model Claude, idealny do prostych zadań i zastosowań w czasie rzeczywistym.', 'ai-chat-assistant');
        } elseif (strpos($model_id, 'instant') !== false) {
            return __('Ekonomiczny model optymalizowany pod kątem interakcji w czasie rzeczywistym i prostych zadań.', 'ai-chat-assistant');
        } elseif (strpos($model_id, '2.1') !== false || strpos($model_id, '2.0') !== false) {
            return __('Klasyczny model Claude drugiej generacji z solidnymi podstawowymi możliwościami.', 'ai-chat-assistant');
        } else {
            return __('Standardowy model Claude z dobrymi ogólnymi zdolnościami.', 'ai-chat-assistant');
        }
    }
    
    /**
     * Zwraca ocenę mocy modelu w postaci kropek
     */
    public function get_model_power_rating($model_id) {
        $rating = 3; // Domyślnie
        
        if (strpos($model_id, 'opus') !== false) {
            $rating = 5;
        } elseif (strpos($model_id, '3.5-sonnet') !== false) {
            $rating = 4;
        } elseif (strpos($model_id, '3-sonnet') !== false) {
            $rating = 4;
        } elseif (strpos($model_id, 'haiku') !== false) {
            $rating = 3;
        } elseif (strpos($model_id, 'instant') !== false) {
            $rating = 2;
        } elseif (strpos($model_id, '2.0') !== false) {
            $rating = 2;
        }
        
        $dots = '';
        for ($i = 1; $i <= 5; $i++) {
            $class = $i <= $rating ? 'filled' : '';
            $dots .= '<span class="aica-rating-dot ' . $class . '"></span>';
        }
        
        return $dots;
    }
    
    /**
     * Zwraca ocenę szybkości modelu w postaci kropek
     */
    public function get_model_speed_rating($model_id) {
        $rating = 3; // Domyślnie
        
        if (strpos($model_id, 'opus') !== false) {
            $rating = 2;
        } elseif (strpos($model_id, '3.5-sonnet') !== false) {
            $rating = 3;
        } elseif (strpos($model_id, '3-sonnet') !== false) {
            $rating = 3;
        } elseif (strpos($model_id, 'haiku') !== false) {
            $rating = 5;
        } elseif (strpos($model_id, 'instant') !== false) {
            $rating = 5;
        } elseif (strpos($model_id, '2.0') !== false) {
            $rating = 3;
        }
        
        $dots = '';
        for ($i = 1; $i <= 5; $i++) {
            $class = $i <= $rating ? 'filled' : '';
            $dots .= '<span class="aica-rating-dot ' . $class . '"></span>';
        }
        
        return $dots;
    }
}