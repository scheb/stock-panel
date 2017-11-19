$(document).ready(function() {
	//External links
	$('a[rel*=external]').click( function() {
		window.open(this.href);
		return false;
	});

    var setPrivacy = function(isEnabled) {
        $.cookie('privacy', isEnabled ? 1 : 0, { expires: 360 });
        if (isEnabled) {
            $(".privacy").hide();
            $(".btn-privacy .glyphicon").addClass("glyphicon-eye-close").removeClass("glyphicon-eye-open");
        }
        else {
            $(".privacy").show();
            $(".btn-privacy .glyphicon").addClass("glyphicon-eye-open").removeClass("glyphicon-eye-close");
        }
    };

    var togglePrivacy = function() {
        if ($.cookie('privacy') == "1") {
            setPrivacy(false);
        }
        else {
            setPrivacy(true);
        }
    };

    $(".btn-privacy").click(togglePrivacy);
    setPrivacy($.cookie('privacy') == "1");

    // Charts overview only
    $('#period-tabs a').click(function (e) {
        // e.preventDefault();
        var period = $(this).attr('data-period');
        $('#stock-charts .chart').each(function () {
            var imageUrl = $(this).attr('data-image-url').replace(/\{period\}/, period);
            console.log(this, imageUrl);
            $(this).css('background-image', 'url(' + imageUrl + ')');
        });
    });
});
