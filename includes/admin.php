<?php

class Cloakup_Admin
{
    /**
     * Constructor will create the menu item
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_menu_cloakup_page'));
    }

    /**
     * Menu item will allow us to load the page to display the table
     */
    public function add_menu_cloakup_page()
    {
        add_menu_page('Cloakup', 'Cloakup', 'manage_options', 'cloakup.php', array($this, 'list_table_page'));
    }

    /**
     * Display the list table page
     *
     * @return Void
     */
    public function list_table_page()
    {

        if (!isset($_GET['action'])) {
            include __DIR__ . '/../views/home.php';
        } elseif ($_GET['action'] == 'add') {
            include __DIR__ . '/../views/add.php';
        } elseif ($_GET['action'] == 'delete') {
            $exampleListTable = new Cloakup_Table();
            $exampleListTable->delete_page();
            include __DIR__ . '/../views/home.php';
        }
    }
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class Cloakup_Table extends WP_List_Table
{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort($data, array(&$this, 'sort_data'));

        $perPage = 10;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args(array(
            'total_items' => $totalItems,
            'per_page' => $perPage
        ));

        $data = array_slice($data, (($currentPage - 1) * $perPage), $perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'campaign_id' => 'ID',
            'campaign_name' => 'Campanha',
            'link' => 'Página',
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('title' => array('title', false));
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        $data = array();
        global $wpdb;

        $table_name = $wpdb->prefix . 'cloakup';

        $sql = "SELECT * FROM $table_name";

        $results = $wpdb->get_results($sql);

        foreach ($results as $result) {
            // get post by result post_id
            $post = get_post($result->post_id);

            $data[] = array(
                'id' => $result->id,
                'campaign_id' => '<a href="https://app.cloakup.me/campaigns/' . $result->campaign_id . '" target="_blank">' . $result->campaign_id . '</a>',
                'campaign_name' => $result->campaign_name,
                'link' => '<a href="' . get_permalink($post->ID) . '" target="_blank">' . $post->post_title . '</a>',
            );
        }

        return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param Array $item Data
     * @param String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
            case 'campaign_id':
            case 'campaign_name':
            case 'link':
                return $item[$column_name];

            default:
                return print_r($item, true);
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data($a, $b)
    {
        // Set defaults
        $orderby = 'title';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if (!empty($_GET['orderby'])) {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if (!empty($_GET['order'])) {
            $order = $_GET['order'];
        }


        $result = strcmp($a[$orderby], $b[$orderby]);

        if ($order === 'asc') {
            return $result;
        }

        return -$result;
    }

    public function column_campaign_id($item)
    {
        $actions = array(
            'delete' => sprintf('<a href="?page=cloakup.php&action=delete&cloakup_id=%s">Apagar</a>', $item['id']),
        );

        return sprintf('%1$s %2$s', $item['campaign_id'], $this->row_actions($actions));
    }

    function add_page()
    {
        include __DIR__ . '/../views/add.php';
    }

    function exists_page($post_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cloakup';
        $sql = "SELECT * FROM $table_name WHERE post_id = $post_id";
        $results = $wpdb->get_results($sql);
        if (count($results) > 0) {
            return true;
        }
        return false;
    }

    function create_campaign($post_id, $campaign_id, $campaign_name, $campaign_slug, $api_key)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cloakup';

        $result = $wpdb->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'campaign_id' => $campaign_id,
                'campaign_name' => $campaign_name,
                'campaign_slug' => $campaign_slug,
                'api_key' => $api_key,
            ),
            array(
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );

        return $result !== false;
    }

    function delete_page()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cloakup';
        $wpdb->delete($table_name, array('id' => $_GET['cloakup_id']));
    }
}
