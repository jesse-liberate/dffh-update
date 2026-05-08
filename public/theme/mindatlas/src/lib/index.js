console.log("src/lib/index.js included");

let Lib = {
    api_path: {
        theme_support: M.cfg.wwwroot + "/blocks/theme_support/api/index.php",
    },
    mock_api_path: {
        theme_support:
            "http://localhost:5555/blocks/theme_support/api/index.php",
    },
    isIE() {
        var ua = window.navigator.userAgent;
        var msie = ua.indexOf("MSIE ");

        if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) {
            // If Internet Explorer, return version number
            console.log(
                parseInt(ua.substring(msie + 5, ua.indexOf(".", msie)))
            );
            return true;
        } // If another browser, return 0
        else {
            console.log(ua);
            return false;
        }
    },
};

export default Lib;
