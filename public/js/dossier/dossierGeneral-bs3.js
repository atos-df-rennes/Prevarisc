$(document).ready(function(){	
	//CHARGEMENT DE L'ENTETE DES DOSSIERS AVEC L'INFORMATIONS DES ETABS
	//Affichage et affectation des différents champs !
	//Empeche la touche entrée de valider le formulaire

	//JQuery UI Date picker
	$('.date').live('click', function() {
		$(this).datepicker({showOn:'focus'}).focus();
	});
	
	//Pour les heures
	$('.time').live('focus', function() {
		$(this).timeEntry($.timeEntry.regional['fr']);
	});
			
	$("#DATEINSERT_INPUT").mask("99/99/9999",{placeholder:" "});

	$("#addNumDoc").live('click',function(){
		if($("#NUM_DOCURBA").val() != ''){
			$("<div class='docurba' style=''><input type='hidden' name='docUrba[]' value='"+$("#docurbaVal").html()+$("#NUM_DOCURBA").val()+"' id='urba_"+$("#docurbaVal").html()+$("#NUM_DOCURBA").val()+"'/>"+$("#docurbaVal").html()+$("#NUM_DOCURBA").val()+" <a href='' idDocurba='"+$("#docurbaVal").html()+$("#selectNature").val()+"'class='suppDocUrba'>&times;</a></div>").insertBefore("#listeDocUrba");
			$("#NUM_DOCURBA").val('');
		}
		return false;
	});
	
	$(".suppDocUrba").live('click',function(){
		$(this).parent().remove();
		return false;
	});
	
	
	$("#OBJET_DOSSIER").blur(function(){
		if($("#OBJET_DOSSIER").val() != ''){
			$("#OBJET_DOSSIER").css("border-color","black");
		}
	});	

	$(".docManquant").blur(function() {
		if($(".docManquant").val() != '') {
			$(".docManquant").css("border-color","black");
		}
	});
	
	$("#dateDocManquant_1").blur(function() {
		if($("#dateDocManquant_1").val() != '') {
			$("#dateDocManquant_1").css("border-color","black");
		}
	});
	
	//Permet de vider un input d'une date pour que celle-ci ne s'affiche plus
	$(".suppDate").live('click',function(){
		$(this).prev('.date').attr('value','');			
		return false;
	});

	$(".hideCalendar").live('click',function(){
		return false;
	});
}); //FIN DOCUMENT READY FUNCTION
