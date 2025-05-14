var AgentFy = (function () {
    var agentfy_data = [];

    var init = async function (callback = '') {
        $.ajax({
            method: 'post',
            url: 'index.php?route=extension/agentfy/module/agentfy',
            data: '',
            dataType: 'json',
            success: function (json) {
                console.log(json['agentId']);

                if (json['agentfy_client']) {
                    var script = document.createElement('script');
                    script.src = json['agentfy_client'];
                    script.type = 'text/javascript';
                    script.async = true;

                    // Execute agentfy({...}) after the script is loaded
                    script.onload = function () {
                        agentfy({
                            agentId: json['agentId'],
                            apiKey: json['code'],
                            ...json['options']
                        });
                    };

                    document.head.appendChild(script);
                } else {
                    // Fallback if no script URL is provided
                    agentfy({
                        agentId: json['agentId'],
                        apiKey: json['code'],
                        ...json['options']
                    });
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    };

    return {
        init: init
    };
}());

(function () {
    AgentFy.init()
})()

