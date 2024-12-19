var AgentFy = (function () {
    var agentfy_data = [];

    var init = async function (callback = '') {
        $.ajax({
            method: 'post',
            url: 'index.php?route=extension/module/agentfy/getData',
            data: '',
            dataType: 'json',
            success: function (json) {
                console.log(json['agentId']);
                agentfy({
                    agentId: json['agentId'],
                    ...json['options']
                })
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

window.addEventListener('load', function () {
    AgentFy.init();
});

