console.log("module/login/index.js loaded");

if ($(".theme-mindatlas-template-login").length) {
    setTimeout(() => {
        $(".carousel").carousel({
            interval: 3000,
        });
    }, 1000);

    // adjust carousel indicater position
    $("ol.carousel-indicators").css("bottom", $("#page-footer").outerHeight());
}
