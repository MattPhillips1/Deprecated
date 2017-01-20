// Matthew Phillips 2016
// Javascript for my website


//
menuLinkSelected = ""
menuLinkClicked = ""

$(document).ready(function(){
	$(document.getElementById("name")).animate({opacity: "1"}, 1250);
});

$(window).on("scroll", function(){
	yPosition = window.pageYOffset;
	if ($(window).scrollTop() + 150 > $(document.getElementById("casing")).position().top && document.getElementById("link_menu").className != "high_menu") {
		document.getElementById("link_menu").className = "high_menu";
	} else if ($(window).scrollTop() + 150 < $(document.getElementById("casing")).position().top && document.getElementById("link_menu").className == "high_menu") {
		document.getElementById("link_menu").className = "section_menu";
		menu = $(document.getElementById("link_menu"));



	}
	viewedSection = whatSection(yPosition);
	if (isElementBackground(viewedSection) && menuLinkSelected != viewedSection.id){
		makeLinkSelected(document.getElementById(viewedSection.id + "_link"));
	}

});

$(".menu_link").hover(function(){
		if (this.id != menuLinkSelected){
			$(this).css("color", "#7C7F80");
			$(this).css("border-left", "1px solid red");
		}
	}, function(){	
		if (this.id != menuLinkSelected){
			$(this).css("color", "black");
			$(this).css("border-left", "1px hidden red");
		}
	}
	);

$(".menu_link").click(function(){
	menuLinkClicked = this.id;
	idToScoll = menuLinkClicked.slice(0,-5);
	jumpTo(idToScoll)
});


function showContactDetails(){
	document.getElementById("contact_detail").innerHTML = "Email: <a href=\"mailto:matt.phillips121@gmail.com\">matt.phillips121@gmail.com</a>";
}

function jumpTo(field){
	$('html, body').animate({
		scrollTop: $("#" + field).offset().top
	}, 'slow');
}

function replaceAndShow(id){
	fadeOut(id);
	fadeIn(id);
	id = id.substring(0,4);

	slideToggle(id + "hidden");
}

function fadeOut(id){
	$("#" + id).fadeOut("slow", function() { 

		if (document.getElementById(id).className === "arrow"){
			document.getElementById(id).className = "up_arrow";
		}else{
			document.getElementById(id).className = "arrow";
		}
	});
}

function fadeIn(id){
	$("#" + id).fadeIn("slow");
}

function slideToggle(id){
	$("#" + id).slideToggle("slow");
}

function makeLinkSelected(link){

	makeLinkNormal(menuLinkSelected);
	menuLinkSelected = link.id;
	$(link).css("color", "black");
	$(link).css("border-left", "2px solid red");
	$(link).css("font-weight", "bold");
}

function makeLinkNormal(linkID){
	old = document.getElementById(linkID);
	$(old).css("font-weight", "normal");
	$(old).css("border-left", "1px hidden red");
}

function whatSection(screenPosition){
	if ($(window).scrollTop() + $(window).height() <= $(document).height() - 10){
		element = document.elementFromPoint(0, 300);
	} else {
		element = document.getElementById("resume");
	}
	return element;
}

function isElementBackground(element){
	if (element.className == "background_colour" || element.className == "background_white" || element.className == "land_page"){
		return true;
	} else {
		return false;
	}
}



