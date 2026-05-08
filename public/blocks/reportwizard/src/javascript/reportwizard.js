console.log("reportwizard.js loaded version 201804110324");

// /////////////////    form report js  ////////////////////////////////
if ($('form.form_report').length) {

    /*---------------------FOR ENROL DATE COMPLETION DATE ------------------------*/
    // initial datepicker
    $( "form.form_report .datepicker" ).datepicker({
        dateFormat: "dd/mm/yy"
    });
    
    /*---------------------FOR MULTI SELECTION ------------------------*/
    // initial datepicker
    $("form.form_report .chosen-select").chosen({width: '206px'});

    /*---------------------FOR COURSE TYPE AND COURSE SELECTOR ------------------------*/
    // show/hide course selector
    toggle_course_selector($('input[type=radio][name="reporttype"]:checked').val());
    $('input[type=radio][name="reporttype"]').change(function(){
        toggle_course_selector(this.value);
    });

    /*---------------------FOR ACCESS TYPE AND SHARE TO SELECTOR ------------------------*/
    // console.log($('input[type=radio][name="access_type"]:checked').val());
    toggle_shareto_selector($('input[type=radio][name="access_type"]:checked').val());
    $('input[type=radio][name="access_type"]').change(function(){
        toggle_shareto_selector($('input[type=radio][name="access_type"]:checked').val());
        // toggle_course_selector(this.value);
        // console.log($('input[type=radio][name="access_type"]:checked').val());
    });

    /*---------------------FOR CHOOSING HIERARCHY NODES------------------------*/
    (function($){
    // init jstree
    $('#jstree').jstree({ 
        'core' : {
            'data' : hierarchy_nodes
        },
        'plugins' : ['search'],
        'search' :  {
            "show_only_matches" : true
        },
    });

    // update hierarchy selection
    $('#jstree').on('changed.jstree', function (e, data) {
        var i, j, r = []; list = [];
        for(i = 0, j = data.selected.length; i < j; i++) {
            r.push(data.instance.get_node(data.selected[i]).text);
            list.push(data.instance.get_node(data.selected[i]).id);
        }

        if (data.action == "ready" ) {
            if ($('#selectednodenames').val()) {
                // deselect_all paramater true means not trigger change envent
                $('#jstree').jstree(true).deselect_all(true);
                $('#selectednodes').val().split(',').map(function(node) {
                  $('#jstree').jstree(true).select_node(node);
                });               
            }else{
                var node_names = r.join(', ');

                $('#selection').html(r.join(', '));
                $('#selectednodes').val(list.join(','));

                $('#selectednodenames').val(node_names);            
                $('#hie_search').val(node_names);       
            }

        }

        if (data.action == "select_node") {
            var node_names = r.join(', ');

            $('#selection').html(r.join(', '));
            $('#selectednodes').val(list.join(','));

            $('#selectednodenames').val(node_names);            
            $('#hie_search').val(node_names);            
        }

        // console.log("NODE ID: " + data.node.id);
        // console.log(data);
        if(data.node != undefined) {
            if(data.node.id != root_node_id)
                $('#hierarchy').val(data.node.id);
            else
                $('#hierarchy').removeAttr('value');
        }

    }).jstree();

    // jstree search
    var to = false;
    $('#hie_search').keyup(function () {
        if(to) { clearTimeout(to); }
        to = setTimeout(function () {
            var v = $('#hie_search').val();
            $('#jstree').jstree(true).search(v);
        }, 250);
    });

    /*-------------------------FOR SHARE TO-----------------------------*/
    // init jstree_shareto
    $('#jstree_shareto').jstree({ 
        'core' : {
            'data' : hierarchy_nodes
        },
        'plugins' : ['search'],
        'search' :  {
            "show_only_matches" : true
        },
    });

    // update hierarchy selection
    $('#jstree_shareto').on('changed.jstree', function (e, data) {
        var i, j, r = []; list = [];
        for(i = 0, j = data.selected.length; i < j; i++) {
            r.push(data.instance.get_node(data.selected[i]).text);
            list.push(data.instance.get_node(data.selected[i]).id);
        }

        if (data.action == "ready") {
            if ($('#selectednodenames_shareto').val()) {
                // deselect_all paramater true means not trigger change envent
                $('#jstree_shareto').jstree(true).deselect_all(true);
                $('#selectednodes_shareto').val().split(',').map(function(node) {
                  $('#jstree_shareto').jstree(true).select_node(node);
                });               
            }else{
                var node_names = r.join(', ');
                
                $('#selection_shareto').html(r.join(', '));
                $('#selectednodes_shareto').val(list.join(','));            

                $('#selectednodenames_shareto').val(node_names);
                $('#hie_search_shareto').val(node_names);    
            }

        }

        if (data.action == "select_node") {
            var node_names = r.join(', ');

            $('#selection_shareto').html(r.join(', '));
            $('#selectednodes_shareto').val(list.join(','));            

            $('#selectednodenames_shareto').val(node_names);
            $('#hie_search_shareto').val(node_names);            
        }

        // console.log("NODE ID: " + data.node.id);
        // console.log(data);
        if(data.node != undefined) {
            if(data.node.id != root_node_id)
                $('#hierarchy_shareto').val(data.node.id);
            else
                $('#hierarchy_shareto').removeAttr('value');
        }

    }).jstree();

    // jstree_shareto search
    var to = false;
    $('#hie_search_shareto').keyup(function () {
        if(to) { clearTimeout(to); }
        to = setTimeout(function () {
            var v = $('#hie_search_shareto').val();
            $('#jstree_shareto').jstree(true).search(v);
        }, 250);
    });

    })(jQuery)

}
// /////////////////  end of  form report js  ////////////////////////////////

function toggle_course_selector(report_type){
    $('#report_completion_condition').show();
    $('#report_enrolleddate_condition').show();
    if (report_type == REPORT_TYPE_ACTIVITY) {
        $('#report_category_course').hide();
        $('#report_activity_course').show();
    }else{
        $('#report_category_course').show();
        $('#report_activity_course').hide();
        if(report_type == REPORT_TYPE_MANDATORY_ONLINE){
            $('#report_completion_condition').hide();
            $('#report_enrolleddate_condition').hide();
        }
    }
}


function toggle_shareto_selector(access_type){
    if (access_type == $('#report_share_to').attr('data-private')) {
        $('#report_share_to').hide();
    }else{
        $('#report_share_to').show();
    }       
}
