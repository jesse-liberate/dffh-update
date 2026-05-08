require(['jquery'], function($) {
    update_deadline_type();
    $('#id_deadline_type').on('change', update_deadline_type);
    toggle_admin_list();
    $('#id_siteadmin').on('change', toggle_admin_list);

    function update_deadline_type() {
        var deadline_type = $('#id_deadline_type').val();
        var course_menu = $('#fitem_id_course');
        var user_menu = $('#fitem_id_user');
        var cohort_menu = $('#fitem_id_cohort');

        // disable_row(course_menu);
        disable_row(user_menu);
        disable_row(cohort_menu);

        if (deadline_type == 'course') {
            enable_row(course_menu);
        } else if (deadline_type == 'user') {
            enable_row(user_menu);
        } else if (deadline_type == 'cohort') {
            enable_row(cohort_menu);
        }
    }

    function disable_row(table_row) {
        table_row.hide();
        table_row.find('select').attr('disable', 'disable');
    }

    function enable_row(table_row) {
        table_row.show();
        table_row.find('select').removeAttr('disable');
    }

    function toggle_admin_list() {
        if (!$('#id_siteadmin').is(':checked')) {
            $('#id_emailtoadmin').attr('disabled', 'disabled');
        } else {
            $('#id_emailtoadmin').removeAttr('disabled');
        }
    }
});