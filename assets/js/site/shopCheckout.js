import $ from 'jquery';

document.addEventListener('DOMContentLoaded', function () {
    $('.card-tablist button[data-toggle]').on('click', function () {
        if($(this).hasClass("active")) {
            return;
        }
        $('.card-tablist button[data-toggle]').each(function () {
            $(this).removeClass("active");
            $($(this).attr("data-target")).removeClass("show").removeClass("active");
        })

        $(this).addClass("active");
        let targetId = $(this).attr("data-target");
        $(targetId).tab('show');

        $('#addonWrapper').removeClass("d-none");
    })
});