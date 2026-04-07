<?php
/*
Plugin Name: Local Auto Tag Generator
Description: TinySegmenterを使用して、外部APIを使わずにタグを自動生成します。
Version: 1.0
Tested up to: 6.9.4
Requires PHP: 8.3.23
Author: masato shibuya(Image-box Co., Ltd.)
*/

if (!defined('ABSPATH')) exit;

// --- 1. ライブラリの自動読み込み設定 (あなたのフォルダ構成に最適化) ---
spl_autoload_register(function ($class) {
    // クラス名が 'JpnForPhp\' で始まるかチェック
    $prefix = 'JpnForPhp\\';
    $base_dir = plugin_dir_path(__FILE__) . 'lib/JpnForPhp/'; // 直接 lib/JpnForPhp を見に行く

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // 相対クラス名を取得し、パスに変換
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// --- 2. 管理画面設定ページ ---
add_action('admin_menu', 'latg_add_admin_menu');
function latg_add_admin_menu() {
    add_options_page('Local Auto Tag 設定', 'Local Auto Tag', 'manage_options', 'local_auto_tag', 'latg_settings_page');
}

function latg_settings_page() {
    if (isset($_POST['latg_save'])) {
        check_admin_referer('latg_save_action');
        update_option('latg_post_types', isset($_POST['post_types']) ? $_POST['post_types'] : array('post'));
        update_option('latg_exclude_words', sanitize_textarea_field($_POST['exclude_words']));
        echo '<div class="updated"><p>設定を保存しました。</p></div>';
    }

    $selected_types = get_option('latg_post_types', array('post'));
    $exclude_words = get_option('latg_exclude_words', '');
    $post_types = get_post_types(array('public' => true), 'objects');

    ?>
    <div class="wrap">
        <h1>Local Auto Tag Generator 設定</h1>
        <form method="post">
            <?php wp_nonce_field('latg_save_action'); ?>
            <table class="form-table">
                <tr>
                    <th>対象の投稿タイプ</th>
                    <td>
                        <?php foreach ($post_types as $post_type): ?>
                            <label>
                                <input type="checkbox" name="post_types[]" value="<?php echo esc_attr($post_type->name); ?>"
                                <?php checked(in_array($post_type->name, $selected_types)); ?>>
                                <?php echo esc_html($post_type->label); ?>
                            </label><br>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th>除外ワード (カンマ区切り)</th>
                    <td>
                        <textarea name="exclude_words" rows="5" cols="50" class="large-text" placeholder="する,ある,こと,もの"><?php echo esc_textarea($exclude_words); ?></textarea>
                    </td>
                </tr>
            </table>
            <?php submit_button('設定を保存', 'primary', 'latg_save'); ?>
        </form>

        <hr>

        <h2>過去記事への一括適用</h2>
        <button id="latg-run-bulk" class="button button-secondary">一括生成を開始</button>
        <div id="latg-log" style="margin-top:15px; padding:10px; background:#f0f0f0; border:1px solid #ccc; max-height:400px; overflow-y:auto; font-family:monospace; font-size:13px;">待機中...</div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#latg-run-bulk').on('click', function() {
            if(!confirm('全記事を解析してタグを自動登録します。よろしいですか？')) return;
            $('#latg-log').empty();
            $(this).prop('disabled', true).text('処理中...');
            processStep(0);
        });

        function processStep(offset) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'latg_bulk_action',
                    offset: offset,
                    nonce: '<?php echo wp_create_nonce("latg_bulk_nonce"); ?>'
                }
            })
            .done(function(response) {
                if(response.success) {
                    $('#latg-log').append('<div>' + response.data.message + '</div>');
                    $('#latg-log').scrollTop($('#latg-log')[0].scrollHeight);
                    if(!response.data.done) {
                        processStep(response.data.next_offset);
                    } else {
                        $('#latg-log').append('<strong>--- 完了しました ---</strong>');
                        $('#latg-run-bulk').text('一括生成を開始').prop('disabled', false);
                    }
                }
            })
            .fail(function() {
                $('#latg-log').append('<div style="color:red;">エラーが発生しました。</div>');
            });
        }
    });
    </script>
    <?php
}

