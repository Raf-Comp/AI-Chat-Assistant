<?php
namespace AICA;

class Main {
    private $admin_pages;
    private $admin_init;

    public function __construct() {
        $this->admin_pages = new \AICA\Admin\PageManager();
        $this->admin_init = new \AICA\Admin\Init();
    }

    public function run() {
        add_action('admin_menu', [$this->admin_pages, 'register_menu']);
        add_action('init', [$this, 'initialize_current_user']);
        add_action('admin_enqueue_scripts', [$this, 'register_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'register_front_assets']);
        add_action('admin_post_save_aica_interface_settings', [$this, 'save_interface_settings']);

        $this->init_ajax();
    }

    public function init_ajax() {
        $ajax_manager = new \AICA\Ajax\AjaxManager();
    }

    public function initialize_current_user() {
        $current_wp_user_id = get_current_user_id();
        if ($current_wp_user_id > 0) {
            $aica_user_id = aica_get_user_id($current_wp_user_id);
            if (!$aica_user_id) {
                $user_data = get_userdata($current_wp_user_id);
                if ($user_data) {
                    $roles = $user_data->roles;
                    $role = 'subscriber';
                    $role_hierarchy = [
                        'administrator' => 5,
                        'editor' => 4,
                        'author' => 3,
                        'contributor' => 2,
                        'subscriber' => 1
                    ];

                    $highest_rank = 0;
                    foreach ($roles as $r) {
                        $rank = $role_hierarchy[$r] ?? 0;
                        if ($rank > $highest_rank) {
                            $highest_rank = $rank;
                            $role = $r;
                        }
                    }

                    aica_add_user(
                        $current_wp_user_id,
                        $user_data->user_login,
                        $user_data->user_email,
                        $role,
                        current_time('mysql')
                    );

                    aica_log('Inicjalizowano użytkownika: ' . $user_data->user_login . ' (ID: ' . $current_wp_user_id . ')');
                }
            }
        }
    }

    public function save_interface_settings() {
        if (!isset($_POST['aica_interface_nonce']) || !wp_verify_nonce($_POST['aica_interface_nonce'], 'aica_interface_settings')) {
            wp_die(__('Błąd bezpieczeństwa. Odśwież stronę i spróbuj ponownie.', 'ai-chat-assistant'));
        }

        $dark_mode = isset($_POST['aica_dark_mode']) ? 1 : 0;
        $compact_view = isset($_POST['aica_compact_view']) ? 1 : 0;

        update_option('aica_dark_mode', $dark_mode);
        update_option('aica_compact_view', $compact_view);
        ?>
        <script type="text/javascript">
            localStorage.setItem('aica_dark_mode', '<?php echo $dark_mode ? 'true' : 'false'; ?>');
            localStorage.setItem('aica_compact_mode', '<?php echo $compact_view ? 'true' : 'false'; ?>');
            window.location.href = '<?php echo admin_url('admin.php?page=ai-chat-assistant&settings-updated=1'); ?>';
        </script>
        <?php
        exit;
    }

    public function register_admin_assets($hook) {
        $page = $_GET['page'] ?? '';

        if (strpos($hook, 'ai-chat-assistant') !== false) {
            wp_enqueue_style('aica-admin', AICA_PLUGIN_URL . 'assets/css/admin.css', [], AICA_VERSION);
        }

        if ($page === 'ai-chat-assistant-settings') {
            wp_enqueue_script('aica-admin', AICA_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], AICA_VERSION, true);
            wp_localize_script('aica-admin', 'aica_data', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'admin_url' => admin_url('admin-post.php'),
                'nonce' => wp_create_nonce('aica_nonce'),
                'settings_nonce' => wp_create_nonce('aica_settings_nonce'),
                'interface_nonce' => wp_create_nonce('aica_interface_settings'),
                'i18n' => [
                    'error' => __('Błąd', 'ai-chat-assistant'),
                    'loading' => __('Ładowanie...', 'ai-chat-assistant'),
                    'sending' => __('Wysyłanie...', 'ai-chat-assistant'),
                    'saving' => __('Zapisywanie...', 'ai-chat-assistant'),
                    'saved' => __('Zapisano', 'ai-chat-assistant'),
                    'save_error' => __('Błąd zapisywania', 'ai-chat-assistant')
                ]
            ]);
        }
    }

    // ✅ NOWOŚĆ: Frontendowe style i skrypty dla czatu
    public function register_front_assets() {
        wp_enqueue_style(
            'aica-modern-chat',
            AICA_PLUGIN_URL . 'assets/css/modern-chat.css',
            [],
            AICA_VERSION
        );

        wp_enqueue_script(
            'aica-modern-chat',
            AICA_PLUGIN_URL . 'assets/js/modern-chat.js',
            ['jquery'],
            AICA_VERSION,
            true
        );
    }
}
