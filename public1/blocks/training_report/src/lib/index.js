let Lib = {
    api_path: {
        general: '/blocks/training_reporting/api/general.php',
    },
    mock_api_path: {
        general: 'http://localhost:5555/mock/api/general.php',
        activity: 'http://localhost:5555/mock/api/activity.php',
    },
    isIE() {
        var ua = window.navigator.userAgent;
        var msie = ua.indexOf("MSIE ");
    
        if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))  // If Internet Explorer, return version number
        {
            console.log(parseInt(ua.substring(msie + 5, ua.indexOf(".", msie))))
            return true
        }
        else  // If another browser, return 0
        {
            console.log(ua)
            return false
        }
    },

}

console.log(M)

export default Lib 