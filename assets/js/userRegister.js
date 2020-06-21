$(document).ready(function () {

    $("#user_register_email").keyup(function () {

        var email = $(this).val().trim();

        if (email != '') {

            $.ajax({
                url: '/register/check?name=' + email,
                type: 'get',
                success: function (response) {
                    if (response == true) {
                        $('#emailvalidation').html('✔️');
                    } else {
                        $('#emailvalidation').html('Email bereits benutzt');
                    }


                }
            });
        } else {
            $("#emailvalidation").html("");
        }

    });

    $("#user_register_nickname").keyup(function () {

        var nickname = $(this).val().trim();

        if (nickname != '') {

            $.ajax({
                url: '/register/check?name=' + nickname,
                type: 'get',
                success: function (response) {
                    if (response == true) {
                        $('#nicknamevalidation').html('✔️');
                    } else {
                        $('#nicknamevalidation').html('Nickname bereits benutzt');
                    }


                }
            });
        } else {
            $("#nicknamevalidation").html("");
        }

    });

});