<?php

namespace LEXO\CP\Core\Plugin;

use WP_Query;

use const LEXO\CP\{
    DOMAIN
};

class Conserver
{
    private const META_KEY = '_conserve_page';
    private array $exclude_pages = [];

    public function __construct()
    {
        $this->exclude_pages = apply_filters(DOMAIN . 'exclude-pages', []);
    }

    public function applyCustomClassToConvertedPages(array $classes, array $css_class, int $post_id): array
    {
        $is_page_conserved = $this->isPageConserved($post_id);

        if ($is_page_conserved) {
            $classes[] = 'conserved-page-row';
        }

        return $classes;
    }

    public function addConservePageColumn(array $posts_columns): array
    {
        $posts_columns['conserve_page'] = __('Conserve Page', 'cp');
        return $posts_columns;
    }

    public function displayConservePageCheckbox(string $column_name, int $post_id): void
    {
        if ($column_name !== 'conserve_page') {
            return;
        }

        if (in_array($post_id, $this->exclude_pages)) {
            return;
        }

        $is_page_conserved = $this->isPageConserved($post_id);

        ob_start(); ?>
            <input
                type="checkbox"
                class="conserve-page-checkbox"
                id="conserve-page-checkbox-<?php echo esc_attr($post_id); ?>"
                data-post-id="<?php echo esc_attr($post_id); ?>"
                <?php checked($is_page_conserved, true); ?>
            />
            <label
                for="conserve-page-checkbox-<?php echo esc_attr($post_id); ?>"
                class="conserve-page-checkbox-label"
            ></label>
        <?php echo ob_get_clean();
    }

    public function toggleConservePageStatus(): void
    {
        if (
            !isset($_POST['post_id'])
            || !isset($_POST['is_conserved'])
            || empty($_POST['post_id'])
            || empty($_POST['is_conserved'])
        ) {
            wp_send_json_error();
        }

        $post_id = intval(sanitize_text_field($_POST['post_id']));
        $is_conserved = sanitize_text_field($_POST['is_conserved']) === 'true';

        $this->updateConservePageMeta($post_id, $is_conserved);
        wp_send_json_success();
    }

    private function isPageConserved(int $post_id): bool
    {
        return get_post_meta($post_id, self::META_KEY, true) === 'true';
    }

    private function updateConservePageMeta(int $post_id, bool $is_conserved): void
    {
        do_action(DOMAIN . '/before-conserving-pages');

        $is_conserved
            ? update_post_meta($post_id, self::META_KEY, 'true')
            : delete_post_meta($post_id, self::META_KEY);

        $child_pages = get_pages([
            'child_of'  => $post_id,
            'post_type' => 'page',
            'exclude'   => $this->exclude_pages
        ]);

        foreach ($child_pages as $child_page) {
            $is_conserved
                ? update_post_meta($child_page->ID, self::META_KEY, 'true')
                : delete_post_meta($child_page->ID, self::META_KEY);
        }

        do_action(DOMAIN . '/after-conserving-pages');
    }

    public function filterPageCount(array $views): array
    {
        $page_counts = $this->getPageCounts();

        $conserved_pages = $page_counts['conserved'];
        $not_conserved_pages = $page_counts['not_conserved'];

        $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

        $is_all_posts = (
            $query === 'post_type=page&all_posts=1' ||
            $query === 'post_type=page'
        );

        $class_all_posts = $is_all_posts ? 'current' : '';

        $class_conserved_pages = (
            isset($_GET['conserved_pages'])
            && $_GET['conserved_pages'] === '1'
        )
        || (
            $this->isSearchForConservedPages()
            && isset($_GET['s'])
        )
            ? 'current'
            : '';

        $views['all'] = sprintf(
            '<a href="%s" class="%s" aria-current="page">%s <span class="count">(%d)</span></a>',
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
                    '<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
                    'edit.php?post_type=page&conserved_pages=1',
                    esc_attr($class_conserved_pages),
                    __('All incl. Conserved', 'cp'),
                    $conserved_pages + $not_conserved_pages
                );
            }
        }

        return $new_views;
    }

    private function getPageCounts(): array
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

    public function filterConservePageQuery(WP_Query $query): void
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($this->isSearchForConservedPages()) {
            $this->handleSearchConservedPages($query);
        }

        if (
            (
                isset($_GET['post_type'])
                && $_GET['post_type'] === 'page'
                && count($_GET) === 1
            )
            || (
                isset($_GET['post_type'])
                && $_GET['post_type'] === 'page'
                && isset($_GET['all_posts'])
                && $_GET['all_posts'] === '1'
            )
        ) {
            $this->handleConservedPagesMetaQuery($query);
        }
    }

    private function handleConservedPagesMetaQuery(WP_Query $query): void
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

    private function isSearchForConservedPages(): bool
    {
        if (empty($_SERVER['HTTP_REFERER'])) {
            return false;
        }

        $referer = filter_var($_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL);

        if (!$referer) {
            return false;
        }

        $query = parse_url($referer, PHP_URL_QUERY);

        if (empty($query)) {
            return false;
        }

        parse_str($query, $params);

        return isset($params['conserved_pages']) && $params['conserved_pages'] === '1';
    }

    private function handleSearchConservedPages(WP_Query $query): void
    {
        $meta_query = [
            'relation' => 'AND',
            [
                'key' => self::META_KEY,
                'value' => 'true',
                'compare' => '='
            ]
        ];

        $query->set('suppress_filters', true);
        $query->set('meta_query', $meta_query);
    }
}
