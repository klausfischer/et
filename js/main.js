$(document).ready(function() {

	// enable/disable submit button
	if($("#form-i-input").val().length > 0) {
		$("#button--submit").removeAttr("disabled");
	}
	$("#form-i-input").on("keyup", function(event) {
		var taval = $(this).val();
		if (taval && taval.length > 0) {
			$("#button--submit").removeAttr("disabled");
		} else {
			$("#button--submit").attr("disabled", "disabled");
		}
	});
});