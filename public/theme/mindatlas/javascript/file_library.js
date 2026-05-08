console.log("file_library.js loaded");

highlight_category($(".category-filter").attr("data-active"));
function highlight_category(categoryid) {
    $('.searchin[data-id="' + categoryid + '"]').addClass("selected");
}

$(".searchin").click(function (e) {
    var $this = $(this);
    var categoryid = $this.attr("data-id");
    window.location.href = format_url(categoryid, get_lmsonly());
});

$("#checkbox-lms-only").change(function () {
    window.location.href = format_url(get_categoryid(), get_lmsonly());
});

// search keyword
$("#btn-search").click(function () {
    window.location.href = format_url(
        get_categoryid(),
        get_lmsonly(),
        get_keyword()
    );
});
$("#input-kewword").keypress(function (e) {
    if (e.which == 13) {
        window.location.href = format_url(
            get_categoryid(),
            get_lmsonly(),
            get_keyword()
        );
    }
});

// switch page
$(".page-number").click(function () {
    var page = $(this).attr("data-page");
    window.location.href = format_url(
        get_categoryid(),
        get_lmsonly(),
        get_keyword(),
        page
    );
});
$("#page-prev").click(function () {
    var current = parseInt($(".page-number.selected").attr("data-page"));
    if (current >= 2) {
        window.location.href = format_url(
            get_categoryid(),
            get_lmsonly(),
            get_keyword(),
            current - 1
        );
    }
});
$("#page-next").click(function () {
    var max = parseInt($(".pagination-bar").attr("data-totalpages"));
    var current = parseInt($(".page-number.selected").attr("data-page"));
    if (current < max) {
        window.location.href = format_url(
            get_categoryid(),
            get_lmsonly(),
            get_keyword(),
            current + 1
        );
    }
});

//view item
$(".resource-list-item").click(function () {
    var id = $(this).attr("data-id");
    var param =
        "&categoryid=" +
        get_categoryid() +
        "&lmsonly=" +
        get_lmsonly() +
        "&keyword=" +
        get_keyword();
    // window.location.href = M.cfg.wwwroot+'/blocks/resources/resources_view.php?id='+id+param;
    window.location.href =
        M.cfg.wwwroot +
        "/theme/mindatlas/pages/view_resource.php?id=" +
        id +
        param;
});

function format_url(categoryid, lmsonly, keyword, page) {
    var param = "?categoryid=" + categoryid + "&lmsonly=" + lmsonly;
    if (keyword) {
        param += "&keyword=" + keyword;
    }
    if (page) {
        param += "&page=" + page;
    }
    var new_url =
        M.cfg.wwwroot + "/theme/mindatlas/pages/file_library.php" + param;
    return new_url;
}

function get_categoryid() {
    return $(".searchin.selected").attr("data-id");
}

function get_lmsonly() {
    return $("#checkbox-lms-only").prop("checked");
}

function get_keyword() {
    return $("#input-kewword").val();
}
