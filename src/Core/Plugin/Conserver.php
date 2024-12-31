<?php

namespace LEXO\CP\Core\Plugin;

class Conserver
{
    private const META_KEY = '_conserve_page';

    public static function applyCustomClassToConvertedPages($classes, $class, $post_id)
    {
        $is_page_conserved = self::isPageConserved($post_id);

        if ($is_page_conserved) {
            $classes[] = 'conserved-page-row';
        }

        return $classes;
    }

    public static function handleAddConservePageColumn($columns)
    {
        $columns['conserve_page'] = __('Conserve Page', 'cp');
        return $columns;
    }

    public static function handleDisplayConservePageCheckbox($column, $post_id)
    {
        if ($column !== 'conserve_page') {
            return;
        }

        $show_checkbox_on_every_top_level = apply_filters('show_conserve_page_checkbox_on_every_top_level', false);
        $top_level_page_with_checkbox = apply_filters('exclude_conserve_page_checkbox', []);
        $parent_id = wp_get_post_parent_id($post_id);
        $is_page_conserved = self::isPageConserved($post_id);

        if (
            $parent_id === 0
            && !in_array($post_id, $top_level_page_with_checkbox)
            && !$show_checkbox_on_every_top_level
        ) {
            return;
        }

        ob_start(); ?>
            <input
                type="checkbox"
                class="conserve-page-checkbox"
                data-post-id="<?php echo esc_attr($post_id); ?>"
                <?php checked($is_page_conserved, true); ?>
            />
        <?php echo ob_get_clean();
    }

    public static function handleToggleConservePageStatus()
    {
        if (
            !isset($_POST['post_id'])
            || !isset($_POST['is_conserved'])
            || empty($_POST['post_id'])
            || empty($_POST['is_conserved'])
        ) {
            wp_send_json_error();
        }

        $post_id = intval($_POST['post_id']);
        $is_conserved = $_POST['is_conserved'] === 'true';

        self::updateConservePageMeta($post_id, $is_conserved);
        wp_send_json_success();
    }

    public static function handleSetConservePageForChild($post_id, $post, $update)
    {
        if ($post->post_type !== 'page') {
            return;
        }

        if ($post->post_parent === 0) {
            delete_post_meta($post_id, self::META_KEY);

            return;
        }

        $is_parent_conserved = get_post_meta($post->post_parent, self::META_KEY, true);

        if ($is_parent_conserved === 'true') {
            update_post_meta($post_id, self::META_KEY, 'true');

            return;
        }

        delete_post_meta($post_id, self::META_KEY);
    }

    public static function handleFilterConservePageQuery($query)
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if (self::isSearchForConservedPages()) {
            self::handleSearchConservedPages($query);
        }

        if (
            (isset($_GET['post_type']) && $_GET['post_type'] === 'page' && count($_GET) === 1) ||
            (isset($_GET['post_type']) && $_GET['post_type'] === 'page' && isset($_GET['all_posts']) && $_GET['all_posts'] === '1')
        ) {
            self::handleConservedPagesMetaQuery($query);
        }
    }

    private static function isSearchForConservedPages()
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $query = parse_url($referer, PHP_URL_QUERY);

        if (empty($referer) || $query) {
            return false;
        }

        parse_str($query, $params);

        return isset($params['conserved_pages']) && $params['conserved_pages'] === '1';
    }


    private static function handleSearchConservedPages($query)
    {
        $meta_query = [
            'relation' => 'AND',
            [
                'key' => self::META_KEY,
                'value' => 'true',
                'compare' => '='
            ]
        ];

        if (isset($_GET['s']) && !empty($_GET['s'])) {
            $query->set('s', sanitize_text_field($_GET['s']));
        }

        $query->set('suppress_filters', true);
        $query->set('meta_query', $meta_query);
    }

    private static function handleConservedPagesMetaQuery($query)
    {
        $meta_query = [
            'relation' => 'OR',
            [
                'key' => self::META_KEY,
                'compare' => 'NOT EXISTS',
            ],
            [
                'key' => self::META_KEY,
                'value' => 'true',
                'compare' => '!=',
            ],
        ];

        $query->set('meta_query', $meta_query);
    }

    public static function handleFilterPageCount($views)
    {
        $page_counts = self::getPageCounts();

        $conserved_pages = $page_counts['conserved'];
        $not_conserved_pages = $page_counts['not_conserved'];

        $current_url = $_SERVER['REQUEST_URI'];
        $parsed_url = parse_url($current_url);
        $query = $parsed_url['query'] ?? '';

        $is_all_posts = (
            $query === 'post_type=page&all_posts=1' ||
            $query === 'post_type=page'
        );

        $is_conserved_filter_active = (
            isset($_GET['conserved_pages']) &&
            $_GET['conserved_pages'] === '1'
        );

        $class_all_posts = $is_all_posts ? 'current' : '';
        $class_conserved_pages = $is_conserved_filter_active ? 'current' : '';

        $views['all'] = sprintf(
            '<a href="%s" class="%s" aria-current="page">%s (%d)</a>',
            'edit.php?post_type=page&all_posts=1',
            esc_attr($class_all_posts),
            __('All excl. Conserved', 'cp'),
            $not_conserved_pages
        );

        $new_views = [];

        foreach ($views as $key => $value) {
            $new_views[$key] = $value;

            if ($key === 'all') {
                $new_views['conserved_pages'] = sprintf(
                    '<a href="%s" class="%s">%s (%d)</a>',
                    'edit.php?post_type=page&conserved_pages=1',
                    esc_attr($class_conserved_pages),
                    __('All incl. Conserved', 'cp'),
                    $conserved_pages + $not_conserved_pages
                );
            }
        }

        return $new_views;
    }

    private static function getPageCounts()
    {
        global $wpdb;

        $sql = "
            SELECT
                SUM(CASE WHEN LOWER(pm.meta_value) = %s THEN 1 ELSE 0 END) AS conserved,
                SUM(CASE WHEN pm.meta_id IS NULL OR LOWER(pm.meta_value) != %s THEN 1 ELSE 0 END) AS not_conserved
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
            WHERE p.post_type = 'page' AND p.post_status != 'auto-draft'
        ";

        $query = $wpdb->prepare($sql, 'true', 'true', self::META_KEY);
        $results = $wpdb->get_row($query);

        return [
            'conserved' => $results->conserved,
            'not_conserved' => $results->not_conserved
        ];
    }

    private static function isPageConserved($post_id)
    {
        return get_post_meta($post_id, self::META_KEY, true) === 'true';
    }

    private static function updateConservePageMeta($post_id, $is_conserved)
    {
        if ($is_conserved) {
            update_post_meta($post_id, self::META_KEY, 'true');
        } else {
            delete_post_meta($post_id, self::META_KEY);
        }

        $child_pages = get_pages([
            'child_of' => $post_id,
            'post_type' => 'page',
            'fields' => 'ids',
        ]);

        foreach ($child_pages as $child_id) {
            $is_conserved
                ? update_post_meta($child_id->ID, self::META_KEY, 'true')
                : delete_post_meta($child_id->ID, self::META_KEY);
        }
    }
}
