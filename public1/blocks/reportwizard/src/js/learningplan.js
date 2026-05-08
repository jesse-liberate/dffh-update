$(function() {
    $("body").on("click", "#id_addkpi", function(e){
        var counter = parseInt($(".cls_kpi_counter").val());
        var new_counter = counter + 1;
        var str_kpi = 'Goal';
        if($('#id_utype').val()==1) str_kpi = 'KPI';
        var string_kpi = "<div id='fgroup_id_group_kpi"+new_counter+"' class='fitem fitem_fgroup'><div class='fitemtitle'><div class='fgrouplabel'><label>"+str_kpi+" "+new_counter+"</label></div></div><fieldset class='felement fgroup'> <input size='42' name='kpi"+new_counter+"' type='text' id='id_kpi"+new_counter+"'> <input title='Delete KPI' name='del_btn_"+new_counter+"' value='Delete' type='button' class='del_btn_kpi'> </fieldset></div>";
        var new_kpi = $(string_kpi);
        //$("#fgroup_id_group_kpi"+counter).after(new_kpi);
        $("#fitem_id_addkpi").before(new_kpi);
        
        $(".cls_kpi_counter").val(new_counter);
    });
    $("body").on("click", ".del_btn_kpi", function(e){
        var $kpi = $(this).parents('.fitem.fitem_fgroup:first');
        $kpi.hide();
        $kpi.find('input[type=text]').val('');
    });
});