$(function () {
	$(".editor").each(function(){
		var simplemde = new SimpleMDE({
			element: this,
			forceSync: true,
			hideIcons: ["side-by-side", "fullscreen"],
			spellChecker: false,
			toolbar: ["bold", "italic", "heading", "|",
				"quote", "unordered-list", "ordered-list", "|",
				"link", "table", "|",
				"undo", "redo", "|",
				"preview", "guide"
			]
		});
	});
});
