<?php

class block_training_report_helper {
    const DEFAULT_PIE_COMPLETED_COLOR = '#CBDDE6';
    const DEFAULT_PIE_NOT_COMPLETED_COLOR = '#EEEEEE';
    const DEFAULT_PIE_COMPLETED_HIGHLIGHT_COLOR = '#9DC2D5';
    const DEFAULT_PIE_NOT_COMPLETED_HIGHLIGHT_COLOR = '#BCB8B8';

    const DEFAULT_BAR_COMPLETED_COLOR = '#CBDDE6';
    const DEFAULT_BAR_NOT_COMPLETED_COLOR = '#EEEEEE';

    const DEFAULT_COURSE_OVERVIEW_PERCENTAGE_TEXT_COLOR = '#EEEEEE';
    const DEFAULT_COURSE_OVERVIEW_PERCENTAGE_BACKGROUND_COLOR = '#CBDDE6';

    public static function is_user_manager($userid) {
        global $DB;

        if (!static::is_hierarchy_installed()) {
            return false;
        }

        return $DB->record_exists_sql("SELECT 'x'
            FROM {hierarchy_user} hu
            JOIN {hierarchy_node} hn
            ON hn.id = hu.node_id
            JOIN {hierarchy_node} team
            ON team.parent_node_id = hn.id
            WHERE hu.user_id = ?", [$userid]);
    }

    public static function is_hierarchy_installed() {
        return !empty(get_config('tool_hierarchy', 'version'));
    }

    public static function is_user_in_hierarchy($userid) {
        global $DB;
        if (!static::is_hierarchy_installed()) {
            return false;
        }
        return $DB->record_exists('hierarchy_user', ['user_id' => $userid]);
    }
}
