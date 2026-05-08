/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
(function ($) {
  $(function () {    
    $('form#report').on('submit', function (e) {
      if ($(this).find('.validation-error').length) {
        e.preventDefault();
      }
    });

    function toggleCompletionDates() {
      var str = $('#completionstatus option:selected').text();
      if (str === 'Not Completed') {
        $('#completiondate_from').prop('disabled', true);
        $('#completiondate_to').prop('disabled', true);
      } else {
        $('#completiondate_from').prop('disabled', false);
        $('#completiondate_to').prop('disabled', false);
      }
    }

    // You can't search incomplete activity with completion date 
    // disable completion date if incomplete status is selected
    $('#completionstatus').change(function () {
      toggleCompletionDates();
    });

    toggleCompletionDates();

    $(document).tooltip({
      position: {
        my: "left top",
        at: "right top"
      },
      items: "input[type=text].datepicker",
      content: function () {
        var title = $(this).attr('title');
        if (title) {
          return '<i class="icon-warning-sign"></i><span style="font-size: 14px; padding: 10px;">' + title + '</span>';
        }
        return false;
      }
    });

    // @23/07/2018 enhancement
    $('.datepicker').datepicker({dateFormat: 'dd/mm/yy'});
    $('.datepicker').on('change', function () {
      var value = $(this).val();
      if (!value) {
        reset_date_error(this);
      } else {
        var is_valid = moment(value, 'DD/MM/YYYY', true).isValid();
        if (!is_valid) {
          show_date_error(this, 'invalid date');
        } else {
          reset_date_error(this);
        }
      }
    });

    function reset_date_error(ele) {
      //$(ele).css('border-color', '#ccc');
      $(ele).removeClass('validation-error');
      $(ele).attr('title', '');
    }

    function show_date_error(ele, msg) {
      $(ele).addClass('validation-error');
      $(ele).attr('title', 'Invalid date format');
    }
    //$("#completiondate").datepicker({dateFormat: 'dd/mm/yy'});
    //{/literal}{$datepicker_fields}{literal}
    //$("#lastaccess").datepicker({dateFormat: 'dd/mm/yy'});
    //$("#enrolleddate").datepicker({
    //	dateFormat: 'dd/mm/yy',
    //	onSelect: function(date){
    //		$("#completiondate").datepicker( "option", "minDate", date );
    //	}
    //});

    $(".chzn-select").chosen({search_contains: true, width: '100%', placeholder_text_multiple: 'Select some options'});

    // prevent "Enter" key
    $(window).keydown(function (event) {
      if (event.keyCode == 13) {
        event.preventDefault();
        return false;
      }
    });
    $('head').append('<link rel="stylesheet" href="css/jquery-ui.css" type="text/css" media="all">');

    // init jstree
    $('#jstree').jstree({
      'core': {
        'data': JSON.parse($('#id_hierarchy_nodes').html())
      },
      'plugins': ['search'],
      'search': {
        "show_only_matches": true
      },
    });

    // update hierarchy selection
    var root_node_id = $('#id_root_node').html();
    $('#jstree').on('changed.jstree', function (e, data) {
      var i, j, r = [];
      list = [];
      for (i = 0, j = data.selected.length; i < j; i++) {
        r.push(data.instance.get_node(data.selected[i]).text);
        list.push(data.instance.get_node(data.selected[i]).id);
      }
      $('#selection').html(r.join(', '));
      $('#selectednodes').val(list.join(','));
      $('#selectednodenames').val(r.join(', '));
      // console.log("NODE ID: " + data.node.id);
      // console.log(data);
      if (data.node != undefined) {
        if (data.node.id != root_node_id)
          $('#hierarchy').val(data.node.id);
      }

    }).jstree();

    // jstree search
    var to = false;
    $('#hie_search').keyup(function () {
      if (to) {
        clearTimeout(to);
      }
      to = setTimeout(function () {
        var v = $('#hie_search').val();
        $('#jstree').jstree(true).search(v);
      }, 250);
    });
  });
})(jQuery);