// --- 3. メインロジック ---
function latg_process_auto_tagging($post_id) {
    if (!class_exists('\JpnForPhp\Analyzer\TinySegmenter')) {
        return array('error' => 'ライブラリが見つかりません。パス: lib/JpnForPhp/Analyzer/TinySegmenter.php');
    }

    $post = get_post($post_id);
    $text = strip_tags($post->post_title . ' ' . $post->post_content);
    if (empty(trim($text))) return array('info' => '本文が空です');

    try {
        $ts = new \JpnForPhp\Analyzer\TinySegmenter();
        $segments = $ts->segment($text);

        $exclude_raw = get_option('latg_exclude_words', '');
        $exclude_list = array_filter(array_map('trim', explode(',', $exclude_raw)));
        $existing_tags = get_terms(array('taxonomy' => 'post_tag', 'hide_empty' => false, 'fields' => 'names'));

        $candidates = array();
        foreach ($segments as $word) {
            $word = trim($word);
            // 2文字以上、記号・数字・空白以外
            if (mb_strlen($word) < 2 || is_numeric($word) || preg_match('/^[、。！？（）「」『』\s：；・]+$/u', $word)) continue;
            if (in_array($word, $exclude_list)) continue;

            // ひらがな2文字（助詞など）を除外
            if (mb_strlen($word) === 2 && preg_match('/^[ぁ-ん]+$/u', $word)) continue;

            $candidates[] = $word;
        }

        if (empty($candidates)) return array('info' => 'タグになる単語が見つかりませんでした');

        $counts = array_count_values($candidates);
        foreach ($counts as $word => $count) {
            if (in_array($word, $existing_tags)) { $counts[$word] += 50; }
        }

        arsort($counts);
        $final_tags = array_slice(array_keys($counts), 0, 5);

        if (!empty($final_tags)) {
            wp_set_post_tags($post_id, $final_tags, false);
            return array('success' => true, 'tags' => $final_tags);
        }

    } catch (Throwable $e) {
        return array('error' => 'エラー: ' . $e->getMessage());
    }
    return array('info' => '処理をスキップしました');
}

// --- 4. AJAX ---
add_action('wp_ajax_latg_bulk_action', 'latg_handle_ajax');
function latg_handle_ajax() {
    check_ajax_referer('latg_bulk_nonce', 'nonce');
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $selected_types = get_option('latg_post_types', array('post'));

    $query = new WP_Query(array(
        'post_type' => $selected_types,
        'posts_per_page' => 1,
        'offset' => $offset,
        'post_status' => 'any'
    ));

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $result = latg_process_auto_tagging(get_the_ID());
            $title = get_the_title() ? get_the_title() : '(無題)';
            if (isset($result['success'])) {
                $msg = "<span style='color:green;'>[成功]</span> {$title} -> [" . implode(', ', $result['tags']) . "]";
            } else {
                $msg = "<span style='color:#666;'>[スキップ]</span> {$title} : " . ($result['info'] ?? $result['error']);
            }
        }
        wp_send_json_success(array('done' => false, 'next_offset' => $offset + 1, 'message' => $msg));
    } else {
        wp_send_json_success(array('done' => true, 'message' => '完了。'));
    }
}

// --- 5. 保存時実行 ---
add_action('save_post', function($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    $selected_types = get_option('latg_post_types', array('post'));
    if (in_array($post->post_type, $selected_types)) {
        latg_process_auto_tagging($post_id);
    }
}, 10, 2);


require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';

$updateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/ms13th-cyber/auto-tag-generator/',
    __FILE__,
    'auto-tag-generator'
);

$updateChecker->setBranch('main');