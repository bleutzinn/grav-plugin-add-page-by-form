$(function () {
	$(document).ready(function() {
		new SimpleMDE({
			element: document.getElementById("simplemde"),
			spellChecker: false,
			hideIcons: ["side-by-side", "fullscreen"],
			forceSync: true
		});
  });
});
